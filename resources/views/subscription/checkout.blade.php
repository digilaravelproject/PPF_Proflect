<!DOCTYPE html><html lang="en"><head><link rel="icon" type="image/png" href="{{ asset('favicon.png') }}"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>Complete purchase · Proflect</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="checkout-body"><header class="simple-header"><x-brand/><span>Secure checkout</span></header><main class="checkout-shell">
<div class="stepper"><span class="done"><i>✓</i>Choose a plan</span><b></b><span class="active"><i>2</i>Checkout</span><b></b><span><i>3</i>Protected</span></div>
<a href="{{ route('subscription.index') }}" class="back-link">← Back to plans</a><div class="checkout-grid"><section><span class="eyebrow">COMPLETE YOUR PURCHASE</span><h1>Protection is one step away.</h1><p class="checkout-lead">Review your plan and pay securely with Stripe.</p>
<div class="pay-method"><div class="pay-method__radio">●</div><div><b>Stripe Secure Checkout</b><p>Credit and debit cards supported by Stripe</p></div><strong>Stripe</strong></div>
<div class="secure-box"><span>♙</span><p><b>Safe and encrypted payment</b><br>Payment details are collected directly by Stripe and never stored by Proflect.</p></div>
@if(request()->boolean('cancelled'))<div class="alert payment-error">Payment was cancelled. You have not been charged.</div>@endif
@error('payment')<div class="alert payment-error">{{ $message }}</div>@enderror
<div id="payment-error" class="alert payment-error" hidden></div>
<button class="button button--primary pay-now" data-payment-button data-plan-id="{{ $plan->id }}" data-order-url="{{ route('payments.order') }}">Pay {{ $plan->formatted_price }} securely <span>→</span></button>
<p class="razorpay-config-note">You will continue to Stripe's secure hosted checkout.</p></section>
<aside class="order-card"><span class="eyebrow">ORDER SUMMARY</span><div class="order-plan"><span class="plan-badge">{{ strtoupper(substr($plan->name,0,1)) }}</span><div><h3>{{ $plan->name }}</h3><p>{{ $plan->duration_label }} · {{ rtrim(rtrim($plan->coverage_sqm,'0'),'.') }} m² coverage</p></div></div><ul>@foreach($plan->features ?? [] as $feature)<li>✓ {{ $feature }}</li>@endforeach</ul><div class="price-line"><span>Plan price</span><b>{{ $plan->formatted_price }}</b></div><div class="price-line"><span>GST</span><b>Included</b></div><div class="price-line total"><span>Total payable</span><b>{{ $plan->formatted_price }}</b></div><small>One-time payment. No auto-renewal.</small></aside></div></main></body></html>

\n
