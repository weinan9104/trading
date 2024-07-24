<?php

namespace App\Http\Controllers\Backend;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Trade;
use GuzzleHttp\Client;
use App\Jobs\SendTwilioSMS;
use App\Models\TradeDetail;
use App\Models\Settings;
use Illuminate\Http\Request;
use App\Mail\TradeAddAlertMail;
use App\Mail\TradeCloseAlertMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Mail\TradeCreationAlertMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\FirebasePushController;
use App\Models\TradeLog;
use Illuminate\Support\Facades\Log;

class TradeAlertController extends Controller
{

	public $tradeinSyncText = 'TradeInSync ';
	public function __construct()
	{
		$this->middleware('permission:trade-list|trade-create|trade-edit|trade-delete', ['only' => ['index','store']]);
		$this->middleware('permission:trade-create', ['only' => ['create','store']]);
		$this->middleware('permission:trade-edit', ['only' => ['edit','update']]);
		$this->middleware('permission:trade-delete', ['only' => ['destroy']]);
	}
	/**
	 * Display a listing of the resource.
	 */
	public function index()
	{
		// Log::info("\n".'================================'. "\n".'in_amount : 10000*4/100 = 400. ' . "\n". 'share : 400/50 = 8'. "\n".'================================');
		$parentTrades = Trade::with('tradeDetail')
			->where('trade_status', 'open')
			->where('trade_type', '!=', 'message')
			->whereNull('exit_price')->whereNull('exit_date')  //open trade
			->orderBy('created_at','desc')->paginate(10);

		return view('admin.trade_alert.index', compact('parentTrades'));
	}

