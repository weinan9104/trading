<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Trade;
use App\Models\TradeDetail;

class ScriptController extends Controller
{
    public function index(Request $request)
    {
        $trades = Trade::get();
        foreach($trades as $trade) {
            if(!empty($trade->exit_date) && !empty($trade->exit_price)) {
                $tradeNewObj = new Trade();
                $tradeNewObj->trade_type = $trade->trade_type;
                $tradeNewObj->trade_symbol = $trade->trade_symbol;
                $tradeNewObj->trade_status = 'closed';
                $tradeNewObj->symbol_image = $trade->symbol_image;
                $tradeNewObj->company_name = $trade->company_name;
                $tradeNewObj->trade_direction = $trade->trade_direction;
                $tradeNewObj->trade_title = $trade->trade_title;
                $tradeNewObj->trade_option = $trade->trade_option;
                $tradeNewObj->expiration_date = $trade->expiration_date;
                $tradeNewObj->current_price = $trade->current_price;
                $tradeNewObj->strike_price = $trade->strike_price;
                $tradeNewObj->entry_price = $trade->entry_price;
                $tradeNewObj->stop_price = $trade->stop_price;
                $tradeNewObj->target_price = $trade->target_price;
                $tradeNewObj->entry_date = $trade->entry_date;
                $tradeNewObj->share_qty = $trade->share_qty;
                $tradeNewObj->share_in_amount = $trade->share_in_amount;
                $tradeNewObj->exit_date = $trade->exit_date;
                $tradeNewObj->exit_price = $trade->exit_price;
                $tradeNewObj->close_comment = $trade->close_comment;
                $tradeNewObj->close_image = $trade->close_image;
                $tradeNewObj->position_size = $trade->position_size;
                $tradeNewObj->trade_description = $trade->trade_description;
                $tradeNewObj->chart_image = $trade->chart_image;
                $tradeNewObj->scheduled_at = $trade->scheduled_at;
                $tradeNewObj->save();

                $tradeDetails = TradeDetail::where('trade_id', $trade->id)->get();
                foreach ($tradeDetails as $key => $tradeDetail) {
                    $tradeNewDetails = new TradeDetail();
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
                Trade::where('id', $trade->id)->update(['trade_status' => 'closed','exit_date' => null,'exit_price' => null,'close_comment' => null,'close_image' => null]);
            } else {
                // open, closed
                Trade::where('id', $trade->id)->update(['trade_status' => 'open']);
            }
        }

        echo "Success full update";
    }
}
