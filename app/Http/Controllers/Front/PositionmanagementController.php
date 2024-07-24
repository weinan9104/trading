<?php

namespace App\Http\Controllers\Front;

use App\Models\User;
use App\Models\Trade;
use App\Models\TradeDetail;
use App\Models\Settings;
use Illuminate\Http\Request;
use Laravel\Cashier\Subscription;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class PositionManagementController extends Controller
{
    public function mainFeed(Request $request)
    {
        $search = $request->input('search');

        // Define the first query
        $trades = DB::table('trades as t')
        ->leftJoin('trade_details as td', 't.id', '=', 'td.trade_id')
        ->select([
            't.id',
            't.trade_type',
            't.symbol_image',
            't.entry_date',
            't.trade_symbol',
            't.trade_title',
			't.current_price',
			't.company_name',
            't.trade_direction AS original_trade_direction',
            DB::raw('NULL as child_direction'),
            't.trade_option',
            't.strike_price',
            DB::raw('CASE
                WHEN t.exit_price IS NOT NULL AND t.exit_date IS NOT NULL THEN
                    ((t.entry_price * t.position_size) + COALESCE(SUM(td.entry_price * td.position_size), 0)) /
                    (t.position_size + COALESCE(SUM(td.position_size), 0))
                ELSE
                    t.entry_price
                END AS entry_price'),
            't.stop_price',
            't.target_price',
            DB::raw('CASE
                WHEN t.exit_price IS NOT NULL AND t.exit_date IS NOT NULL THEN
                    (t.position_size + COALESCE(SUM(td.position_size), 0))
                ELSE
                    t.position_size
                END AS position_size'),
            't.exit_price',
            't.exit_date',
            't.trade_description',
            't.chart_image',
            't.close_comment',
            't.close_image',
            't.expiration_date',
            't.created_at',
            't.updated_at'
        ])
        ->groupBy( 't.id', 't.trade_type', 't.symbol_image', 't.entry_date', 't.trade_symbol', 't.trade_direction', 't.trade_option','t.trade_title','t.current_price','t.company_name','t.strike_price', 't.entry_price', 't.stop_price', 't.target_price', 't.position_size',
        't.exit_price', 't.exit_date', 't.trade_description', 't.chart_image', 't.close_comment',
        't.close_image',  't.expiration_date', 't.created_at', 't.updated_at');
       

        // Add search condition for trades
        if (!empty($search)) {
            $trades->where('t.trade_symbol', 'LIKE', '%' . $search . '%');
        }

        // Define the second query and join with the trades table
        $tradeDetails = DB::table('trade_details as td')
        ->join('trades as t', 'td.trade_id', '=', 't.id')
        ->select([
            'td.id',
            't.trade_type',
            't.symbol_image',
            't.entry_date',
            't.trade_symbol',
            't.trade_title',
			't.current_price',
			't.company_name',
            't.trade_direction as original_trade_direction',
            'td.trade_direction as child_direction',
            't.trade_option',
            'td.strike_price',
            'td.entry_price',
            'td.stop_price',
            'td.target_price',
            'td.position_size',
            DB::raw('NULL AS exit_price'),
            DB::raw('NULL AS exit_date'),
            'td.trade_description',
            'td.chart_image',
            DB::raw("'' AS close_comment"),
            DB::raw("'' AS close_image"),
            'td.expiration_date',
            'td.created_at',
            'td.updated_at'
        ])
        ->where('t.trade_status', 'open')
        ->whereNull('t.exit_price')
        ->whereNull('t.exit_date');

        // Add search condition for tradeDetails
        if (!empty($search)) {
            $tradeDetails->where('t.trade_symbol', 'LIKE', '%' . $search . '%');
        }

        $unionQuery = $trades->union($tradeDetails)->orderBy('created_at', 'desc');

        // Combine both queries
        $results = $unionQuery->paginate(12);
        
        //Get Account login info and Billing info
        $billing_data = Subscription::where('user_id', auth()->user()->id)->first();

        $getReportData = $this->generatorReport();
        
        return view('front.trades.main-feed', compact('results', 'billing_data','getReportData'));
    }

    public function openStockTrades(Request $request)
    {
        $query = Trade::with('tradeDetail')
            ->where('trade_status','open')
            ->where('trade_type', 'stock')
            ->whereNull('exit_price')->whereNull('exit_date');  //open trade

             // Handle search query
            $search = $request->input('search');
            if (!empty($search)) {
                $query->where('trade_symbol', 'like', '%' . $search . '%');
            }

            // Fetch the paginated results
            $trades = $query->orderBy('updated_at', 'desc')->paginate(10);

        return view('front.trades.open-stock-trades', compact('trades'));
    }

    public function closedStockTrades(Request $request)
    {
        // $query = Trade::with('tradeDetail')
        // ->where('trade_type', 'stock')
        // ->whereNotNull('exit_price')->whereNotNull('exit_date');  //open trade

        $query = DB::table('trades as t')
        ->leftJoin('trade_details as td', 't.id', '=', 'td.trade_id')
        ->select([
            't.id',
            't.trade_type',
            't.trade_symbol',
            't.trade_direction',
            't.entry_date',
			't.current_price',
			't.company_name',
			't.symbol_image',
            DB::raw('NULL as child_direction'),
            't.trade_option',
            't.strike_price',
            DB::raw('CASE
                WHEN t.exit_price IS NOT NULL AND t.exit_date IS NOT NULL THEN
                    ((t.entry_price * t.position_size) + COALESCE(SUM(td.entry_price * td.position_size), 0)) /
                    (t.position_size + COALESCE(SUM(td.position_size), 0))
                ELSE
                    t.entry_price
                END AS entry_price'),
            't.stop_price',
            't.target_price',
            DB::raw('CASE
                WHEN t.exit_price IS NOT NULL AND t.exit_date IS NOT NULL THEN
                    (t.position_size + COALESCE(SUM(td.position_size), 0))
                ELSE
                    t.position_size
                END AS position_size'),
            't.exit_price',
            't.exit_date',
            't.trade_description',
            't.chart_image',
            't.close_comment',
            't.close_image',
            't.created_at',
            't.updated_at'
        ])
        ->where('trade_type', 'stock')
        ->whereNotNull('exit_price')
        ->whereNotNull('exit_date')
        ->groupBy( 't.id', 't.trade_type', 't.trade_symbol', 't.trade_direction', 't.trade_option',  't.entry_date','t.current_price','t.company_name','t.symbol_image',
       't.strike_price', 't.entry_price', 't.stop_price', 't.target_price', 't.position_size',
        't.exit_price', 't.exit_date', 't.trade_description', 't.chart_image', 't.close_comment',
        't.close_image', 't.created_at', 't.updated_at');

        // Handle search query
        $search = $request->input('search');
        if (!empty($search)) {
            $query->where('trade_symbol', 'like', '%' . $search . '%');
        }

        // Fetch the paginated results
        $trades = $query->orderBy('updated_at', 'desc')->paginate(10);

        return view('front.trades.closed-stock-trades', compact('trades'));
    }

    public function openOptionsTrades(Request $request)
    {
        $query = Trade::with('tradeDetail')
            ->where('trade_status','open')
            ->where('trade_type', 'option')
            ->whereNull('exit_price')->whereNull('exit_date');  //open trade

             // Handle search query
            $search = $request->input('search');
            if (!empty($search)) {
                $query->where('trade_symbol', 'like', '%' . $search . '%');
            }

            // Fetch the paginated results
            $trades = $query->orderBy('updated_at', 'desc')->paginate(10);

        return view('front.trades.open-options-trades', compact('trades'));
    }

    public function closedOptionsTrades(Request $request)
    {
        // $query = Trade::with('tradeDetail')
        // ->where('trade_type', 'option')
        // ->whereNotNull('exit_price')->whereNotNull('exit_date');  //open trade

        $query = DB::table('trades as t')
        ->leftJoin('trade_details as td', 't.id', '=', 'td.trade_id')
        ->select([
            't.id',           
            't.trade_type',
            't.trade_symbol',
            't.trade_direction',
            't.entry_date',
			't.current_price',
			't.company_name',
			't.symbol_image',
            DB::raw('NULL as child_direction'),
            't.trade_option',
            't.strike_price',
            DB::raw('CASE
                WHEN t.exit_price IS NOT NULL AND t.exit_date IS NOT NULL THEN
                    ((t.entry_price * t.position_size) + COALESCE(SUM(td.entry_price * td.position_size), 0)) /
                    (t.position_size + COALESCE(SUM(td.position_size), 0))
                ELSE
                    t.entry_price
                END AS entry_price'),
            't.stop_price',
            't.target_price',
            DB::raw('CASE
                WHEN t.exit_price IS NOT NULL AND t.exit_date IS NOT NULL THEN
                    (t.position_size + COALESCE(SUM(td.position_size), 0))
                ELSE
                    t.position_size
                END AS position_size'),
            't.exit_price',
            't.exit_date',
            't.trade_description',
            't.chart_image',
            't.close_comment',
            't.close_image',
            't.created_at',
            't.updated_at'
        ])
        ->where('trade_type', 'option')
        ->whereNotNull('exit_price')
        ->whereNotNull('exit_date')
        ->groupBy( 't.id', 't.trade_type', 't.trade_symbol', 't.trade_direction', 't.trade_option',  't.entry_date','t.current_price','t.company_name','t.symbol_image','t.strike_price', 't.entry_price', 't.stop_price', 't.target_price', 't.position_size',
        't.exit_price', 't.exit_date', 't.trade_description', 't.chart_image', 't.close_comment',
        't.close_image', 't.created_at', 't.updated_at');

         // Handle search query
        $search = $request->input('search');
        if (!empty($search)) {
            $query->where('trade_symbol', 'like', '%' . $search . '%');
        }

        // Fetch the paginated results
        $trades = $query->orderBy('updated_at', 'desc')->paginate(10);

        return view('front.trades.closed-options-trades', compact('trades'));
    }

    public function tradeDetail($id, $type)
    {
        if($type == 'n') {
            //trade creation alert
            $trade = Trade::where('id', $id)->first();
        }else if ($type == 'a'){
            //trade add alert
            $trade = TradeDetail::with('trade')->where('id', $id)->first();
        }else if ($type == 'c'){
            //trade close alert
            $trade = Trade::with('tradeDetail')->where('id', $id)->first();
        }

        return view('front.trades.trade-detail', compact('trade', 'type'));
    }

    public function updateCloseEvent()
    {
        $obj = User::findorFail(auth()->user()->id);
        $obj->close_feed = 1;
        $obj->save();
    }

    public function generatorReport(){

        $total_open_trades = DB::table('trades')->select('*')->whereIn('trade_type',['stock','option'])->where('trade_status','open')->whereNull('exit_price')->whereNull('exit_date')->get()->count(); 

        $total_closed_trades = DB::table('trades')->select('*')->whereIn('trade_type',['stock','option'])->where('trade_status','closed')->whereNotNull('exit_price')->whereNotNull('exit_date')->get()->count(); 

        $settings = DB::table('settings')->select('*')->first();
        $this_month = Carbon::now();
        $start_month = isset($settings->date) ? Carbon::parse($settings->date) : Carbon::now();
        $diff_month = $start_month->diffInMonths($this_month);

        /***********************************************************************************************************************
         * Buy trades get data
         * 
         ***********************************************************************************************************************/

        $total_buy_winners_amount = 0;
        $total_buy_losers_amount = 0;
        $total_buy_winners_trade_count = 0;

        $buy_trades = DB::table('trades as t')
        ->leftJoin('trade_details as td', 't.id', '=', 'td.trade_id')
        ->select([
            't.id',
            't.entry_price',
            't.position_size',
            DB::raw('CASE
                WHEN t.exit_price IS NOT NULL AND t.exit_date IS NOT NULL THEN
                    GROUP_CONCAT(td.entry_price)
                ELSE
                    null
                END AS add_entry_price'),
            DB::raw('CASE
                WHEN t.exit_price IS NOT NULL AND t.exit_date IS NOT NULL THEN
                    GROUP_CONCAT(td.position_size)
                ELSE
                    null
                END AS add_position_size'),
            DB::raw('CASE
                WHEN t.exit_price IS NOT NULL AND t.exit_date IS NOT NULL THEN
                    GROUP_CONCAT(td.share_qty)
                ELSE
                    null
                END AS add_share_qty'),
            DB::raw('CASE
                WHEN t.exit_price IS NOT NULL AND t.exit_date IS NOT NULL THEN
                    GROUP_CONCAT(td.share_in_amount)
                ELSE
                    null
                END AS add_share_in_amount'),
            't.share_qty',
            't.share_in_amount',
            't.exit_price',
            't.exit_date',
        ])
        ->where('t.trade_direction','buy')->whereIn('t.trade_type',['stock','option'])->where('t.trade_status','closed')->whereNotNull('t.exit_price')->whereNotNull('t.exit_date')
        ->groupBy( 't.id', 't.trade_type', 't.trade_title','t.current_price', 't.entry_price', 't.position_size','t.exit_price', 't.exit_date','t.share_qty',
        't.share_in_amount');
        $buy_trades_all_data = $buy_trades->get();
            
        $total_buy_closed_trades = $buy_trades_all_data->count();
        /**
         * cumulative_return start
        */
        foreach ($buy_trades_all_data as $key => $buy_trade) {
            $entry_time_investment_amount = $buy_trade->entry_price * $buy_trade->share_qty;
            $exit_time_investment_amount = $buy_trade->exit_price * $buy_trade->share_qty;

            $trade_profit_and_loss_find = $exit_time_investment_amount - $entry_time_investment_amount;
            if($trade_profit_and_loss_find > 0 ){
                $total_buy_winners_trade_count++;
                $total_buy_winners_amount = $total_buy_winners_amount + $trade_profit_and_loss_find;
            }else{
                $total_buy_losers_amount = $total_buy_losers_amount + $trade_profit_and_loss_find;
            }

            if(!is_null($buy_trade->add_entry_price) && !is_null($buy_trade->add_position_size) && !is_null($buy_trade->add_share_qty)){
                $add_entry_price = explode(',',$buy_trade->add_entry_price);
                $add_share_qty = explode(',',$buy_trade->add_share_qty);
                $add_position_size = explode(',',$buy_trade->add_position_size);

                foreach ($add_entry_price as $add_key => $add_entry_price_value) {

                    $entry_time_investment_amount_add = $add_entry_price_value * $add_share_qty[$add_key];
                    $exit_time_investment_amount_add = $buy_trade->exit_price * $add_share_qty[$add_key];

                    $trade_profit_and_loss_find_add = $exit_time_investment_amount_add - $entry_time_investment_amount_add;
                    if($trade_profit_and_loss_find_add > 0 ){
                        $total_buy_winners_amount = $total_buy_winners_amount + $trade_profit_and_loss_find_add;
                    }else{
                        $total_buy_losers_amount = $total_buy_losers_amount + $trade_profit_and_loss_find_add;
                    }
                }
            }
        }

        $total_buy_losers_amount_finale =  (str_contains($total_buy_losers_amount,'-')) ? str_replace('-','',$total_buy_losers_amount) : $total_buy_losers_amount;
        
        $buy_accumulative_trade_return = ($total_buy_winners_amount > $total_buy_losers_amount_finale) ? $total_buy_winners_amount - $total_buy_losers_amount_finale : $total_buy_losers_amount_finale - $total_buy_winners_amount ;
        
        /***********************************************************************************************************************
         * Sell trades get data
         * 
        ************************************************************************************************************************/

        $total_sell_winners_amount = 0;
        $total_sell_losers_amount = 0;
        $total_sell_winners_trade_count = 0;

        $sell_trades = DB::table('trades as t')
        ->leftJoin('trade_details as td', 't.id', '=', 'td.trade_id')
        ->select([
            't.id',
            't.entry_price',
            't.position_size',
            DB::raw('CASE
                WHEN t.exit_price IS NOT NULL AND t.exit_date IS NOT NULL THEN
                    GROUP_CONCAT(td.entry_price)
                ELSE
                    null
                END AS add_entry_price'),
            DB::raw('CASE
                WHEN t.exit_price IS NOT NULL AND t.exit_date IS NOT NULL THEN
                    GROUP_CONCAT(td.position_size)
                ELSE
                    null
                END AS add_position_size'),
            DB::raw('CASE
                WHEN t.exit_price IS NOT NULL AND t.exit_date IS NOT NULL THEN
                    GROUP_CONCAT(td.share_qty)
                ELSE
                    null
                END AS add_share_qty'),
            DB::raw('CASE
                WHEN t.exit_price IS NOT NULL AND t.exit_date IS NOT NULL THEN
                    GROUP_CONCAT(td.share_in_amount)
                ELSE
                    null
                END AS add_share_in_amount'),
            't.share_qty',
            't.share_in_amount',
            't.exit_price',
            't.exit_date',
        ])
        ->where('t.trade_direction','sell')->whereIn('t.trade_type',['stock','option'])->where('t.trade_status','closed')->whereNotNull('t.exit_price')->whereNotNull('t.exit_date')
        ->groupBy( 't.id', 't.trade_type', 't.trade_title','t.current_price', 't.entry_price', 't.position_size','t.exit_price', 't.exit_date','t.share_qty',
        't.share_in_amount');
        $sell_trades_all_data = $sell_trades->get();

        $total_sell_closed_trades = $sell_trades_all_data->count();

        /**
         * cumulative_return start
        */
        foreach ($sell_trades_all_data as $key => $sell_trade) {
            $entry_time_investment_amount = $sell_trade->entry_price * $sell_trade->share_qty;
            $exit_time_investment_amount = $sell_trade->exit_price * $sell_trade->share_qty;

            $trade_profit_and_loss_find = $exit_time_investment_amount - $entry_time_investment_amount;
            if($trade_profit_and_loss_find > 0 ){
                $total_sell_winners_trade_count++;
                $total_sell_winners_amount = $total_sell_winners_amount + $trade_profit_and_loss_find;
            }else{
                $total_sell_losers_amount = $total_sell_losers_amount + $trade_profit_and_loss_find;
            }

            if(!is_null($sell_trade->add_entry_price) && !is_null($sell_trade->add_position_size) && !is_null($sell_trade->add_share_qty)){
                $add_entry_price = explode(',',$sell_trade->add_entry_price);
                $add_share_qty = explode(',',$sell_trade->add_share_qty);
                $add_position_size = explode(',',$sell_trade->add_position_size);
                
                foreach ($add_entry_price as $add_key => $add_entry_price_value) {

                    $entry_time_investment_amount_add = $add_entry_price_value * $add_share_qty[$add_key];
                    $exit_time_investment_amount_add = $sell_trade->exit_price * $add_share_qty[$add_key];

                    $trade_profit_and_loss_find_add = $exit_time_investment_amount_add - $entry_time_investment_amount_add;
                    if($trade_profit_and_loss_find_add > 0 ){
                        $total_sell_winners_amount = $total_sell_winners_amount + $trade_profit_and_loss_find_add;
                    }else{
                        $total_sell_losers_amount = $total_sell_losers_amount + $trade_profit_and_loss_find_add;
                    }
                }
            }
        }

        $total_sell_losers_amount_finale = (str_contains($total_sell_losers_amount,'-')) ? str_replace('-','',$total_sell_losers_amount) : $total_sell_losers_amount;

        $sell_accumulative_trade_return =  (($total_sell_losers_amount_finale > 0 && $total_sell_losers_amount_finale > $total_sell_winners_amount) || ($total_sell_winners_amount > $total_sell_losers_amount_finale)) ? $total_sell_winners_amount - $total_sell_losers_amount_finale : $total_sell_losers_amount_finale - $total_sell_winners_amount;

        /***********************************************************************************************************************
         * dashboard report data get
         * 
        ************************************************************************************************************************/
        
        $totals_cumulative_return = $buy_accumulative_trade_return + $sell_accumulative_trade_return;

        $winners = $total_buy_winners_amount + $total_sell_winners_amount;
        $losers = $total_buy_losers_amount + $total_sell_losers_amount;
        if($losers){
            // losers_amount
            $settingsObj = Settings::first();
            if(!is_null($settingsObj)){
                if($settingsObj->losers_amount !== $losers){
                    $settingsObj->losers_amount = $losers;
                    $settingsObj->save();
                }
            }
        }

        $losers_finale = (str_contains($losers,'-')) ? str_replace('-','',$losers) : $losers;

        $total_profit_factor = ($losers) ? ($winners/$losers_finale) : 0;

        $total_winners_trade_count = $total_buy_winners_trade_count + $total_sell_winners_trade_count;
        $total_closed_trades_count = $total_buy_closed_trades + $total_sell_closed_trades;
        

        $win_percentage = ($total_closed_trades_count)? ($total_winners_trade_count/$total_closed_trades_count)*100 : 0;

        // Total Return
        $total_return = (($settings->portfolio_size+$totals_cumulative_return)/$settings->portfolio_size)*100;
        
        // Average Monthly Return
        $average_monthly_return = (!empty($total_return) && !empty($diff_month))? $total_return / $diff_month : 0;

        return ['totals_cumulative_return' => $totals_cumulative_return,'total_profit_factor' => $total_profit_factor,'winners'=>$winners,'losers'=>$losers,'total_open_trades' => $total_open_trades,'total_closed_trades' => $total_closed_trades,'win_percentage' => $win_percentage,'average_monthly_return' => $average_monthly_return,'portfolio_size' => $settings->portfolio_size,'investment_amount' =>$settings->investment_amount ];
        
    }
}

