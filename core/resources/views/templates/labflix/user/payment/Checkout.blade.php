@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card custom--card">
                    <div class="card-header">
                        <h5>@lang('Checkout.com')</h5>
                    </div>
                    <div class="card-body">
                        <div class="card-wrapper mb-3"></div>
                        <form id="payment-form" role="form" method="{{ $data->method }}" action="{{ $data->url }}">
                            @csrf
                            <input name="track" type="hidden" value="{{ $data->track }}">
                            <div class="row gy-3">
                                <div class="col-md-6">
                                    <label class="form-label">@lang('Name on Card')</label>
                                    <div class="input-group">
                                        <input class="form-control form--control" name="name" type="text" value="{{ old('name') }}" required autocomplete="off" autofocus />
                                        <span class="input-group-text"><i class="fa fa-font"></i></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">@lang('Card Number')</label>
                                    <div class="input-group">
                                        <input class="form-control form--control" name="cardNumber" type="tel" value="{{ old('cardNumber') }}" autocomplete="off" required autofocus />
                                        <span class="input-group-text"><i class="fa fa-credit-card"></i></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">@lang('Expiration Date')</label>
                                    <input class="form-control form--control" name="cardExpiry" type="tel" value="{{ old('cardExpiry') }}" autocomplete="off" required />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">@lang('CVC Code')</label>
                                    <input class="form-control form--control" name="cardCVC" type="tel" value="{{ old('cardCVC') }}" autocomplete="off" required />
                                </div>
                            </div>
                            <br>
                            <button class="btn btn--base w-100 mt-3" type="submit"> @lang('Submit')</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ asset('assets/global/js/card.js') }}"></script>

    <script>
        (function($) {
            "use strict";
            var card = new Card({
                form: '#payment-form',
                container: '.card-wrapper',
                formSelectors: {
                    numberInput: 'input[name="cardNumber"]',
                    expiryInput: 'input[name="cardExpiry"]',
                    cvcInput: 'input[name="cardCVC"]',
                    nameInput: 'input[name="name"]'
                }
            });
        })(jQuery);
    </script>
@endpush
