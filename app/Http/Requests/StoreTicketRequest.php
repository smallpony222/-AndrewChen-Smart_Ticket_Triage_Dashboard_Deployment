<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
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
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'status' => ['sometimes', 'string', 'in:' . implode(',', Ticket::STATUSES)],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'subject.required' => 'The ticket subject is required.',
            'subject.max' => 'The ticket subject cannot exceed 255 characters.',
            'body.required' => 'The ticket body is required.',
            'body.max' => 'The ticket body cannot exceed 10,000 characters.',
            'status.in' => 'The status must be one of: ' . implode(', ', Ticket::STATUSES),
        ];
    }
}
