<?php

namespace Tests\Unit;

use App\Services\Whatsapp\TestMessageParser;
use PHPUnit\Framework\TestCase;

class TestMessageParserTest extends TestCase
{
    public function test_it_extracts_an_english_harvest(): void
    {
        $result = (new TestMessageParser)->parse('Harvested 40kg sukuma wiki from Plot 2');

        $this->assertSame('harvest', $result['intent']);
        $this->assertSame('en', $result['language']);
        $this->assertSame(40.0, $result['extracted_data']['quantity']);
        $this->assertSame('kg', $result['extracted_data']['unit']);
    }

    public function test_it_recognises_a_swahili_planting(): void
    {
        $result = (new TestMessageParser)->parse('Tumepanda mahindi Plot 4');

        $this->assertSame('planting', $result['intent']);
        $this->assertSame('mixed', $result['language']);
    }

    public function test_it_flags_an_operational_issue(): void
    {
        $result = (new TestMessageParser)->parse('Irrigation pipe broken Plot 1');

        $this->assertSame('issue', $result['intent']);
        $this->assertTrue($result['extracted_data']['flag']);
    }
}
