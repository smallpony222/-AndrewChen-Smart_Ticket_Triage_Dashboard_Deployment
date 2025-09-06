<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ClassifyTicket;
use App\Models\Ticket;
use Illuminate\Console\Command;

class BulkClassifyTickets extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tickets:bulk-classify 
                            {--status= : Filter by status (open, closed, pending)}
                            {--unclassified : Only classify tickets without a category}
                            {--limit=50 : Maximum number of tickets to classify}
                            {--preserve-manual : Preserve manually set categories}';

    /**
     * The console command description.
     */
    protected $description = 'Bulk classify tickets using AI';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting bulk ticket classification...');

        $query = Ticket::query();

        // Apply filters
        if ($status = $this->option('status')) {
            if (!in_array($status, Ticket::STATUSES)) {
                $this->error("Invalid status. Valid statuses: " . implode(', ', Ticket::STATUSES));
                return 1;
            }
            $query->where('status', $status);
        }

        if ($this->option('unclassified')) {
            $query->whereNull('category');
        }

        $limit = (int) $this->option('limit');
        $preserveManual = $this->option('preserve-manual');

        $tickets = $query->limit($limit)->get();

        if ($tickets->isEmpty()) {
            $this->info('No tickets found matching the criteria.');
            return 0;
        }

        $this->info("Found {$tickets->count()} tickets to classify.");

        $bar = $this->output->createProgressBar($tickets->count());
        $bar->start();

        $jobsDispatched = 0;

        foreach ($tickets as $ticket) {
            // Skip manually categorized tickets if preserve-manual is enabled
            if ($preserveManual && $ticket->isManuallyCategorized()) {
                $bar->advance();
                continue;
            }

            ClassifyTicket::dispatch($ticket, $preserveManual);
            $jobsDispatched++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Dispatched {$jobsDispatched} classification jobs to the queue.");
        $this->info('Use "php artisan queue:work" to process the jobs.');

        return 0;
    }
}
