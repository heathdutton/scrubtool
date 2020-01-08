@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Update Your Plan') }}</div>

                    <div class="card-body">
                        <form method="POST" action="{{ route('plan') }}">
                            @csrf

                            <input id="card-holder-name" type="text">

                            <input type="hidden" name="userId" value="{{ $user->id }}">

                            <!-- Stripe Elements Placeholder -->
                            <div id="card-element"></div>

                            <button id="card-button" data-secret="{{ $intent->client_secret }}">
                                Update Payment Method
                            </button>

                            <script src="https://js.stripe.com/v3/"></script>

                            <script>
                                const stripe = Stripe('stripe-public-key');

                                const elements = stripe.elements();
                                const cardElement = elements.create('card');

                                cardElement.mount('#card-element');

                                //
                                const cardHolderName = document.getElementById('card-holder-name');
                                const cardButton = document.getElementById('card-button');

                                cardButton.addEventListener('click', async (e) => {
                                    const { paymentMethod, error } = await stripe.createPaymentMethod(
                                        'card', cardElement, {
                                            billing_details: { name: cardHolderName.value }
                                        }
                                    );

                                    if (error) {
                                        // Display "error.message" to the user...
                                    } else {
                                        // The card has been verified successfully...
                                    }
                                });
                            </script>

{{--                            <div class="form-group row mb-0">--}}
{{--                                <div class="col-md-6 offset-md-4">--}}
{{--                                    <button type="submit" class="btn btn-primary">--}}
{{--                                        {{ __('Update') }}--}}
{{--                                    </button>--}}
{{--                                </div>--}}
{{--                            </div>--}}
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