	/**
	 * Show the form for creating a new resource.
	 */
	public function create()
	{
		return view('admin.trade_alert.create');
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(Request $request)
	{
		$trade_type = $request->trade_type;
		$trade_symbol = $request->trade_symbol;
		$trade_option = $request->trade_option;
		$trade_direction = $request->trade_direction;
		$expiration_date = $request->expiration_date;
		$strike_price = str_replace(',','',$request->strike_price);
		$stop_price = $request->stop_price;
		$target_price = str_replace(',', '', $request->target_price);
		$entry_price = str_replace(',', '', $request->entry_price);
		$entry_date = $request->entry_date;
		$position_size = $request->position_size;
		$trade_description = $request->quill_html;

		// Extract base64 encoded image data from Quill content
		$pattern = '/data:image\/(.*?);base64,([^\'"]*)/';

		$trade_description = preg_replace_callback($pattern, function ($match) {
			$extension = $match[1]; // Get image extension
			$base64Image = $match[2]; // Get base64 image data
			$imageData = base64_decode($base64Image); // Decode base64 data

			 // Generate a unique identifier for the image name
			$uniqueIdentifier = uniqid();

			// Combine unique identifier and current timestamp for the image name
			$imageName = 'image_' . $uniqueIdentifier.'_'. time() . '.' . $extension;
			$imagePath = public_path('uploads/trade/' . $imageName);
			file_put_contents($imagePath, $imageData);

			// Replace base64 encoded image with URL
			$imageUrl = asset('uploads/trade/' . $imageName);

			return $imageUrl;
		}, $trade_description);

		//duplication issue
		//for stock's opened trades.  by the trade symbol
		if($trade_type == 'stock'){
			$tradeCount = Trade::where([
				'trade_status' => 'open',
				'trade_type' => 'stock',
				'trade_symbol' => $trade_symbol
			])
			->whereNull('exit_price')
			->whereNull('exit_date')
			->count();

			$msg = 'Symbol already exists';
		}else{
			//for option by the whole content
			$tradeCount = Trade::where([
				'trade_status' => 'open',
				'trade_type' => 'option',
				'trade_symbol' => $trade_symbol,
				'expiration_date' => $expiration_date,
				'trade_option' => $trade_option,
				'strike_price' => $strike_price
			])
			->whereNull('exit_price')
			->whereNull('exit_date')
			->count();

			$msg = 'Contract already exists';

		}
		
		if($tradeCount > 0)
			return back()->withErrors($msg)->withInput();

		DB::beginTransaction();
		try{

			$tradeObj = new Trade();
			$tradeObj->trade_type = $trade_type;
			$tradeObj->trade_symbol = $trade_symbol;
			$tradeObj->trade_direction = $trade_direction;
			$tradeObj->stop_price = str_replace(',','',$stop_price);
			$tradeObj->target_price = str_replace(',','',$target_price);
			$tradeObj->entry_date = $entry_date;
			$tradeObj->entry_price = str_replace(',','',$entry_price);
			$tradeObj->current_price = str_replace(',','',$request->current_price);
			$tradeObj->company_name = $request->company_name;
			$tradeObj->position_size = str_replace(',','',$position_size);
			$tradeObj->trade_description = $trade_description;
			$tradeObj->trade_status = 'open';

			if($trade_type == 'option'){
				$tradeObj->trade_option = $trade_option;
				$tradeObj->expiration_date = $expiration_date;
				$tradeObj->strike_price = $strike_price;
			}
		
			if($request->hasFile('image')){
				$request->validate([
					'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
				]);

				$imageName = time().'.'.$request->image->extension();
				$request->image->move(public_path('uploads/trade'), $imageName);

				$tradeObj->chart_image = 'uploads/trade/' . $imageName;
			}

			$tradeObj->symbol_image = $request->symbol_image;

			// share qty find
			$settings = Settings::first();
			if(!is_null($settings)){
				$portfolio_size = $settings->portfolio_size;
				$position_size = str_replace(',','',$position_size);
				$entry_price_s = str_replace(',','',$entry_price);

				// position size find investment amount find by (portfolio size multiplication position size in to division) this formula used
				// (portfolio_size * position_size / 100)
				$find_investment_amount = (($portfolio_size*$position_size)/100);
				
				// share find by(investment amount division by entry price) 
				// find_investment_amount/entry_price_s = round figure 
				$share = round($find_investment_amount/$entry_price_s);
				$share = ($share == 0)? 1 : $share;
				$tradeObj->share_qty = $share;
				
				$share_investment_amount = $entry_price_s*$share;

				$tradeObj->share_in_amount = $share_investment_amount;

				$new_investment_amount = $settings->investment_amount + $share_investment_amount;
				$settings->investment_amount = $new_investment_amount;

				$settings->portfolio_size = $portfolio_size - $share_investment_amount;
				$settings->save();
			}
			$tradeObj->save();
			DB::commit();

			//Bulk trade creation email to activated users's email
			$activeSubscribers = $this->getActiveSubscriptionUsers();

			if($trade_type == 'option'){
				$trade_mail_title = $this->tradeinSyncText.ucfirst($trade_type).' '.'Alert';

				$sms_msg = $this->tradeinSyncText.ucfirst($trade_type).' '.'Alert - New Trade '.strtoupper($trade_direction). ' '.strtoupper($trade_symbol).' '.Carbon::parse($expiration_date)->format('ymd').ucfirst(substr($trade_option,0,1)).$strike_price;

				$body_first_title = ucfirst($trade_type).' '.'Alert - New Trade '.strtoupper($trade_direction). ' '.strtoupper($trade_symbol).' '.Carbon::parse($expiration_date)->format('ymd').ucfirst(substr($trade_option,0,1)).$strike_price;

				$body_title = strtoupper($trade_direction).' '.strtoupper($trade_symbol).' '.Carbon::parse($expiration_date)->format('M d, Y').' $'.number_format($strike_price, 0).' '.ucfirst($trade_option).'@$'.$entry_price.' or better';
			}else{
				$trade_mail_title = $this->tradeinSyncText.ucfirst($trade_type).' '.'Alert';

				$sms_msg = $this->tradeinSyncText.ucfirst($trade_type).' '.'Alert - New Trade '.strtoupper($trade_direction). ' '.strtoupper($trade_symbol);

				$body_first_title = ucfirst($trade_type).' '.'Alert - New Trade '.strtoupper($trade_direction). ' '.strtoupper($trade_symbol);

				$body_title = strtoupper($trade_direction).' '.strtoupper($trade_symbol);
			}
			$url = route('front.trade-detail', [
				'id'=>$tradeObj->id,
				'type'=>'n'
			]);

			$data = [
				'title' => $trade_mail_title,
				'body' => [
					'first_title' => $body_first_title,
					'title' => $body_title,
					'trade_entry_date' => Carbon::parse($entry_date)->format('m/d/Y'),
					'trade_entry_price' => $entry_price,
					'position_size' => $position_size,
					'stop_price' => $stop_price,
					'target_price' => $target_price,
					'comments' => $trade_description,
					'visit' =>  $url
				]
			];

			foreach($activeSubscribers as $subscriber){
				Mail::to($subscriber->email)->queue(new TradeCreationAlertMail($data));
			}

			Artisan::call('queue:work --stop-when-empty');

			//Bulk trade creation notification to activated users' phone
			$msg = $sms_msg.' '.$url;
			foreach($activeSubscribers as $subscriber)
			{
				 //if user subscribed for the mobile notification and verified the phone number
				if($subscriber->mobile_notification_setting == 1 && $subscriber->mobile_verified_at !== null)
				{
					SendTwilioSMS::dispatch($subscriber->mobile_number, $msg);
				}
			}

			//Send Mobile users----------------
			$push_service = new FirebasePushController();
			$push_service->notificationToAllMobiles($msg);
			//-------------------------------

			return redirect()->route('trades.index')->with('flash_success', 'Trade was created successfully!')->withInput();

		}catch(Exception $ex){
			DB::rollBack();
			return back()->withErrors($ex->getMessage())->withInput();
		}

	}

	/**
	 * Display the specified resource.
	 */
	public function show(string $id)
	{
		//
	}

	/**
	 * Show the form for editing the specified resource.
	 */
	public function edit(string $id)
	{
		//
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update(Request $request, string $id)
	{
		//
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy(string $id)
	{
		//
	}

	public function tradeAdd(Request $request)
	{
		$addFormID = $request->addFormID;
		$addTradeType = $request->addTradeType;
		$addTradeSymbol = $request->addTradeSymbol;
		$addTradeOption = $request->addTradeOption;
		$addTradeStrikePrice = $request->addTradeStrikePrice;
		$addExpirationDate = $request->addExpirationDate;
		$addTradeDirection = $request->addTradeDirection;
		$addEntryDate = $request->addEntryDate;
		$addBuyPrice = $request->addBuyPrice;
		$addPositionSize = $request->addPositionSize;
		$addStopPrice = $request->addStopPrice;
		$addTargetPrice = $request->addTargetPrice;
		$addComments = $request->quill_add_html;

		// Extract base64 encoded image data from Quill content
		$pattern = '/data:image\/(.*?);base64,([^\'"]*)/';

		$addComments = preg_replace_callback($pattern, function ($match) {
			$extension = $match[1]; // Get image extension
			$base64Image = $match[2]; // Get base64 image data
			$imageData = base64_decode($base64Image); // Decode base64 data

			// Generate a unique identifier for the image name
			$uniqueIdentifier = uniqid();

			// Combine unique identifier and current timestamp for the image name
			$imageName = 'image_' . $uniqueIdentifier.'_'. time() . '.' . $extension;
			$imagePath = public_path('uploads/trade/' . $imageName);
			file_put_contents($imagePath, $imageData);

			// Replace base64 encoded image with URL
			$imageUrl = asset('uploads/trade/' . $imageName);

			return $imageUrl;
		}, $addComments);

		DB::beginTransaction();
		try{
			$tradeObj = new TradeDetail();
			$tradeObj->trade_id = $addFormID;
			$tradeObj->trade_direction = 'Add';
			$tradeObj->entry_date = $addEntryDate;
			$tradeObj->entry_price = str_replace(',','',$addBuyPrice);
			$tradeObj->position_size = str_replace(',','',$addPositionSize);
			$tradeObj->stop_price = str_replace(',','',$addStopPrice);
			$tradeObj->target_price = str_replace(',','',$addTargetPrice);
			$tradeObj->trade_description = $addComments;

			if($addTradeType == 'option'){
				$tradeObj->expiration_date = \Carbon\Carbon::parse($addExpirationDate)->format('Y-m-d');
				$tradeObj->strike_price = str_replace(',','',$addTradeStrikePrice);
			}

			if($request->hasFile('addImage')){
				$imageName = time().'.'.$request->addImage->extension();
				$request->addImage->move(public_path('uploads/trade'), $imageName);

				$tradeObj->chart_image = 'uploads/trade/' . $imageName;
			}

			$settings = Settings::first();
			if(!is_null($settings)){
				$position_size = str_replace(',','',$addPositionSize);
				$entry_price = str_replace(',','',$addBuyPrice);
				$portfolio_size = $settings->portfolio_size;
				$position_size = str_replace(',','',$position_size);
				$entry_price_s = str_replace(',','',$entry_price);

				// investment amount find by (portfolio size multiplication position size in to division) this formula used
				// (portfolio_size * position_size / 100)
				// 100000*5/100 = 5000
				$find_investment_amount = (($portfolio_size*$position_size)/100);
				
				// share find by(investment amount division by entry price)  
				$share = round($find_investment_amount/$entry_price_s);
				$tradeObj->share_qty = $share;
				
				$share_investment_amount = $entry_price_s*$share;

				$tradeObj->share_in_amount = $share_investment_amount;

				$new_investment_amount = $settings->investment_amount + $share_investment_amount;
				$settings->investment_amount = $new_investment_amount;

				$settings->portfolio_size = $portfolio_size - $share_investment_amount;
				$settings->save();
			}

			$tradeObj->save();
			DB::commit();

			 //Bulk Trade add email to activated users
			$activeSubscribers = $this->getActiveSubscriptionUsers();
			
			
			if($addTradeType == 'option'){
				$trade_mail_title = $this->tradeinSyncText.$addTradeType.' Alert';

				$sms_title = $this->tradeinSyncText.$addTradeType.' Alert - '.strtoupper($addTradeDirection). ' '.strtoupper($addTradeSymbol).' '.Carbon::parse($addExpirationDate)
				->format('ymd').ucfirst(substr($addTradeOption,0,1)).$addTradeStrikePrice.' (Add)';

				$body_first_title = ucfirst($addTradeType).' Alert - '.strtoupper($addTradeDirection). ' '.strtoupper($addTradeSymbol).' '.Carbon::parse($addExpirationDate)
				->format('ymd').ucfirst(substr($addTradeOption,0,1)).$addTradeStrikePrice.' (Add)';

				$body_title = strtoupper($addTradeDirection).' '.strtoupper($addTradeSymbol).' '.Carbon::parse($addExpirationDate)
				->format('M d, Y').'$'.$addTradeStrikePrice.ucfirst($addTradeOption).' (Add) @$'.$addBuyPrice.' or better';

			}else{
				$trade_mail_title = $this->tradeinSyncText.$addTradeType.' Alert';

				$sms_title = $this->tradeinSyncText.$addTradeType.' Alert - '.strtoupper($addTradeDirection). ' '.strtoupper($addTradeSymbol).' '. '(Add)';

				$body_first_title = ucfirst($addTradeType).' Alert - '.strtoupper($addTradeDirection). ' '.strtoupper($addTradeSymbol).' '. '(Add)';

				$body_title = strtoupper($addTradeDirection).' '.strtoupper($addTradeSymbol). ' (Add) @ $'.$addBuyPrice.' or better';
			}

			$url = route('front.trade-detail', [
				'id'=>$tradeObj->id,
				'type'=>'a'
			]);

			 $data = [
				 'title' => $trade_mail_title,
				 'body' => [
					'first_title' => $body_first_title,
					 'title' => $body_title,
					 'trade_entry_date' => Carbon::parse($addEntryDate)->format('m/d/Y'),
					 'trade_entry_price' => $addBuyPrice,
					 'position_size' => $addPositionSize,
					 'stop_price' => $addStopPrice,
					 'target_price' => $addTargetPrice,
					 'comments' => $addComments,
					 'visit' => $url
				 ]
			 ];

			foreach($activeSubscribers as $subscriber){
				Mail::to($subscriber->email)->queue(new TradeAddAlertMail($data));
			}
			
			Artisan::call('queue:work --stop-when-empty');

			//Bulk trade creation notification to activated users' phone
			$msg = $sms_title.' '.$url;

			foreach($activeSubscribers as $subscriber)
			{
				//if user subscribed for the mobile notification and verified the phone number
				if($subscriber->mobile_notification_setting == 1 && $subscriber->mobile_verified_at !== null)
				{
					SendTwilioSMS::dispatch($subscriber->mobile_number, $msg);
				}
			}

			return back()->with('flash_success', 'Trade was added successfully!');
		}catch(Exception $ex){
			DB::rollBack();
			return back()->withErrors($ex->getMessage());
		}
	}


	public function tradeClose(Request $request)
	{
		$closeFormID = $request->closeFormID;
		$closeTradeType = $request->closeTradeType;
		$closeExitDate = $request->closeExitDate;
		$closeExitPrice = str_replace(',','',$request->closeExitPrice);
		$closeTradeEntryPrice = (float)str_replace(['$', '(', ')'], '', $request->closeTradeEntryPrice);
		$closedComments = $request->quill_close_html;
		$closeTradeSymbol = $request->closeTradeSymbol;
		$closeTradeDirection = $request->closeTradeDirection;
		$closeTradePositionSize = $request->closeTradePositionSize;
		$closeTradeStrikePrice = $request->closeTradeStrikePrice;
		$closeTradeOption = $request->closeTradeOption;
		$closeTradeShareQTY = $request->closeTradeShareQTY;
		$closeTradeShareInAmount = $request->closeTradeShareInAmount;
		$closeOptionExpirationDate = $request->closeOptionExpirationDate;

		 // Extract base64 encoded image data from Quill content
		 $pattern = '/data:image\/(.*?);base64,([^\'"]*)/';

		 $closedComments = preg_replace_callback($pattern, function ($match) {
			 $extension = $match[1]; // Get image extension
			 $base64Image = $match[2]; // Get base64 image data
			 $imageData = base64_decode($base64Image); // Decode base64 data

			 // Generate a unique identifier for the image name
			 $uniqueIdentifier = uniqid();

			 // Combine unique identifier and current timestamp for the image name
			 $imageName = 'image_' . $uniqueIdentifier.'_'. time() . '.' . $extension;
			 $imagePath = public_path('uploads/trade/' . $imageName);
			 file_put_contents($imagePath, $imageData);

			 // Replace base64 encoded image with URL
			 $imageUrl = asset('uploads/trade/' . $imageName);

			 return $imageUrl;
		 }, $closedComments);

		DB::beginTransaction();
		try{
			$tradeObj = Trade::findorFail($closeFormID);
			$tradeObj->trade_status = 'closed';
			$tradeNewObj = new Trade;
			$tradeNewObj->trade_type = $tradeObj->trade_type;
			$tradeNewObj->trade_symbol = $tradeObj->trade_symbol;
			$tradeNewObj->trade_status = 'closed';
			$tradeNewObj->symbol_image = $tradeObj->symbol_image;
			$tradeNewObj->company_name = $tradeObj->company_name;
			$tradeNewObj->trade_direction = $tradeObj->trade_direction;
			$tradeNewObj->trade_title =$tradeObj->trade_title;
			$tradeNewObj->trade_option =$tradeObj->trade_option;
			$tradeNewObj->expiration_date =$tradeObj->expiration_date;
			$tradeNewObj->current_price = $tradeObj->current_price;
			$tradeNewObj->strike_price =$tradeObj->strike_price;
			$tradeNewObj->entry_price = $tradeObj->entry_price;
			$tradeNewObj->stop_price = $tradeObj->stop_price;
			$tradeNewObj->target_price = $tradeObj->target_price;
			$tradeNewObj->entry_date = $tradeObj->entry_date;
			$tradeNewObj->share_qty = $tradeObj->share_qty;
			$tradeNewObj->share_in_amount = $tradeObj->share_in_amount;
			$tradeNewObj->exit_date = $closeExitDate;
			$tradeNewObj->exit_price = $closeExitPrice;
			$tradeNewObj->close_comment = $closedComments;
			$tradeNewObj->close_image =$tradeObj->close_image;
			$tradeNewObj->position_size = $tradeObj->position_size;
			$tradeNewObj->trade_description =$tradeObj->trade_description;
			$tradeNewObj->chart_image = $tradeObj->chart_image;
			$tradeNewObj->scheduled_at =$tradeObj->scheduled_at;

			if($request->hasFile('closeImage')){
				$imageName = time().'.'.$request->closeImage->extension();
				$request->closeImage->move(public_path('uploads/trade'), $imageName);

				$tradeNewObj->close_image = 'uploads/trade/' . $imageName;
			}

			// share qty find
			$settingsObj = Settings::first();
			if(!is_null($settingsObj)){
				$portfolio_size = $settingsObj->portfolio_size;
				$totle_investment_amount = $settingsObj->investment_amount;
				$share_close_investment_amount = $closeExitPrice*$closeTradeShareQTY;
				
				$find_portfolio = $portfolio_size + $share_close_investment_amount;
				$find_investment_amount = $totle_investment_amount - $closeTradeShareInAmount;
				
				Log::info("\n".'================================'. "\n".'name:'.$closeTradeSymbol .'('.$closeFormID.')'. "\n".'portfolio_size :'.$portfolio_size . "\n". 'totle_investment_amount :'.$totle_investment_amount. "\n".'find_portfolio:'.$portfolio_size.'+'.$share_close_investment_amount.'='.$find_portfolio. "\n".'find_investment_amount:'.$totle_investment_amount .'-'. $closeTradeShareInAmount.'='.$find_investment_amount. "\n".'================================');

				Settings::where('id', $settingsObj->id)->update(['portfolio_size' => $find_portfolio,'investment_amount' => $find_investment_amount]);
			}
			$tradeNewObj->save();
			// at time close
			$tradeDetails = TradeDetail::where('trade_id',$tradeObj->id)->get();
			if(!is_null($tradeDetails)){
				
				foreach ($tradeDetails as $key => $tradeDetail) {
					$settingsObjAdd = Settings::first();
					if(!is_null($settingsObjAdd)){
						$portfolio_size_add = $settingsObjAdd->portfolio_size;
						$totle_investment_amount_add = $settingsObjAdd->investment_amount;
						$share_close_investment_amount_add = $closeExitPrice * $tradeDetail->share_qty;

						$find_portfolio_add = $portfolio_size_add + $share_close_investment_amount_add;
						$find_investment_amount_add = $totle_investment_amount_add - $tradeDetail->share_in_amount;
						
						Log::info("\n".'================================'. "\n".'name:'.$closeTradeSymbol.'(Add-'.$tradeDetail->id.')'. "\n".'portfolio_size_add :'.$portfolio_size_add . "\n". 'totle_investment_amount_add :'.$totle_investment_amount_add. "\n".'find_portfolio_add:'.$portfolio_size_add.'+'.$share_close_investment_amount_add.'='.$find_portfolio_add. "\n".'find_investment_amount_add:'.$totle_investment_amount_add .'-'. $tradeDetail->share_in_amount.'='.$find_investment_amount_add. "\n".'================================');

						Settings::where('id', $settingsObjAdd->id)->update(['portfolio_size' => $find_portfolio_add,'investment_amount' => $find_investment_amount_add]);
					}

					$tradeNewDetails = new TradeDetail;
					$tradeNewDetails->trade_id = $tradeNewObj->id;
					$tradeNewDetails->trade_direction = $tradeDetail->trade_direction;
					$tradeNewDetails->expiration_date = $tradeDetail->expiration_date;
					$tradeNewDetails->strike_price = $tradeDetail->strike_price;
					$tradeNewDetails->entry_price = $tradeDetail->entry_price;
					$tradeNewDetails->stop_price = $tradeDetail->stop_price;
					$tradeNewDetails->target_price = $tradeDetail->target_price;
					$tradeNewDetails->entry_date = $tradeDetail->entry_date;
					$tradeNewDetails->share_qty = $tradeDetail->share_qty;
					$tradeNewDetails->share_in_amount = $tradeDetail->share_in_amount;
					$tradeNewDetails->position_size = $tradeDetail->position_size;
					$tradeNewDetails->trade_description = $tradeDetail->trade_description;
					$tradeNewDetails->chart_image = $tradeDetail->chart_image;
					$tradeNewDetails->scheduled_at = $tradeDetail->scheduled_at;
					$tradeNewDetails->save();
				}
			}

			$tradeObj->save();
			DB::commit();

			 //Bulk Trade add email to activated users
			 $activeSubscribers = $this->getActiveSubscriptionUsers();

			 //converted Closed trade Direction from frontend
			 if($closeTradeDirection == 'Buy')
             {
				//original: Sell Trade  [average sell price – buy price]/average sell price]*100.
				if ($closeTradeEntryPrice != 0)
					$profits = ($closeTradeEntryPrice - $closeExitPrice) / $closeTradeEntryPrice * 100;  //closeTradeEntryPrice: it's average price.
				else
					$profits = 0;

             }
             else   // original: Buy Trade
             {
                //Profit % for a buy trade = [[close price- average purchase price]/average purchase price]*100.
                if ($closeTradeEntryPrice != 0)
                    $profits = ($closeExitPrice - $closeTradeEntryPrice) / $closeTradeEntryPrice * 100;
                else
                    $profits = 0;
             }

			if($closeTradeType == 'option')
			{
				$trade_mail_title = $this->tradeinSyncText.$closeTradeType.' Alert';

				$sms_title = $this->tradeinSyncText.$closeTradeType.' Alert - '.strtoupper($closeTradeDirection). ' To Close '.strtoupper($closeTradeSymbol).' '.Carbon::parse($closeOptionExpirationDate)
				->format('ymd').ucfirst(substr($closeTradeOption,0,1)).$closeTradeStrikePrice;

				$body_first_title = ucfirst($closeTradeType).' Alert - '.strtoupper($closeTradeDirection). ' To Close '.strtoupper($closeTradeSymbol).' '.Carbon::parse($closeOptionExpirationDate)
				->format('ymd').ucfirst(substr($closeTradeOption,0,1)).$closeTradeStrikePrice;

				$body_title = strtoupper($closeTradeDirection).' '.strtoupper($closeTradeSymbol).' '.Carbon::parse($closeOptionExpirationDate)->format('M d, Y').' $'
				.$closeTradeStrikePrice.' '.ucfirst($closeTradeOption).' @ $'.$closeExitPrice.' or better';
			}else{
				$trade_mail_title = $this->tradeinSyncText.$closeTradeType.' Alert';

				$sms_title = $this->tradeinSyncText.$closeTradeType.' Alert - '.strtoupper($closeTradeDirection). ' To Close '.strtoupper($closeTradeSymbol).' ';

				$body_first_title = ucfirst($closeTradeType).' Alert - '.strtoupper($closeTradeDirection). ' To Close '.strtoupper($closeTradeSymbol).' ';

				$body_title = strtoupper($closeTradeDirection).' '.strtoupper($closeTradeSymbol);
			}

			 $url = route('front.trade-detail', [
				'id'=>$tradeObj->id,
				'type'=>'c'
			]);

			 $data = [
				 'title' => $trade_mail_title,
				 'body' => [
					'first_title' => $body_first_title,
					 'title' => $body_title,
					 'trade_exit_date' => Carbon::parse($closeExitDate)->format('m/d/Y'),
					 'position_size' => $closeTradePositionSize,
					 'exit_price' => number_format($closeExitPrice, 2),
					 'profits' => number_format($profits, 2),
					 'trade_direction' => $closeTradeDirection,
					 'comments' => $closedComments,
					 'visit' => $url
				 ]
			 ];

			foreach($activeSubscribers as $subscriber){
				Mail::to($subscriber->email)->queue(new TradeCloseAlertMail($data));
			}
			Artisan::call('queue:work --stop-when-empty');


			//Bulk trade creation notification to activated users' phone
			$msg = $sms_title.' '.$url;

			foreach($activeSubscribers as $subscriber)
			{
				 //if user subscribed for the mobile notification and verified the phone number
				if($subscriber->mobile_notification_setting == 1 && $subscriber->mobile_verified_at !== null)
				{
					SendTwilioSMS::dispatch($subscriber->mobile_number, $msg);
				}
			}

			return back()->with('flash_success', 'Trade was closed successfully!')->withInput();
		}catch(Exception $ex){
			DB::rollBack();
			return back()->withErrors($ex->getMessage());
		}
	}

	private function getActiveSubscriptionUsers()
	{
		$activeSubscribers = User::whereHas('subscriptions', function ($query) {
			$query->where('ends_at', '>', now())
					->orWhereNull('ends_at');
		})->get();

		return $activeSubscribers;
	}

	function searchTread(Request $request) : JsonResponse {
		
		$symbol = $request->get('symbol');
		$type = $request->get('type');
		$apiKey = 'rVepuhvI6BzfIXsCa6P3JdygCmXAYL7p';
		$apiDomain = 'https://financialmodelingprep.com/api/v3';

		$resultLists = [];
		switch($type) {
			case 'list-companies':
				$url_info = "$apiDomain/search?query=$symbol&limit=10&apikey=$apiKey";
			
				$http = new Client();
				$response = $http->request('GET', $url_info);
				$companyLists = json_decode($response->getBody());
		
				foreach($companyLists as $company) {
					$resultLists[] = [
						'value' => $company->symbol,
						'label' => $company->name . " - " . $company->symbol
					];
				}
				break;

			case 'company-details':
				$url_info = "$apiDomain/profile/$symbol?apikey=$apiKey";
			
				$http = new Client();
				$response = $http->request('GET', $url_info);
				$company = json_decode($response->getBody());
				$company = $company[0];
		
				$resultLists = [
					'price' => $company->price,
					'image' => $company->image,
					'company_name' => $company->companyName
				];
				break;
		}

		return response()->json($resultLists);
		
	}
}


