<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Imports\EntityCsvImporter;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class ImportEntities extends Component
{
    use WithFileUploads;

    public ?TemporaryUploadedFile $file = null;

    /** @var array{imported: int, skipped: int, errors: list<string>}|null */
    public ?array $result = null;

    public function import(EntityCsvImporter $importer): void
    {
        $this->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $this->result = $importer->import((string) file_get_contents($this->file->getRealPath()));
        $this->reset('file');
    }

    public function render(): View
    {
        return view('livewire.import-entities');
    }
}
