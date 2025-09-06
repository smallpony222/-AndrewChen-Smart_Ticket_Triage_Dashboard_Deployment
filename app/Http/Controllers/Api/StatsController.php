<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    /**
     * Get dashboard statistics for tickets.
     */
    public function index(): JsonResponse
    {
        $stats = [
            'total_tickets' => Ticket::count(),
            'tickets_by_status' => $this->getTicketsByStatus(),
            'tickets_by_category' => $this->getTicketsByCategory(),
            'classification_stats' => $this->getClassificationStats(),
            'recent_activity' => $this->getRecentActivity(),
        ];

        return response()->json($stats);
    }

    /**
     * Get ticket counts grouped by status.
     */
    private function getTicketsByStatus(): array
    {
        $statusCounts = Ticket::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Ensure all statuses are represented
        $result = [];
        foreach (Ticket::STATUSES as $status) {
            $result[$status] = $statusCounts[$status] ?? 0;
        }

        return $result;
    }

    /**
     * Get ticket counts grouped by category.
     */
    private function getTicketsByCategory(): array
    {
        $categoryCounts = Ticket::select('category', DB::raw('count(*) as count'))
            ->whereNotNull('category')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        // Include uncategorized tickets
        $uncategorized = Ticket::whereNull('category')->count();
        if ($uncategorized > 0) {
            $categoryCounts['uncategorized'] = $uncategorized;
        }

        return $categoryCounts;
    }

    /**
     * Get AI classification statistics.
     */
    private function getClassificationStats(): array
    {
        $total = Ticket::count();
        $aiClassified = Ticket::whereNotNull('confidence')->count();
        $manuallyClassified = Ticket::whereNotNull('category')
            ->whereNull('confidence')
            ->count();
        $unclassified = Ticket::whereNull('category')->count();

        $avgConfidence = Ticket::whereNotNull('confidence')
            ->avg('confidence');

        return [
            'total_tickets' => $total,
            'ai_classified' => $aiClassified,
            'manually_classified' => $manuallyClassified,
            'unclassified' => $unclassified,
            'average_confidence' => $avgConfidence ? round($avgConfidence, 2) : null,
            'classification_rate' => $total > 0 ? round((($aiClassified + $manuallyClassified) / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Get recent ticket activity.
     */
    private function getRecentActivity(): array
    {
        $recentTickets = Ticket::select('id', 'subject', 'status', 'category', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'subject' => $ticket->subject,
                    'status' => $ticket->status,
                    'category' => $ticket->category,
                    'created_at' => $ticket->created_at->toISOString(),
                ];
            });

        return $recentTickets->toArray();
    }
}
