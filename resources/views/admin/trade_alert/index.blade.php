@extends('layouts.master')

@section('title', 'Trade Alerts')

@section('page-style')
    <link rel="stylesheet" type="text/css" href="{{asset('app-assets/vendors/css/pickers/flatpickr/flatpickr.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/editors/quill/katex.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/editors/quill/monokai-sublime.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/editors/quill/quill.snow.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/editors/quill/quill.bubble.css') }}">
    <link rel="stylesheet" type="text/css" href="{{asset('app-assets/css/plugins/forms/form-quill-editor.css')}}">
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h4 class="card-title">Trade Alerts</h4>
        <a href="{{ route('trades.create') }}" class="btn btn-primary">Add Trade</a>
    </div>
   
    <div class="table-responsive">
        <table class="table trade-alert-table">
            <thead class="table-light">
                <tr>
                    <th></th>
                    <th>Trade</th>
                    <th>Trade Type</th>
                    <th>Direction</th>
                    <th>Trade Date</th>
                    <th>Average Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($parentTrades as $parentTrade)
                    <tr style="font-weight:bold">
                        <td class="expand-icon text-primary" style="width: 5%">
                            @if($parentTrade->tradeDetail !== null && $parentTrade->tradeDetail->count())
                                <i class="expand-toggle fa-solid fa-chevron-right" style="cursor: pointer;"></i>
                            @endif
                        </td>
                        <td class="parent-trade text-primary" data-trade-id="{{ $parentTrade->id }}"  style="width: 15%">                                                    
                            @if($parentTrade->trade_type === 'stock')
                                {{ $parentTrade->trade_symbol }}
                            @elseif ($parentTrade->trade_type === 'option')
                                {{ $parentTrade->trade_symbol .''. \Carbon\Carbon::parse($parentTrade->expiration_date)->format('ymd').''.ucfirst(substr($parentTrade->trade_option, 0, 1)).''.number_format($parentTrade->strike_price, '0')}}
                            @endif  
                        </td>                       
                        <td style="width:10%">{{ $parentTrade->trade_type }}</td>
                        <td style="width: 15%">
                            @if($parentTrade->trade_direction == 'buy' )     
                                Long
                            @else
                                Short
                            @endif
                        </td>
                        <td style="width: 15%">{{ \Carbon\Carbon::parse($parentTrade->entry_date)->format('m/d/Y') }}</td>
                        <td style="width: 20%" class="average-price">
                            @if($parentTrade->trade_direction == 'sell')
                                <span class="price">(${{ $parentTrade->entry_price }})</span>
                            @else
                                <span class="price">${{ $parentTrade->entry_price }}</span>
                            @endif                            
                            <span class="size">({{ rtrim(rtrim(number_format($parentTrade->position_size, 1), '0'), '.') }}%)</span>
                        </td>
                        <td style="width: 20%">
                            <a href="#" class="btn btn-success btnClose"
                                data-id="{{ $parentTrade->id }}" 
                                data-type = "{{ $parentTrade->trade_type }}"
                                data-direction="{{ $parentTrade->trade_direction }}"
                                data-position = "{{ $parentTrade->position_size }}"
                                data-symbol="{{$parentTrade->trade_symbol}}" 
                                data-strikeprice = "{{$parentTrade->strike_price}}" 
                                data-option="{{$parentTrade->trade_option}}" 
                                data-entryprice="{{$parentTrade->entry_price}}" 
                                data-currentprice="{{$parentTrade->current_price}}" 
                                data-shareqty="{{$parentTrade->share_qty}}"
                                data-shareinamount="{{$parentTrade->share_in_amount}}"
                                data-expirationdate="{{\Carbon\Carbon::parse($parentTrade->expiration_date)->format('Ymd')}}" >
                                Close
                            </a>
                            <a href="#" class="btn btn-success btnAdd"  
                                data-id="{{ $parentTrade->id }}" 
                                data-type = "{{ $parentTrade->trade_type }}" 
                                data-direction="{{ $parentTrade->trade_direction }}"
                                data-position = "{{ $parentTrade->position_size }}"
                                data-symbol="{{$parentTrade->trade_symbol}}" 
                                data-strikeprice = "{{$parentTrade->strike_price}}" 
                                data-option="{{$parentTrade->trade_option}}" 
                                data-entryprice="{{$parentTrade->entry_price}}" 
                                data-expirationdate="{{\Carbon\Carbon::parse($parentTrade->expiration_date)->format('Ymd')}}"
                               >
                              Add
                            </a>
                        </td>
                    </tr>
                    @if($parentTrade->tradeDetail !== null && $parentTrade->tradeDetail->count())
                        @php
                            //parent row's data
                            $totalPrice = $parentTrade->entry_price * $parentTrade->position_size / 100;
                            $totalPercentage = $parentTrade->position_size / 100;  
                            $averagePrice = 0;
                        @endphp
                        <tr class="child-trade child-trade-{{ $parentTrade->id }}" style="display: none;">
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>{{ \Carbon\Carbon::parse($parentTrade->entry_date)->format('m/d/Y') }}</td>
                            <td>
                                <span>${{ $parentTrade->entry_price }}</span>
                                <span>({{ rtrim(rtrim(number_format($parentTrade->position_size, 1), '0'), '.') }}%)</span>
                            </td>
                            <td></td>
                        </tr>
                        @foreach($parentTrade->tradeDetail as $childTrade)
                            @php
                                $totalPrice += $childTrade->entry_price * $childTrade->position_size /100;
                                $totalPercentage += $childTrade->position_size / 100;
                            @endphp
                            <tr class="child-trade child-trade-{{ $parentTrade->id }}" style="display: none;">
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td>{{ \Carbon\Carbon::parse($childTrade->entry_date)->format('m/d/Y') }}</td>
                                <td>
                                    <span>${{ $childTrade->entry_price }}</span>
                                    <span>({{ rtrim(rtrim(number_format($childTrade->position_size, 1), '0'), '.') }}%)</span>
                                </td>
                                <td></td>
                            </tr>
                        @endforeach
                        @php
                            $averagePrice = $totalPrice / $totalPercentage;
                        @endphp
                        <script>
                            $(document).ready(function() {
                                var averagePrice = {{ $averagePrice }};
                                var totalPercentage = {{$totalPercentage}}

                                $('.parent-trade[data-trade-id="{{ $parentTrade->id }}"]').closest('tr').find('.average-price').
                                find('.price').text('$'+parseFloat(averagePrice).toFixed(2));

                                $('.parent-trade[data-trade-id="{{ $parentTrade->id }}"]').closest('tr').find('.average-price')
                                .find('.size').text(' ('+parseFloat(totalPercentage * 100).toFixed(2)+'%)');

                                // Show/hide child rows on expand icon click
                                $(".expand-toggle").off('click').on('click', function() {
                                    // console.log('toggle icon is clicked');
                                    var parentRow = $(this).closest('tr');  
                                    var tradeId = parentRow.find('.parent-trade').data('trade-id');
                                    var childRows = $('.child-trade-' + tradeId);
                                    if (parentRow.hasClass('expanded')) {
                                        parentRow.removeClass('expanded');
                                        childRows.hide();
                                        $(this).removeClass('fa-chevron-down').addClass('fa-chevron-right');
                                    } else {
                                        parentRow.addClass('expanded');
                                        childRows.show();
                                        $(this).removeClass('fa-chevron-right').addClass('fa-chevron-down');
                                    }    
                                });
                            });
                        </script>
                    @endif
                @endforeach
            </tbody>
        </table>
       
        {{ $parentTrades->appends(request()->query())->links() }}
    </div>

     <!-- Add Trade Modal -->
     <div class="modal fade" id="addTrade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-add-trade">
            <div class="modal-content">
                <div class="modal-header bg-transparent">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pb-5 px-sm-5 pt-50">
                    <div class="text-center mb-2">
                        <h1 class="modal_trade_title">Add Trade</h1>
                        <h2 class="mb-1 tradeAddTitle" style="font-weight: bold;"></h2>
                    </div>
                    
                    <form id="addTradeForm" method="post" action="{{route('admin.trade-add')}}" class="row gy-1 pt-75" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="addFormID" id="addFormID" value="" />          
                        <input type="hidden" name="addTradeSymbol" id="addTradeSymbol" value="" />                        
                        <input type="hidden" name="addTradeType" id="addTradeType" value="" />                        
                        <input type="hidden" name="addTradeOption" id="addTradeOption" value="" />
                        <input type="hidden" name="addTradeDirection" id="addTradeDirection" value="" />
                        <input type="hidden" name="addTradeStrikePrice" id="addTradeStrikePrice" value="" />
                        <input type="hidden" name="addExpirationDate" id="addExpirationDate" value="" />
                        
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="AddEntryDate">Entry Date</label>
                            <input type="text" id="addEntryDate" name="addEntryDate" class="form-control picker" value="{{old('addEntryDate')}}" />
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="addBuyPrice">Buy Price</label>
                            <input type="text" id="addBuyPrice" name="addBuyPrice" class="form-control numeral-mask" value="{{old('addBuyPrice')}}" />
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label" for="itemname">Position Size(%)</label>
                            <select class="form-select" name="addPositionSize" id="addPositionSize">
                                @for ($i = 0.5; $i <= 10; $i += 0.5)
                                    <option value="{{$i}}" {{ old('addPositionSize') == $i ? 'selected' : '' }}>{{$i}}</option>
                                @endfor                                                
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label" for="addStopPrice">Stop Price</label>
                            <input type="text" id="addStopPrice" name="addStopPrice" class="form-control"  value="{{old('addStopPrice')}}"  />
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label" for="AddBuyPrice">Target Price</label>
                            <input type="text" id="addTargetPrice" name="addTargetPrice" class="form-control numeral-mask" value="{{old('addTargetPrice')}}" />
                        </div>

                        <div class="col-12 col-md-12 mb-5">
                            <label class="form-label" for="itemname">Comment on Trade</label>
                            <div class="quill-add-toolbar">
                                <span class="ql-formats">
                                    <select class="ql-header">
                                        <option value="1">Heading</option>
                                        <option value="2">Subheading</option>
                                        <option selected>Normal</option>
                                    </select>
                                    <select class="ql-font">
                                        <option selected>Sailec Light</option>
                                        <option value="sofia">Sofia Pro</option>
                                        <option value="slabo">Slabo 27px</option>
                                        <option value="roboto">Roboto Slab</option>
                                        <option value="inconsolata">Inconsolata</option>
                                        <option value="ubuntu">Ubuntu Mono</option>
                                    </select>
                                </span>
                                <span class="ql-formats">
                                    <button class="ql-bold"></button>
                                    <button class="ql-italic"></button>
                                    <button class="ql-underline"></button>
                                </span>
                                <span class="ql-formats">
                                    <button class="ql-list" value="ordered"></button>
                                    <button class="ql-list" value="bullet"></button>
                                </span>
                                <span class="ql-formats">
                                    <button class="ql-link"></button>
                                    <button class="ql-image"></button>
                                    <button class="ql-video"></button>
                                </span>
                                <span class="ql-formats">
                                    <button class="ql-formula"></button>
                                    <button class="ql-code-block"></button>
                                </span>
                                <span class="ql-formats">
                                    <button class="ql-clean"></button>
                                </span>
                            </div>
                            <div class="quill_add_editor">
                                                
                            </div>        
                            <input type="hidden" id="quill_add_html" name="quill_add_html">
                        </div>

                        {{-- <div class="col-12 col-md-12 addImgRow">
                            <label for="customFile" class="form-label">Chart Image</label>
                            <input class="form-control" type="file" id="addImage" name="addImage" />
                        </div> --}}
                       
                        <div class="col-12 text-center mt-2 pt-50">
                            <button type="submit" class="btn btn-primary me-1">Submit</button>
                            <button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="modal" aria-label="Close">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!--/ Add Trade Modal -->


    <!-- Close Trade Modal -->
    <div class="modal fade" id="closeTrade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-close-trade">
            <div class="modal-content">
                <div class="modal-header bg-transparent">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pb-5 px-sm-5 pt-50">
                    <div class="text-center mb-2">
                        <h1 class="modal_trade_title">Close Trade</h1>
                        <h2 class="mb-1 tradeCloseTitle" style="font-weight: bold;"></h2>
                    </div>
                    
                    <form id="closeTradeForm" method="post" action="{{route('admin.trade-close')}}" class="row gy-1 pt-75" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="closeFormID" id="closeFormID" value="" />
                        <input type="hidden" name="closeTradeType" id="closeTradeType" value="" />
                        <input type="hidden" name="closeTradeSymbol" id="closeTradeSymbol" value="" />
                        <input type="hidden" name="closeTradeDirection" id="closeTradeDirection" value="" />
                        <input type="hidden" name="closeTradeStrikePrice" id="closeTradeStrikePrice" value="" />
                        <input type="hidden" name="closeTradeEntryPrice" id="closeTradeEntryPrice" value="" />
                        <input type="hidden" name="closeTradePositionSize" id="closeTradePositionSize" value="" />
                        <input type="hidden" name="closeTradeOption" id="closeTradeOption" value="" />
                        <input type="hidden" name="closeTradeShareQTY" id="closeTradeShareQTY" value="" />
                        <input type="hidden" name="closeTradeShareInAmount" id="closeTradeShareInAmount" value="" />
                        <input type="hidden" name="closeOptionExpirationDate" id="closeOptionExpirationDate" value="" />

                        <div class="col-12 col-md-4">
                            <label class="form-label" for="closeExitDate">Exit Date</label>
                            <input type="text" class="form-control" value="<?php echo date('Y-m-d'); ?>" disabled />
                            <input type="hidden" id="closeExitDate" name="closeExitDate"  value="<?php echo date('Y-m-d'); ?>" />
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label" for="closeExitPrice">Exit Price</label>
                            <input type="text" id="closeExitPrice" name="closeExitPrice" class="form-control numeral-mask" value="{{old('closeExitPrice')}}" />
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label" for="itemname">Position Size(%)</label>
                            <input type="text" id="PositionSize" name="PositionSize" class="form-control" value="All" disabled />
                        </div>

                        <div class="col-12 col-md-12 mb-5">
                            <label class="form-label" for="itemname">Comment on Trade</label>
                            <div class="quill-close-toolbar">
                                <span class="ql-formats">
                                    <select class="ql-header">
                                        <option value="1">Heading</option>
                                        <option value="2">Subheading</option>
                                        <option selected>Normal</option>
                                    </select>
                                    <select class="ql-font">
                                        <option selected>Sailec Light</option>
                                        <option value="sofia">Sofia Pro</option>
                                        <option value="slabo">Slabo 27px</option>
                                        <option value="roboto">Roboto Slab</option>
                                        <option value="inconsolata">Inconsolata</option>
                                        <option value="ubuntu">Ubuntu Mono</option>
                                    </select>
                                </span>
                                <span class="ql-formats">
                                    <button class="ql-bold"></button>
                                    <button class="ql-italic"></button>
                                    <button class="ql-underline"></button>
                                </span>
                                <span class="ql-formats">
                                    <button class="ql-list" value="ordered"></button>
                                    <button class="ql-list" value="bullet"></button>
                                </span>
                                <span class="ql-formats">
                                    <button class="ql-link"></button>
                                    <button class="ql-image"></button>
                                    <button class="ql-video"></button>
                                </span>
                                <span class="ql-formats">
                                    <button class="ql-formula"></button>
                                    <button class="ql-code-block"></button>
                                </span>
                                <span class="ql-formats">
                                    <button class="ql-clean"></button>
                                </span>
                            </div>
                            <div class="quill_close_editor">
                                                
                            </div>        
                            <input type="hidden" id="quill_close_html" name="quill_close_html">
                        </div>

                        <div class="col-12 col-md-12 closeImgRow">
                            <label for="customFile" class="form-label">Chart Image</label>
                            <input class="form-control" type="file" id="closeImage" name="closeImage" />
                        </div>
                       
                        <div class="col-12 text-center mt-2 pt-50">
                            <button type="submit" class="btn btn-primary me-1">Submit</button>
                            <button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="modal" aria-label="Close">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!--/ Edit Trade Modal -->
