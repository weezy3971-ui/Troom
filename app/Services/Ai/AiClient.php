<?php

namespace App\Services\Ai;

use App\Models\AiUsageLog;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper around the Anthropic Messages API.
 *
 * Uses Laravel's HTTP client (Guzzle) so no extra Composer dependency is
 * required. The single call is isolated in generate(); to move to the official
 * anthropic-ai/sdk later, swap only that method — the rest of the app depends
 * on this interface, not the transport.
 */
class AiClient
{
    public function __construct(
        private ?string $apiKey = null,
        private ?string $model = null,
    ) {
        $this->apiKey = $apiKey ?? config('services.anthropic.key');
        $this->model = $model ?? config('services.anthropic.model');
    }

    /** Whether an API key is present. Features degrade gracefully when false. */
    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }

    /**
     * Single-turn prompt → generated text, with usage logged.
     *
     * @param  array{model?: string, max_tokens?: int, feature?: string, user_id?: int|null}  $opts
     * @return array{text: string, input_tokens: int, output_tokens: int, model: string}
     */
    public function generate(string $system, string $userMessage, array $opts = []): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Anthropic API key is not configured. Set ANTHROPIC_API_KEY in your .env file.');
        }

        $model = $opts['model'] ?? $this->model;
        $maxTokens = $opts['max_tokens'] ?? (int) config('services.anthropic.max_tokens', 2000);
        $base = rtrim((string) config('services.anthropic.base_url', 'https://api.anthropic.com'), '/');

        $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => config('services.anthropic.version', '2023-06-01'),
                'content-type' => 'application/json',
            ])
            ->timeout((int) config('services.anthropic.timeout', 60))
            ->post("{$base}/v1/messages", [
                'model' => $model,
                'max_tokens' => $maxTokens,
                'system' => $system,
                'messages' => [
                    ['role' => 'user', 'content' => $userMessage],
                ],
            ]);

        if ($response->failed()) {
            $message = $response->json('error.message') ?? $response->body();

            throw new RuntimeException("Anthropic API error ({$response->status()}): {$message}");
        }

        $data = $response->json();

        // Safety classifiers can decline a request with HTTP 200.
        if (($data['stop_reason'] ?? null) === 'refusal') {
            throw new RuntimeException('The model declined to generate this content.');
        }

        // content is a list of blocks; concatenate the text ones.
        $text = collect($data['content'] ?? [])
            ->where('type', 'text')
            ->pluck('text')
            ->implode('');

        $usage = $data['usage'] ?? [];
        $result = [
            'text' => trim($text),
            'input_tokens' => (int) ($usage['input_tokens'] ?? 0),
            'output_tokens' => (int) ($usage['output_tokens'] ?? 0),
            'model' => $data['model'] ?? $model,
        ];

        AiUsageLog::record(
            feature: $opts['feature'] ?? 'general',
            model: $result['model'],
            inputTokens: $result['input_tokens'],
            outputTokens: $result['output_tokens'],
            userId: $opts['user_id'] ?? null,
        );

        return $result;
    }
}
