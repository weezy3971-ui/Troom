<?php

namespace App\Services\Whatsapp;

use Illuminate\Support\Str;

/** Fast, deterministic pilot parser. Claude can replace it behind this boundary. */
class TestMessageParser
{
    public function parse(string $body): array
    {
        $text = Str::lower(trim($body));
        $swahili = preg_match('/\b(tumepanda|nimepanda|tumevuna|nimevuna|imeharibika|wadudu|ugonjwa|maji|shamba)\b/u', $text);
        $english = preg_match('/\b(harvest|planted|planting|broken|pest|disease|irrigation|water|plot)\b/u', $text);

        $intent = match (true) {
            (bool) preg_match('/\b(harvest(?:ed)?|tumevuna|nimevuna|mavuno)\b/u', $text) => 'harvest',
            (bool) preg_match('/\b(plant(?:ed|ing)?|sow(?:n|ed)?|tumepanda|nimepanda)\b/u', $text) => 'planting',
            (bool) preg_match('/\b(broken|damage|pest|disease|irrigation|leak|wadudu|ugonjwa|imeharibika|hakuna maji)\b/u', $text) => 'issue',
            default => 'other',
        };

        preg_match('/(?<quantity>\d+(?:\.\d+)?)\s*(?<unit>kg|kgs|kilograms?|g|grams?|bags?|crates?|trays?)\b/i', $body, $quantity);
        preg_match('/\b(?:plot|block|greenhouse|shamba)\s*[-#:]?\s*(?<area>[\pL\d][\pL\d _-]*)/iu', $body, $area);

        $data = array_filter([
            'quantity' => isset($quantity['quantity']) ? (float) $quantity['quantity'] : null,
            'unit' => isset($quantity['unit']) ? Str::lower($quantity['unit']) : null,
            'area_text' => isset($area['area']) ? trim($area['area']) : null,
            'flag' => $intent === 'issue',
        ], fn ($value) => $value !== null);

        $requiredFound = $intent === 'issue'
            || ($intent === 'harvest' && isset($data['quantity']))
            || $intent === 'planting';

        return [
            'language' => $swahili && $english ? 'mixed' : ($swahili ? 'sw' : 'en'),
            'intent' => $intent,
            'extracted_data' => $data,
            'confidence' => $intent === 'other' ? 0.35 : ($requiredFound ? 0.82 : 0.60),
        ];
    }
}
