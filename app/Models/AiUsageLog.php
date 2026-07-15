<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per AI API call — the basis for spend visibility and a monthly cap.
 */
class AiUsageLog extends Model
{
    protected $fillable = [
        'feature',
        'model',
        'input_tokens',
        'output_tokens',
        'estimated_cost_usd',
        'user_id',
    ];

    protected $casts = [
        'estimated_cost_usd' => 'decimal:6',
    ];

    /** USD per 1M tokens, [input, output]. Matched by model-name prefix. */
    private const PRICING = [
        'claude-haiku-4-5' => [1.00, 5.00],
        'claude-sonnet-5'  => [3.00, 15.00],
        'claude-opus-4-8'  => [5.00, 25.00],
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function estimateCost(string $model, int $inputTokens, int $outputTokens): float
    {
        $match = null;
        foreach (array_keys(self::PRICING) as $prefix) {
            if (str_starts_with($model, $prefix)) {
                $match = $prefix;
                break;
            }
        }

        [$inPrice, $outPrice] = self::PRICING[$match] ?? self::PRICING['claude-haiku-4-5'];

        return ($inputTokens / 1_000_000) * $inPrice + ($outputTokens / 1_000_000) * $outPrice;
    }

    public static function record(string $feature, string $model, int $inputTokens, int $outputTokens, ?int $userId = null): self
    {
        return self::create([
            'feature' => $feature,
            'model' => $model,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'estimated_cost_usd' => self::estimateCost($model, $inputTokens, $outputTokens),
            'user_id' => $userId,
        ]);
    }
}
