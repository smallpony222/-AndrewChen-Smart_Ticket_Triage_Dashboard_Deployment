<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Ticket;
use App\Services\TicketClassifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ClassifyTicket implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Ticket $ticket,
        public bool $preserveManualCategory = true
    ) {}

    /**
     * Execute the job.
     */
    public function handle(TicketClassifier $classifier): void
    {
        try {
            Log::info('Starting ticket classification', ['ticket_id' => $this->ticket->id]);

            $classification = $classifier->classify($this->ticket);

            // If user has manually set a category and we want to preserve it,
            // only update explanation and confidence
            if ($this->preserveManualCategory && $this->ticket->isManuallyCategorized()) {
                $this->ticket->update([
                    'explanation' => $classification['explanation'],
                    'confidence' => $classification['confidence'],
                ]);

                Log::info('Updated ticket with AI explanation while preserving manual category', [
                    'ticket_id' => $this->ticket->id,
                    'category' => $this->ticket->category,
                ]);
            } else {
                // Update all classification fields
                $this->ticket->update([
                    'category' => $classification['category'],
                    'explanation' => $classification['explanation'],
                    'confidence' => $classification['confidence'],
                ]);

                Log::info('Ticket classified successfully', [
                    'ticket_id' => $this->ticket->id,
                    'category' => $classification['category'],
                    'confidence' => $classification['confidence'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Ticket classification failed', [
                'ticket_id' => $this->ticket->id,
                'error' => $e->getMessage(),
            ]);

            // Re-throw the exception to trigger job retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Ticket classification job failed permanently', [
            'ticket_id' => $this->ticket->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