</div>

@endsection

@section('page-script')
<script src="https://kit.fontawesome.com/8c0eabb613.js" crossorigin="anonymous"></script>
<script src="{{asset('app-assets/vendors/js/pickers/flatpickr/flatpickr.min.js')}}"></script>
<script src="{{asset('app-assets/vendors/js/forms/cleave/cleave.min.js')}}"></script>
<script src="{{asset('app-assets/vendors/js/forms/cleave/addons/cleave-phone.us.js')}}"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script src="{{asset('app-assets/vendors/js/editors/quill/katex.min.js')}}"></script>
<script src="{{asset('app-assets/vendors/js/editors/quill/highlight.min.js')}}"></script>
<script src="{{asset('app-assets/vendors/js/editors/quill/quill.min.js')}}"></script>
<script>    
    $(document).ready(function () {
        var addTradeForm = $('#addTradeForm');
        var closeTradeForm = $('#closeTradeForm');

        $('body').on('click', '.btnAdd', function(e) {
            e.preventDefault();
            var space = ' ';
            var id = $(this).data('id');
            var trade_type = $(this).data('type'); 
            var direction = $(this).data('direction').toUpperCase();
            var symbol = $(this).data('symbol');
            var strikeprice = $(this).data('strikeprice');            
            var option = $(this).data('option');
            var entryprice = $(this).closest('tr').find('.average-price').find('.price').text();
            if(entryprice == undefined)
                entryprice = $(this).data('entryprice');
            var expirationdate = $(this).data('expirationdate');  

            if(trade_type == 'option'){
                var tradeTitle = direction+space+symbol+expirationdate.toString().substring(2,8)+option.substring(0,1).toUpperCase()+strikeprice;
            }else{
                var tradeTitle = direction+space+symbol;
            }
            // console.log(tradeTitle);
            
            $('#addTrade').modal('show');
            $('.tradeAddTitle').text(tradeTitle);
            $('#addFormID').val(id);
            $('#addTradeType').val(trade_type);
            $('#addTradeSymbol').val(symbol);
            $('#addTradeOption').val(option);
            $('#addTradeDirection').val(direction);
            $('#addTradeStrikePrice').val(strikeprice);   
            $('#addExpirationDate').val(expirationdate);                 
        });

        var quill_add = new Quill('.quill_add_editor', {
            modules: {
                toolbar: '.quill-add-toolbar'
            },
            theme: 'snow'
        });

        quill_add.on('text-change', function(delta, oldDelta, source) {
            document.getElementById("quill_add_html").value = quill_add.root.innerHTML; 
        });

        $('#addTrade').draggable({
            handle: ".modal-header" 
        });

        $('body').on('click', '.btnClose', function(e) {
            e.preventDefault();  
            var space = ' ';
            var id = $(this).data('id');
            var type = $(this).data('type');
            var direction = $(this).data('direction');
             //if there is a total position size
            var position_size = $(this).closest('tr').find('.average-price').find('.size').text().replace(/[()%]/g, '');
            if(position_size == undefined)
                position_size = parseFloat($(this).data('position').replace(/[()%]/g, ''));
            
            var entryprice = $(this).closest('tr').find('.average-price').find('.price').text();
            if(entryprice == undefined)
                entryprice = $(this).data('entryprice');

            var symbol = $(this).data('symbol');
			var currentprice = $(this).data('currentprice');
			
            var strikeprice = $(this).data('strikeprice');
            var option = $(this).data('option');

            var shareqty = $(this).data('shareqty');
            var shareinamount = $(this).data('shareinamount');
          
            var expirationdate = $(this).data('expirationdate');
            if(direction =='sell') direction = 'Buy';
            else direction = 'Sell'

            if(type == 'option'){
                var tradeTitle = direction.toUpperCase()+space+symbol+space+position_size.replace(/[\$\(\)]/g, '')+'%'+space+expirationdate.toString().substring(2,8)+space+option.substring(0,1).toUpperCase()+space+strikeprice;
            }else{
                var tradeTitle = direction.toUpperCase()+space+symbol+space+position_size+'%';
            }

			$('.modal_trade_title').html('Close Trade (<span class="text-success">$'+ currentprice +'</span>)');
           
            $('.tradeCloseTitle').text(tradeTitle);
            $('#closeFormID').val(id);
            $('#closeTradeType').val(type);
            $('#closeTradeSymbol').val(symbol);
            $('#closeTradeDirection').val(direction);
            $('#closeTradeEntryPrice').val(entryprice);  //average price
            $('#closeTradePositionSize').val(position_size);
            $('#closeTradeStrikePrice').val(strikeprice);
            $('#closeTradeOption').val(option);
            $('#closeOptionExpirationDate').val(expirationdate);  
            $('#closeExitPrice').val(currentprice);
            $('#closeTradeShareQTY').val(shareqty);
            $('#closeTradeShareInAmount').val(shareinamount);

            var quill_close = new Quill('.quill_close_editor', {
                modules: {
                toolbar: '.quill-close-toolbar'
            },
                theme: 'snow'
            });

            quill_close.on('text-change', function(delta, oldDelta, source) {
                document.getElementById("quill_close_html").value = quill_close.root.innerHTML; 
            });

            $('#closeTrade').modal('show');
        });

        $('#closeTrade').draggable({
            handle: ".modal-header" 
        });

        $.validator.addMethod('filesize', function(value, element, param) {
            // param = size (in bytes) 
            // element = element to validate (<input>)
            // value = value of the element (file name)
            return this.optional(element) || (element.files[0].size <= param) 
        });

        $.validator.addMethod("extension", function(value, element, param) {
            param = typeof param === "string" ? param.replace(/,/g, '|') : "png|jpe?g|gif";
            return this.optional(element) || value.match(new RegExp(".(" + param + ")$", "i"));
        });
        
        addTradeForm.validate({
            rules: {
                'addEntryDate': {
                    required: true
                },
                'addBuyPrice': {
                    required: true
                },
                'addStopPrice': {
                    required: true
                },
                'addTargetPrice': {
                    required: true
                },
                'addImage':{
                    extension: "png|jpg|jpeg",
                    filesize: 1048576  //1MB
                }
            },
            messages: {
                field: {
                    extension: "Please upload file in .jpg, .png format",
                    filesize: "File must be less than 1MB"
                }
            }
        });

        closeTradeForm.validate({
            rules: {
                'closeExitDate': {
                    required: true
                },
                'closeExitPrice': {
                    required: true
                },               
                'closeImage':{
                    extension: "png|jpg|jpeg",
                    filesize: 1048576  //1MB
                }
            },
            messages: {
                field: {
                    extension: "Please upload file in .jpg, .png format",
                    filesize: "File must be less than 1MB"
                }
            }
        });

        var picker = $('.picker');
        picker.flatpickr({
            allowInput: true,
            // dateFormat: "d-m-Y",  // Date format set to day-month-year
            onReady: function (selectedDates, dateStr, instance) {
                if (instance.isMobile) {
                    $(instance.mobileInput).attr('step', null);
                }
            }
        });

        var fields = ['#addBuyPrice', '#addTargetPrice', '#closeExitPrice'];
        var options = {
            numeral: true,
            numeralThousandsGroupStyle: 'thousand'
        };

        fields.forEach(element => {
            new Cleave(element, options);
        });
    });    
</script>
@endsection
