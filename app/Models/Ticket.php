<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory, HasUlids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'subject',
        'body',
        'status',
        'category',
        'explanation',
        'confidence',
        'note',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'confidence' => 'float',
        ];
    }

    /**
     * The possible status values.
     */
    public const STATUSES = [
        'open',
        'closed',
        'pending',
    ];

    /**
     * The possible category values.
     */
    public const CATEGORIES = [
        'technical',
        'billing',
        'general',
        'bug_report',
        'feature_request',
        'account',
        'other',
    ];

    /**
     * Check if the ticket has been manually categorized.
     */
    public function isManuallyCategorized(): bool
    {
        return !is_null($this->category) && is_null($this->confidence);
    }

    /**
     * Check if the ticket has AI classification.
     */
    public function hasAiClassification(): bool
    {
        return !is_null($this->category) && !is_null($this->confidence);
    }

    /**
     * Scope for filtering by status.
     */
    public function scopeByStatus($query, ?string $status)
    {
        if ($status && in_array($status, self::STATUSES)) {
            return $query->where('status', $status);
        }

        return $query;
    }

    /**
     * Scope for filtering by category.
     */
    public function scopeByCategory($query, ?string $category)
    {
        if ($category && in_array($category, self::CATEGORIES)) {
            return $query->where('category', $category);
        }

        return $query;
    }

    /**
     * Scope for text search in subject and body.
     */
    public function scopeSearch($query, ?string $search)
    {
        if ($search) {
            return $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('body', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
