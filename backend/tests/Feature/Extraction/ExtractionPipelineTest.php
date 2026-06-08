<?php

declare(strict_types=1);

namespace Tests\Feature\Extraction;

use App\Extraction\ExtractionPipeline;
use App\Extraction\ExtractionResult;
use App\Extraction\StubExtractor;
use App\Models\Document;
use App\Models\Entity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtractionPipelineTest extends TestCase
{
    use RefreshDatabase;

    private function pipeline(ExtractionResult $result): ExtractionPipeline
    {
        return new ExtractionPipeline(new StubExtractor($result));
    }

    public function test_prefills_empty_fields_and_marks_extracted_but_not_confirmed(): void
    {
        $document = Document::factory()->forEntity(Entity::factory()->create())->create([
            'issue_date' => null,
            'expiry_date' => null,
            'extracted' => false,
            'confirmed' => false,
        ]);

        $this->pipeline(new ExtractionResult(
            holderName: 'Aisha Owner',
            idNumber: '784-1990-1234567-1',
            issueDate: '2025-01-01',
            expiryDate: '2027-01-01',
        ))->run($document, 'bytes', 'application/pdf');

        $document->refresh();
        $this->assertTrue($document->extracted);
        $this->assertFalse($document->confirmed);
        $this->assertSame('2025-01-01', $document->issue_date->toDateString());
        $this->assertSame('2027-01-01', $document->expiry_date->toDateString());
        $this->assertSame('Aisha Owner', $document->extracted_data['holder_name']);

        // The safety property: extraction alone never creates a deadline.
        $this->assertSame(0, $document->deadlines()->count());
    }

    public function test_does_not_overwrite_human_entered_dates(): void
    {
        $document = Document::factory()->forEntity(Entity::factory()->create())->create([
            'issue_date' => '2024-03-03',
            'expiry_date' => null,
        ]);

        $this->pipeline(new ExtractionResult(issueDate: '2025-01-01', expiryDate: '2027-01-01'))
            ->run($document, 'bytes', 'application/pdf');

        $document->refresh();
        $this->assertSame('2024-03-03', $document->issue_date->toDateString()); // kept
        $this->assertSame('2027-01-01', $document->expiry_date->toDateString()); // filled
    }
}
