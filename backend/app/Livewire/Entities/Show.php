<?php

declare(strict_types=1);

namespace App\Livewire\Entities;

use App\Models\Entity;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public int $entityId;

    public function mount(string $id): void
    {
        // Tenant-scoped: a cross-tenant id 404s.
        $this->entityId = Entity::query()->findOrFail($id)->id;
    }

    public function render(): View
    {
        $entity = Entity::query()
            ->with(['persons', 'documents.documentType', 'documents.deadlines'])
            ->findOrFail($this->entityId);

        return view('livewire.entities.show', ['entity' => $entity]);
    }
}
