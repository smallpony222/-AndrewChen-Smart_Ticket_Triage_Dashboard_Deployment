<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $subjects = [
            'Unable to login to my account',
            'Password reset not working',
            'Payment failed but money was charged',
            'Website is loading very slowly',
            'Feature request: Dark mode',
            'Bug: Images not displaying correctly',
            'Account suspension inquiry',
            'Billing discrepancy on invoice',
            'Two-factor authentication issues',
            'Mobile app crashes on startup',
            'Data export functionality needed',
            'Email notifications not received',
            'Profile picture upload fails',
            'Search function returns no results',
            'Integration with third-party service',
            'Performance issues during peak hours',
            'Security concern about data handling',
            'Request for API documentation',
            'Subscription cancellation problem',
            'User interface improvement suggestion',
        ];

        $bodies = [
            'I have been trying to access my account for the past hour but keep getting an error message. Can you please help me resolve this issue?',
            'The password reset email is not arriving in my inbox. I have checked spam folder as well. Please assist.',
            'My payment was processed but I received an error message. The amount has been debited from my account but my subscription is not active.',
            'The website takes more than 30 seconds to load any page. This is affecting my productivity significantly.',
            'It would be great if you could add a dark mode option to reduce eye strain during night usage.',
            'Images are not loading properly on the dashboard. They appear as broken links. This started happening yesterday.',
            'I received an email saying my account has been suspended but I am not sure why. Please provide clarification.',
            'There is a discrepancy in my latest invoice. I was charged twice for the same service. Please review.',
            'I am unable to complete the two-factor authentication setup. The QR code is not scanning properly.',
            'The mobile application crashes immediately after opening. I have tried reinstalling but the issue persists.',
            'I need to export all my data for compliance purposes. Is there a way to get a complete data dump?',
            'I have not been receiving any email notifications for the past week. Please check my notification settings.',
            'Every time I try to upload a profile picture, I get an error saying the file format is not supported.',
            'The search functionality is not working. No matter what I search for, it returns zero results.',
            'I need help integrating your service with our existing CRM system. Do you have API documentation?',
            'During peak hours (9 AM - 5 PM), the system becomes very slow and sometimes unresponsive.',
            'I am concerned about how my personal data is being handled. Can you provide details about your security measures?',
            'I am a developer and need comprehensive API documentation to build an integration.',
            'I have been trying to cancel my subscription but the cancellation button is not working.',
            'The current user interface is confusing. Consider redesigning the navigation menu for better usability.',
        ];

        $categories = array_rand(array_flip(Ticket::CATEGORIES), 1);
        $hasAiClassification = $this->faker->boolean(60); // 60% chance of having AI classification

        return [
            'subject' => $this->faker->randomElement($subjects),
            'body' => $this->faker->randomElement($bodies),
            'status' => $this->faker->randomElement(Ticket::STATUSES),
            'category' => $hasAiClassification ? $categories : null,
            'explanation' => $hasAiClassification ? $this->faker->sentence(10) : null,
            'confidence' => $hasAiClassification ? $this->faker->randomFloat(2, 0.5, 1.0) : null,
            'note' => $this->faker->boolean(30) ? $this->faker->paragraph(2) : null, // 30% chance of having a note
        ];
    }

    /**
     * Indicate that the ticket should have manual categorization.
     */
    public function manuallyClassified(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $this->faker->randomElement(Ticket::CATEGORIES),
            'explanation' => null,
            'confidence' => null,
        ]);
    }

    /**
     * Indicate that the ticket should have AI classification.
     */
    public function aiClassified(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $this->faker->randomElement(Ticket::CATEGORIES),
            'explanation' => $this->faker->sentence(10),
            'confidence' => $this->faker->randomFloat(2, 0.5, 1.0),
        ]);
    }

    /**
     * Indicate that the ticket should be unclassified.
     */
    public function unclassified(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => null,
            'explanation' => null,
            'confidence' => null,
        ]);
    }
}
