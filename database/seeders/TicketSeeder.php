<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Ticket;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 25+ tickets with varied states
        
        // 10 tickets with AI classification
        Ticket::factory()
            ->count(10)
            ->aiClassified()
            ->create();

        // 8 tickets with manual classification
        Ticket::factory()
            ->count(8)
            ->manuallyClassified()
            ->create();

        // 7 tickets unclassified (need AI classification)
        Ticket::factory()
            ->count(7)
            ->unclassified()
            ->create();

        // Create some specific tickets for demonstration
        Ticket::factory()->create([
            'subject' => 'Critical: System outage affecting all users',
            'body' => 'We are experiencing a complete system outage that started at 2 PM EST. All users are unable to access the platform. This is affecting approximately 10,000 active users. Please prioritize this issue.',
            'status' => 'open',
            'category' => 'technical',
            'explanation' => 'This ticket describes a critical technical issue affecting system availability and user access.',
            'confidence' => 0.95,
            'note' => 'Escalated to engineering team. ETA for resolution: 2 hours.',
        ]);

        Ticket::factory()->create([
            'subject' => 'Billing inquiry - overcharged for premium features',
            'body' => 'I was charged $99 for premium features but I only signed up for the basic plan at $29. Please review my account and process a refund for the difference.',
            'status' => 'pending',
            'category' => 'billing',
            'explanation' => 'This ticket is related to billing discrepancies and requires account review.',
            'confidence' => 0.88,
            'note' => 'Reviewed account. Customer was automatically upgraded due to usage. Refund processed.',
        ]);

        Ticket::factory()->create([
            'subject' => 'Feature request: Export data to CSV',
            'body' => 'It would be very helpful if users could export their data in CSV format for analysis in external tools like Excel or Google Sheets.',
            'status' => 'open',
            'category' => 'feature_request',
            'note' => 'Added to product roadmap for Q2 2024.',
        ]);
    }
}
