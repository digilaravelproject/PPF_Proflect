@props(['selected' => [], 'interactive' => false])
<div {{ $attributes->class(['car-selector', 'car-selector--static' => ! $interactive]) }} aria-label="Vehicle panel diagram">
    <div class="car-selector__stage">
    <img src="{{ asset('images/claim-car-top-light.png') }}" alt="Top view of a premium sedan">
    <svg viewBox="0 0 300 600" role="img">
        <title>{{ $interactive ? 'Select damaged vehicle panels' : 'Selected damaged vehicle panels' }}</title>
        <g class="car-outline"><path d="M90 42 Q150 10 210 42 L238 100 248 180 244 430 220 548 Q150 585 80 548 L56 430 52 180 62 100Z"/><path d="M92 112 Q150 82 208 112 L222 192 78 192Z"/><path d="M78 205 L222 205 228 385 72 385Z"/><path d="M78 400 L222 400 208 500 Q150 530 92 500Z"/></g>
        <g class="car-panels">
            @foreach([
                'front_bumper' => 'M90 42 Q150 10 210 42 L229 80 Q150 61 71 80Z',
                'bonnet' => 'M71 84 Q150 65 229 84 L222 192 78 192Z',
                'roof' => 'M78 205 L222 205 228 385 72 385Z',
                'left_fender' => 'M57 110 76 90 72 202 52 222 52 180Z',
                'right_fender' => 'M243 110 224 90 228 202 248 222 248 180Z',
                'left_door' => 'M53 228 72 207 72 385 55 405Z',
                'right_door' => 'M247 228 228 207 228 385 245 405Z',
                'rear_bumper' => 'M78 505 Q150 540 222 505 L211 548 Q150 580 89 548Z',
                'other' => 'M78 400 222 400 208 500 Q150 527 92 500Z',
            ] as $panel => $path)
                <path @if($interactive) data-panel-shape="{{ $panel }}" @endif class="{{ in_array($panel, $selected, true) ? 'selected' : '' }}" d="{{ $path }}"/>
            @endforeach
        </g>
        <g class="car-lines"><line x1="150" y1="205" x2="150" y2="385"/><line x1="72" y1="296" x2="228" y2="296"/></g>
    </svg>
    </div>
    <small>{{ $interactive ? 'Selected panels turn orange' : 'Orange areas were selected by the customer' }}</small>
</div>
