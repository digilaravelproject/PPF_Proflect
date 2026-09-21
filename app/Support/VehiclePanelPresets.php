<?php

namespace App\Support;

class VehiclePanelPresets
{
    public const NAMES = [
        'car' => ['Front Bumper', 'Bonnet', 'Roof', 'Left Fender', 'Right Fender', 'Left Door', 'Right Door', 'Rear Bumper', 'Other'],
        'van' => ['Front Bumper', 'Bonnet', 'Roof', 'Left Fender', 'Right Fender', 'Left Door', 'Right Door', 'Rear Doors', 'Rear Bumper', 'Other'],
        'truck' => ['Front Bumper', 'Bonnet', 'Cab Roof', 'Left Fender', 'Right Fender', 'Left Door', 'Right Door', 'Rear Panel', 'Other'],
        'bus' => ['Front Bumper', 'Front Panel', 'Roof', 'Left Side Panel', 'Right Side Panel', 'Passenger Door', 'Rear Panel', 'Rear Bumper', 'Other'],
        'motorcycle' => ['Front Fairing', 'Fuel Tank', 'Left Fairing', 'Right Fairing', 'Front Fender', 'Rear Fender', 'Tail Panel', 'Other'],
        'moped' => ['Front Apron', 'Leg Shield', 'Left Side Panel', 'Right Side Panel', 'Front Fender', 'Rear Fender', 'Tail Panel', 'Other'],
    ];
}
