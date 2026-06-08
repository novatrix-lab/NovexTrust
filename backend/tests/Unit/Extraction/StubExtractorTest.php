<?php

declare(strict_types=1);

namespace Tests\Unit\Extraction;

use App\Extraction\ExtractionResult;
use App\Extraction\StubExtractor;
use PHPUnit\Framework\TestCase;

class StubExtractorTest extends TestCase
{
    public function test_returns_an_empty_result_by_default(): void
    {
        $result = (new StubExtractor)->extract('bytes', 'application/pdf');

        $this->assertNull($result->issueDate);
        $this->assertNull($result->expiryDate);
        $this->assertNull($result->holderName);
    }

    public function test_returns_the_canned_result_when_provided(): void
    {
        $canned = new ExtractionResult(holderName: 'Aisha', expiryDate: '2027-01-01');
        $result = (new StubExtractor($canned))->extract('bytes', 'application/pdf');

        $this->assertSame('Aisha', $result->holderName);
        $this->assertSame('2027-01-01', $result->expiryDate);
    }
}
