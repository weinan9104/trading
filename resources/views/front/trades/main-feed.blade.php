@extends('layouts.front-master')
@section('title', 'Main Feed')

@section('page-style')
<style>
	.view-comment img {
		width: 100%;
	}
	.view-comment p {
		color: #6c757d !important;
	}
	.port_inve_box{
		float: right;
    	padding: 15px 50px 0px 0px;
	}
	.comment_line_height{
		width: 100%;
    	padding-left: 30px;
	}
	.card-body-box{
		padding: 20px;
	}
</style>
@endsection


@section('content')
     <!-- MAIN -->
     <main class="main-wrapper">
		 <!-- <div class="port_inve_box">
			 <ul>
				 <li>
					 <span>Portfolio Value: ${{--number_format($getReportData['portfolio_size'])--}}</span>
				 </li>
				 <li>
					 <span>Investment Amount: ${{--number_format($getReportData['investment_amount'])--}}</span>
				 </li>
			 </ul>
		 </div> -->
        <div class="main-feed">
            <div class="container-lg">
				<div class="row report-listing g-sm-3 g-2 mb-5">
					<div class="col-lg-3 col-md-4 col-sm-3 col-3">
						<div class="price-box m-0 d-flex flex-column p-3 h-100 gap-2 align-items-start w-100">
							<p class="title mb-0">Cumulative Return ( $ )</p>
							<span class="amount mt-auto">${{number_format($getReportData['totals_cumulative_return'],2)}}</span>
						</div>
					</div>
					<div class="col-lg-3 col-md-4 col-sm-3 col-3">
						<div class="price-box m-0 d-flex flex-column p-3 h-100 gap-2 align-items-start w-100">
							<p class="title mb-0">Profit Factor</p>
							<span class="amount mt-auto ">{{number_format($getReportData['total_profit_factor'],2)}}</span>
						</div>
					</div>
					<div class="col-lg-3 col-md-4 col-sm-3 col-3">
						<div class="price-box m-0 d-flex flex-column p-3 h-100 gap-2 align-items-start w-100">
							<p class="title mb-0">Return $a on Winners</p>
							<span class="amount mt-auto text-success">{{number_format($getReportData['winners'],2)}}</span>
						</div>
					</div>
					<div class="col-lg-3 col-md-4 col-sm-3 col-3">
						<div class="price-box m-0 d-flex flex-column p-3 h-100 gap-2 align-items-start w-100">
							<p class="title mb-0">Return $ on Losers</p>
							<span class="amount mt-auto text-danger">{{number_format($getReportData['losers'],2)}}</span>
						</div>
					</div>
					<div class="col-lg-3 col-md-4 col-sm-3 col-3">
						<div class="price-box m-0 d-flex flex-column p-3 h-100 gap-2 align-items-start w-100">
							<p class="title mb-0">Total Open Trades</p>
							<span class="amount mt-auto">{{$getReportData['total_open_trades']}}</span>
						</div>
					</div>
					<div class="col-lg-3 col-md-4 col-sm-3 col-3">
						<div class="price-box m-0 d-flex flex-column p-3 h-100 gap-2 align-items-start w-100">
							<p class="title mb-0">Total Closed Trades</p>
							<span class="amount mt-auto">{{$getReportData['total_closed_trades']}}</span>
						</div>
					</div>
					<div class="col-lg-3 col-md-4 col-sm-3 col-3">
						<div class="price-box m-0 d-flex flex-column p-3 h-100 gap-2 align-items-start w-100">
							<p class="title mb-0">Win %</p>
							<span class="amount mt-auto">{{number_format($getReportData['win_percentage'])}}%</span>
						</div>
					</div>
					<div class="col-lg-3 col-md-4 col-sm-3 col-3">
						<div class="price-box m-0 d-flex flex-column p-3 h-100 gap-2 align-items-start w-100">
							<p class="title mb-0">Average Monthly Return %</p>
							<span class="amount mt-auto">{{number_format($getReportData['average_monthly_return'])}}%</span>
						</div>
					</div>
				</div>
                <div class="d-flex gap-3 flex-wrap justify-content-between mb-4">
                    <h1 class="title">Main Feed</h1>
                    <div class="search-input">
                        <form action="{{ route('front.main-feed') }}" method="GET" class="mainFeedSearch">
                            <div class="input-group mb-3">
                                <span class="input-group-text svg-24" id="basic-addon1">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M18.319 14.433C19.566 12.8254 20.1537 10.803 19.9625 8.77748C19.7714 6.7519 18.8157 4.87524 17.29 3.52927C15.7642 2.1833 13.783 1.46913 11.7494 1.53206C9.71584 1.59499 7.7826 2.43028 6.34301 3.86801C4.90217 5.30674 4.06414 7.24073 3.99971 9.27588C3.93528 11.311 4.64929 13.2942 5.99624 14.8211C7.34319 16.3481 9.22171 17.304 11.249 17.4941C13.2763 17.6842 15.2997 17.094 16.907 15.844L16.95 15.889L21.192 20.132C21.2849 20.2249 21.3952 20.2986 21.5166 20.3489C21.638 20.3992 21.7681 20.4251 21.8995 20.4251C22.0309 20.4251 22.161 20.3992 22.2824 20.3489C22.4038 20.2986 22.5141 20.2249 22.607 20.132C22.6999 20.0391 22.7736 19.9288 22.8239 19.8074C22.8742 19.686 22.9001 19.5559 22.9001 19.4245C22.9001 19.2931 22.8742 19.163 22.8239 19.0416C22.7736 18.9202 22.6999 18.8099 22.607 18.717L18.364 14.475C18.3494 14.4606 18.3344 14.4466 18.319 14.433ZM16.243 5.28301C16.8076 5.83849 17.2566 6.50026 17.5642 7.23015C17.8718 7.96004 18.0318 8.7436 18.035 9.53563C18.0382 10.3277 17.8846 11.1125 17.583 11.8449C17.2814 12.5772 16.8378 13.2426 16.2777 13.8027C15.7176 14.3628 15.0522 14.8064 14.3199 15.108C13.5875 15.4096 12.8027 15.5632 12.0106 15.56C11.2186 15.5568 10.435 15.3968 9.70514 15.0892C8.97526 14.7816 8.31349 14.3326 7.75801 13.768C6.64793 12.6397 6.02866 11.1185 6.03511 9.53563C6.04156 7.95281 6.67319 6.43666 7.79242 5.31742C8.91165 4.19819 10.4278 3.56656 12.0106 3.56011C13.5935 3.55367 15.1147 4.17293 16.243 5.28301Z" fill="#737373"/>
                                    </svg>
                                </span>
                                <input type="text" name="search" class="form-control search_input" placeholder="Search" value="{{ request()->get('search') }}">
                                <i class="fas fa-times-circle close-icon m-auto p-2"></i>
                                <button type="submit" class="btn btn-primary opacity-0 d-none" >Search</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="row g-3">
                    @foreach ($results as $trade)
						@if ($trade->trade_type == 'message')
						{{-- col-12 col-sm-6 col-lg-6 col-xxl-4 --}}
							<div class="col-12">
								<div class="feed-card-msg">
									<div class="card-badge">
										Message
									</div>
									<div class="card-header">
										<h4 class="feed-card-title">{{ $trade->trade_title }}</h4>
										<h6 class="feed-card-subtitle">{{ date('F d, Y', strtotime($trade->updated_at)) }} | Update</h6>
									</div>
									<div class="card-body">
										<div class="msg-content d-none">
											{!! $trade->trade_description  !!}
										</div>
										<div class="msg-content-10">
											{!! Str::limit(strip_tags($trade->trade_description), 150, '...')  !!}
										</div>
										<a href="javascript:;" class="msgreadmore">Read More</a>
									</div>
									<div class="card-footer">
										{{ date('F d, Y H:i A', strtotime($trade->created_at)) }}
									</div>
								</div>
							</div>
						@else
						{{-- col-12 col-md-6 col-lg-6 col-xxl-4 --}}
						<div class="col-12">
							@php
								$tradeDirection = ucfirst($trade->original_trade_direction);
								$tradeSymbol = strtoupper($trade->trade_symbol);

								$formattedEntryPrice = number_format($trade->entry_price, 2);
								$formattedExitPrice = number_format($trade->exit_price, 2);
							@endphp
							<div class="feed-card gap-3">
								<div class="card-header m-0">
									<div class="card-badge w-100 d-flex flex-column align-items-start gap-2 m-0">
										<div class="top-bar w-100 justify-content-between d-flex align-items-center gap-3 text-uppercase">
											<p class="mb-0 ">{{ucfirst($trade->trade_type)}} Alert</p>
											<p class="date-time mb-0"> <span class="date">{{\Carbon\Carbon::parse($trade->created_at)->format('m/d/Y h:i A')}}</span> </p>
										</div>
										<div class="buy-sell-info d-flex align-items-center gap-1">
											<p class="title mb-0 mt-0 text-uppercase">
												@if ($trade->trade_type == 'option')
													@if ($trade->exit_price !== null && $trade->exit_date !== null)
														@if ($trade->original_trade_direction == 'buy') Sell to Close : @else Buy to Close : @endif

														{{ $tradeSymbol }}{{ !empty($trade->expiration_date) ? ' '.date('M d Y', strtotime($trade->expiration_date)) : ''}}{{(($trade->strike_price) ? ' $'.number_format($trade->strike_price, 2) : '').' '.$trade->trade_option }}{{ ' @ $'.number_format($trade->exit_price, 2)}} 
													@else
														{{$tradeDirection }} TO OPEN {{($trade->child_direction != '') ? ' ('.$trade->child_direction.')' : ''}}:
														
														{{ $tradeSymbol }}{{ !empty($trade->expiration_date) ? ' '.date('M d Y', strtotime($trade->expiration_date)) : ''}}{{(($trade->strike_price) ? ' $'.number_format($trade->strike_price, 2) : '').' '.$trade->trade_option }}{{ ' @ $'.number_format($trade->entry_price, 2)}}
													@endif
												@else
													@if ($trade->exit_price !== null && $trade->exit_date !== null)
														@if ($trade->original_trade_direction == 'buy') Sell to Close : @else Buy to Close : @endif
														@if ($trade->child_direction != '')
															{{$tradeSymbol.' ('.$trade->child_direction.')'}}
														@else
															{{ $tradeSymbol }}{{ ' @ $'.number_format($trade->exit_price, 2)}}
														@endif 
													@else
														{{$tradeDirection }} TO OPEN {{($trade->child_direction != '') ? ' ('.$trade->child_direction.')' : ''}}: 
														
														@if ($trade->child_direction != '')
															{{$tradeSymbol}}{{ ' @ $'.number_format($trade->entry_price, 2)}}
														@else
															{{ $tradeSymbol }}{{($trade->trade_type == 'option') ? ' '.$trade->trade_option : '' }}{{ ' @ $'.number_format($trade->entry_price, 2)}}
														@endif 
													@endif
												@endif
												{{-- {{ $tradeSymbol.' '.date('M d Y', strtotime($trade->entry_date)) }}{{($trade->trade_type == 'option') ? ' '.$trade->trade_option : '' }}{{ ' @ $'.number_format($trade->entry_price, 2)}} --}}
											</p>
										</div>
									</div>
								</div>
								<div class="card-body m-0">
									<div class="card-body-caption card-body-box d-flex flex-column align-items-start gap-2">
										<div class="d-flex alin-items-start gap-2 w-100">
											<div class="stock-company-img">
												@if(!empty($trade->symbol_image)  && file_exists(public_path($trade->symbol_image))) 
													<img src="{{asset($trade->symbol_image)}}" alt="">
												@else 
													@if(!empty($trade->symbol_image)) 
														<img src="{{$trade->symbol_image}}" alt="">
													@endif
												@endif
											</div>
											<ul class="nav feed-card-list flex-grow-1">
												{{-- company name --}}
												<li>
													<span class="listamt fw-normal">
														@if ( $trade->company_name ?? false)
															{{ $trade->company_name }}	
														@endif
													</span>
												</li>
												{{-- Direction --}}
												<li>
													<span class="listtitle fw-bold">Direction:</span>
													<span class="listamt fw-normal">
														@if ($trade->exit_price !== null && $trade->exit_date !== null)
															{{($trade->original_trade_direction == 'buy') ? 'CLOSE' : 'COVER TO CLOSE'}}
														@else
															@if ($trade->original_trade_direction !== null )
																{{($trade->original_trade_direction == 'buy') ? 'LONG' : 'SHORT SELL'}}
																@if ($trade->child_direction != '' && $trade->original_trade_direction == 'buy')
																	({{strtoupper($trade->child_direction)}})
																@endif
															@endif
														@endif
													</span>
												</li>
											</ul>
										</div>
										
										<div class="caption-box d-flex flex-column" >
											<ul class="nav feed-card-list flex-grow-1">
												{{-- Order --}}
												@if ($trade->trade_type == 'option')
													<li>
														<span class="listtitle fw-bold">Order:</span>
														<span class="listamt fw-normal text-uppercase">
															{{ $tradeSymbol.' '.date('M d Y', strtotime($trade->expiration_date)) }}{{ ' $'.number_format($trade->strike_price, 2)}}{{($trade->trade_type == 'option') ? ' '.$trade->trade_option : '' }}
														</span>
													</li>
												@else	
													@if ($trade->exit_price == null && $trade->exit_date == null && $trade->trade_type == 'option')
													<li>
														<span class="listtitle fw-bold">Order:</span>
														<span class="listamt fw-normal text-uppercase">
															{{ $tradeSymbol.' '.date('M d Y', strtotime($trade->entry_date)) }}{{ ' @ $'.number_format($trade->entry_price, 2)}}{{($trade->trade_type == 'option') ? ' '.$trade->trade_option : '' }}
														</span>
													</li>
													@endif
												@endif

												{{-- Price --}}
												@if($trade->original_trade_direction == 'buy')
													<li>
														<span class="listtitle fw-bold">@if($trade->exit_price !== null) Sell Price: @else Buy Price: @endif</span>
														<span class="listamt fw-normal">${{ ($trade->exit_price !== null) ? number_format($trade->exit_price, 2) : number_format($trade->entry_price, 2) }}</span>
													</li>
												@else
													<li>
														<span class="listtitle fw-bold">@if($trade->exit_price !== null) Buy Price: @else Sell Price: @endif</span>
														<span class="listamt fw-normal text-uppercase">${{ ($trade->exit_price !== null) ? number_format($trade->exit_price, 2) : number_format($trade->entry_price, 2) }}</span>
													</li>
												@endif
												
												{{-- Size --}}
												@if ($trade->exit_price !== null && $trade->exit_date !== null)
												<li>
													<span class="listtitle fw-bold">Size:</span>
													<span class="listamt fw-normal">{{rtrim(rtrim(number_format($trade->position_size, 1), '0'), '.')}}% (Full Position)</span>
												</li>
												@else
												<li>
													<span class="listtitle fw-bold">Size:</span>
													<span class="listamt fw-normal">{{rtrim(rtrim(number_format($trade->position_size, 1), '0'), '.')}}% of Portfolio</span>
												</li>
												@endif
												
												{{-- Average --}}
												@if ($trade->child_direction != '')
												<li>
													<span class="listtitle fw-bold">Average :</span>
													<span class="listamt fw-normal">${{stock_average_price_get($trade->id)}}% of Portfolio</span>
												</li>
												@endif

												{{-- Stop / Target --}}
												@if ($trade->exit_price === null && $trade->exit_date === null)
													<li>
														<span class="listtitle fw-bold">Stop:</span>
														<span class="listamt fw-normal">{{ is_numeric($trade->stop_price) ? '$' . number_format((float) $trade->stop_price, 2) : $trade->stop_price }}</span>
													</li>
													<li>
														<span class="listtitle fw-bold">Target:</span>
														<span class="listamt fw-normal">${{number_format($trade->target_price, 2)}}</span>
													</li>
												@endif
													
												{{-- Gain/Loss --}}
												@if ($trade->exit_price !== null && $trade->exit_date !== null)
													@if ($trade->original_trade_direction == 'buy')
														@php $buyProfits = number_format(($trade->exit_price - $trade->entry_price ) / $trade->entry_price * 100, 2); @endphp

														<li class="@if($buyProfits > 0) profit @else loss @endif">
															<span class="listtitle fw-bold">% Gain/Loss:</span>
															<span class="listamt">
																@if ($trade->entry_price != 0 ) {{ $buyProfits }}% @else 0% @endif
															</span>
														</li>
													@else
														@php $sellProfits = ($trade->exit_price >= $trade->entry_price) ? number_format(($trade->exit_price - $trade->entry_price) / $trade->entry_price * 100, 2) : number_format(($trade->entry_price - $trade->exit_price) / $trade->entry_price * 100, 2); @endphp

														<li class="@if($trade->exit_price >= $trade->entry_price) profit @else loss @endif">
															<span class="listtitle fw-bold">% Gain/Loss:</span>
															<span class="listamt">
																@if ($trade->entry_price != 0 ) {{ $sellProfits }}% @else 0% @endif
															</span>
														</li>
													@endif
												@endif

												{{-- Comment --}}
												<li class="mt-3">
													<span class="listtitle fw-bold">Comment:</span>
													<span class="listamt comment_line_height fw-normal">
														@if ($trade->exit_price !== null && $trade->exit_date !== null)
															{!! ($trade->close_comment) !!}
														@else
															{!! ($trade->trade_description) !!}
														@endif
													</span>
												</li>

												{{-- Chart --}}
												<li class="mt-3">
													<span class="listtitle fw-bold">Chart:</span>
													<span class="listamt fw-normal">
														@if ($trade->exit_price !== null && $trade->exit_date !== null)
															@if(!empty($trade->close_image) && file_exists(public_path($trade->close_image))) 
																yes 
															@else 
																@if($trade->close_image != '') yes  @else no @endif
															@endif
														@else
															@if(!empty($trade->chart_image) && file_exists(public_path($trade->chart_image))) 
																yes 
															@else 
																@if($trade->chart_image != '') yes  @else no @endif
															@endif
														@endif
													</span>
												</li>
											</ul>
											<div class="chart-thumbnail-block">
												<ul class="chart-thumbnail-list list-unstyled  m-0">
													<li>
														@if ($trade->exit_price !== null && $trade->exit_date !== null)
															@if(!empty($trade->close_image) && file_exists(public_path($trade->close_image))) 
																<a> 
																	<img class="chart-thumb-img comment_img" src="{{ asset($trade->close_image) }}" data-image="{{ asset($trade->close_image) }}" alt="">
																</a>
															@else 
																@if($trade->close_image != '') 
																	<a> 
																		<img class="chart-thumb-img comment_img" src="{{ $trade->close_image }}" data-image="{{ $trade->close_image }}" alt="">
																	</a>
																@endif
															@endif
														@else
															@if(!empty($trade->chart_image) && file_exists(public_path($trade->chart_image))) 
																<a> 
																	<img class="chart-thumb-img comment_img" src="{{ asset($trade->chart_image) }}" data-image="{{ asset($trade->chart_image) }}" alt="">
																</a>
															@else 
																@if($trade->chart_image != '') 
																	<a> 
																		<img class="chart-thumb-img comment_img" src="{{ $trade->chart_image }}" data-image="{{ $trade->chart_image }}" alt="">
																	</a>
																@endif
															@endif
														@endif
													</li>
												</ul>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						@endif
                    @endforeach
                </div>
                {{ $results->appends(request()->query())->links() }}
            </div>
        </div>
    </main>
    <!-- MAIN -->

	<!-- chart img MODAL start -->
    <div class="modal fade chart-img-modal" id="commentImage" tabindex="-1" aria-labelledby="chart_img_modalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-body pb-5 px-sm-5 pt-50">
					<div class="modal-body-caption">
						<img class="modalImg" src=""/>
					</div>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
        </div>
    </div>
	<!-- chart img MODAL end -->

	<div class="modal fade" id="MsgModal" tabindex="-1" aria-labelledby="MsgModalLabel">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="MsgModalLabel">Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="msgtitle"></div>
                    <div class="msgsubtitle"></div>
                    <div class="msgcontent" id="msgcontent"></div>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('page-script')
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script>
        $('.btn-close').click(function (e) {
            e.preventDefault();
            $.ajax({
                url: '{{ route("front.update-close-event") }}',
                type:'GET',
                success: function(data) {
                    console.log(data);
                },
                error: function(xhr, status, error) {
                    console.log('Error occurred: ' + error);
                }
            })
        });

        $('.comment_img, .view-comment img').on('click', function(e) {
            var comment_img = $(this).attr('src');
            $('.modalImg').attr('src', comment_img);
            $('#commentImage').modal('show');
        });

        $('#commentImage').draggable({
            handle: ".modal-header"
        });

        var search_input = $('.search_input');
        $(document).ready(function () {
           var search_input_length = search_input.val().length;
           if(search_input_length > 0){
                $('.close-icon').show();
           } else {
                $('.close-icon').hide();
           }
        });

        function delay(callback, ms) {
            var timer = 0;
            return function() {
                var context = this, args = arguments;
                clearTimeout(timer);
                timer = setTimeout(function () {
                callback.apply(context, args);
                }, ms || 0);
            };
        }

        $(document).ready(function() {
            $('.mainFeedSearch').keyup(delay(function (e) {
                $(".mainFeedSearch").submit();
            }, 500));
        });

       // JavaScript to handle the close icon click event
       $('.close-icon').click(function() {
            const input = $(this).parent().find('input');
            input.val('');
            input.focus();
            $(this).hide();
            $(".mainFeedSearch").submit();
        });

        search_input.on('input', function() {
            const icon = $(this).parent().find('.close-icon');
            if ($(this).val().length > 0) {
                icon.show();
            } else {
                icon.hide();
            }
        });

		$('.msgreadmore').click(function (e) { 
			e.preventDefault();
			$('#msgcontent').html($(this).closest('.card-body').find('.msg-content').html());
			$('#MsgModal').modal('show');
		});
    </script>
@endsection
