<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CardVehicle extends Component
{
    public function __construct(
        public int $id,
        public string $url,
        public bool $profile = false,
    ) {}

    public function render()
    {
        return view('components.card-vehicle');
    }
}
