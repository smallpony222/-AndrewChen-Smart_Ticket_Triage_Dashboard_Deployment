<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Jobs\ClassifyTicket;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TicketController extends Controller
{
    /**
     * Display a listing of tickets with filtering, search, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Ticket::query();

        // Apply filters
        $query->byStatus($request->get('status'))
              ->byCategory($request->get('category'))
              ->search($request->get('search'));

        // Order by creation date (newest first)
        $query->orderBy('created_at', 'desc');

        // Paginate results
        $perPage = min((int) $request->get('per_page', 15), 50); // Max 50 per page
        $tickets = $query->paginate($perPage);

        return response()->json($tickets);
    }

    /**
     * Store a newly created ticket.
     */
    public function store(StoreTicketRequest $request): JsonResponse
    {
        $ticket = Ticket::create($request->validated());

        Log::info('New ticket created', ['ticket_id' => $ticket->id]);

        return response()->json($ticket, 201);
    }

    /**
     * Display the specified ticket.
     */
    public function show(Ticket $ticket): JsonResponse
    {
        return response()->json($ticket);
    }

    /**
     * Update the specified ticket.
     */
    public function update(UpdateTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $oldCategory = $ticket->category;
        
        $ticket->update($request->validated());

        // Log category changes
        if ($oldCategory !== $ticket->category) {
            Log::info('Ticket category updated', [
                'ticket_id' => $ticket->id,
                'old_category' => $oldCategory,
                'new_category' => $ticket->category,
            ]);
        }

        return response()->json($ticket);
    }

    /**
     * Dispatch AI classification job for the specified ticket.
     */
    public function classify(Ticket $ticket): JsonResponse
    {
        try {
            ClassifyTicket::dispatch($ticket, true);

            Log::info('Classification job dispatched', ['ticket_id' => $ticket->id]);

            return response()->json([
                'message' => 'Classification job queued successfully',
                'ticket_id' => $ticket->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch classification job', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to queue classification job',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export tickets to CSV format.
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $query = Ticket::query();

        // Apply same filters as index
        $query->byStatus($request->get('status'))
              ->byCategory($request->get('category'))
              ->search($request->get('search'));

        $query->orderBy('created_at', 'desc');

        $filename = 'tickets_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // CSV headers
            fputcsv($handle, [
                'ID',
                'Subject',
                'Body',
                'Status',
                'Category',
                'Confidence',
                'Note',
                'Created At',
                'Updated At',
            ]);

            // Stream data in chunks to handle large datasets
            $query->chunk(100, function ($tickets) use ($handle) {
                foreach ($tickets as $ticket) {
                    fputcsv($handle, [
                        $ticket->id,
                        $ticket->subject,
                        $ticket->body,
                        $ticket->status,
                        $ticket->category,
                        $ticket->confidence,
                        $ticket->note,
                        $ticket->created_at->toDateTimeString(),
                        $ticket->updated_at->toDateTimeString(),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
