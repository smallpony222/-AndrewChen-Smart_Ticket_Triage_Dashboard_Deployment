<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', 'in:' . implode(',', Ticket::STATUSES)],
            'category' => ['sometimes', 'nullable', 'string', 'in:' . implode(',', Ticket::CATEGORIES)],
            'note' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'status.in' => 'The status must be one of: ' . implode(', ', Ticket::STATUSES),
            'category.in' => 'The category must be one of: ' . implode(', ', Ticket::CATEGORIES),
            'note.max' => 'The note cannot exceed 5,000 characters.',
        ];
    }
}
