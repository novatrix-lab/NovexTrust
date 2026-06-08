<?php

declare(strict_types=1);

namespace App\Livewire\Entities;

use App\Models\Entity;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    public function render(): View
    {
        return view('livewire.entities.index', [
            'entities' => Entity::query()
                ->withCount(['documents', 'persons'])
                ->orderBy('legal_name')
                ->get(),
        ]);
    }
}
