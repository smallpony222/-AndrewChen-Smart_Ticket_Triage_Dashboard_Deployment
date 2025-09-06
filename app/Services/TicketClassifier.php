<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class TicketClassifier
{
    private const RATE_LIMIT_KEY = 'openai_classification_calls';
    private const SYSTEM_PROMPT = 'You are an AI assistant that classifies support tickets. Analyze the ticket subject and body, then respond with a JSON object containing exactly three keys: "category" (one of: technical, billing, general, bug_report, feature_request, account, other), "explanation" (a brief explanation of why this category was chosen), and "confidence" (a float between 0 and 1 indicating your confidence in this classification).';

    /**
     * Classify a ticket using OpenAI or return dummy data if disabled.
     */
    public function classify(Ticket $ticket): array
    {
        if (!$this->canMakeApiCall()) {
            Log::warning('OpenAI rate limit exceeded for ticket classification');
            throw new \Exception('Rate limit exceeded. Please try again later.');
        }

        if (!config('openai.classify_enabled', true)) {
            return $this->getDummyClassification();
        }

        try {
            $this->incrementRateLimit();
            
            $response = OpenAI::chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => self::SYSTEM_PROMPT,
                    ],
                    [
                        'role' => 'user',
                        'content' => "Subject: {$ticket->subject}\n\nBody: {$ticket->body}",
                    ],
                ],
                'max_tokens' => 200,
                'temperature' => 0.3,
            ]);

            $content = $response->choices[0]->message->content;
            $classification = json_decode($content, true);

            if (!$this->isValidClassification($classification)) {
                Log::error('Invalid classification response from OpenAI', ['response' => $content]);
                return $this->getDummyClassification();
            }

            return $classification;
        } catch (\Exception $e) {
            Log::error('OpenAI classification failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            return $this->getDummyClassification();
        }
    }

    /**
     * Check if we can make an API call based on rate limiting.
     */
    private function canMakeApiCall(): bool
    {
        $limit = config('openai.rate_limit_per_minute', 10);
        $calls = Cache::get(self::RATE_LIMIT_KEY, 0);
        
        return $calls < $limit;
    }

    /**
     * Increment the rate limit counter.
     */
    private function incrementRateLimit(): void
    {
        $key = self::RATE_LIMIT_KEY;
        $calls = Cache::get($key, 0);
        Cache::put($key, $calls + 1, now()->addMinute());
    }

    /**
     * Validate the classification response structure.
     */
    private function isValidClassification(mixed $classification): bool
    {
        if (!is_array($classification)) {
            return false;
        }

        $requiredKeys = ['category', 'explanation', 'confidence'];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $classification)) {
                return false;
            }
        }

        // Validate category
        if (!in_array($classification['category'], Ticket::CATEGORIES)) {
            return false;
        }

        // Validate confidence
        if (!is_numeric($classification['confidence']) || 
            $classification['confidence'] < 0 || 
            $classification['confidence'] > 1) {
            return false;
        }

        return true;
    }

    /**
     * Get dummy classification when OpenAI is disabled or fails.
     */
    private function getDummyClassification(): array
    {
        $categories = Ticket::CATEGORIES;
        $randomCategory = $categories[array_rand($categories)];

        return [
            'category' => $randomCategory,
            'explanation' => 'This is a dummy classification generated when OpenAI is disabled or unavailable.',
            'confidence' => round(mt_rand(50, 95) / 100, 2),
        ];
    }
}
