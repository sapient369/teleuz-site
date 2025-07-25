@extends($activeTemplate . 'layouts.master')
@section('content')
    <section class="pt-80 pb-80">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card custom--card">
                        <div class="card-header">
                            <h5 class="title">@lang('Voguepay')</h5>
                        </div>
                        <div class="card-body p-5">
                            <ul class="list-group text-center">
                                <li class="list-group-item d-flex justify-content-between flex-wrap gap-2">
                                    @lang('You have to pay '):
                                    <strong>{{ showAmount($deposit->final_amount, currencyFormat: false) }} {{ __($deposit->method_currency) }}</strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between flex-wrap gap-2">
                                    @lang('You will get '):
                                    <strong>{{ showAmount($deposit->amount) }}</strong>
                                </li>
                            </ul>
                            <button type="button" class="btn btn--base w-100 mt-3" id="btn-confirm">@lang('Pay Now')</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@push('script')
    <script src="//pay.voguepay.com/js/voguepay.js"></script>
    <script>
        "use strict";
        var closedFunction = function() {}
        var successFunction = function(transaction_id) {
            window.location.href = '{{ route(gatewayRedirectUrl()) }}';
        }
        var failedFunction = function(transaction_id) {
            window.location.href = '{{ route(gatewayRedirectUrl()) }}';
        }

        function pay(item, price) {
            //Initiate voguepay inline payment
            Voguepay.init({
                v_merchant_id: "{{ $data->v_merchant_id }}",
                total: price,
                notify_url: "{{ $data->notify_url }}",
                cur: "{{ $data->cur }}",
                merchant_ref: "{{ $data->merchant_ref }}",
                memo: "{{ $data->memo }}",
                recurrent: true,
                frequency: 10,
                developer_code: '60a4ecd9bbc77',
                custom: "{{ $data->custom }}",
                customer: {
                    name: 'Customer name',
                    country: 'Country',
                    address: 'Customer address',
                    city: 'Customer city',
                    state: 'Customer state',
                    zipcode: 'Customer zip/post code',
                    email: 'example@example.com',
                    phone: 'Customer phone'
                },
                closed: closedFunction,
                success: successFunction,
                failed: failedFunction
            });
        }
        (function($) {
            $('#btn-confirm').on('click', function(e) {
                e.preventDefault();
                pay('Buy', {{ $data->Buy }});
            });
        })(jQuery);
    </script>
@endpush
