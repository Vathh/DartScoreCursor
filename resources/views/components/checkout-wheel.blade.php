@props([
    'hits' => [],
    'demo' => false,
])

@php
    $svg = \App\Support\Checkout\CheckoutWheelSvg::inline();
@endphp

<div
    {{ $attributes->class('checkout-wheel') }}
    data-checkout-wheel
    @if($demo) data-checkout-wheel-demo="1" @endif
    data-checkout-hits='@json($hits)'
>
    <div class="checkout-wheel__stage rounded-xl overflow-visible px-4 py-8 sm:px-8">
        {!! $svg !!}
    </div>
</div>
