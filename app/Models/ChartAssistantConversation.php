<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChartAssistantConversation extends Model
{
    protected $fillable = [
        'resident_id',
        'title',
        'status',
        'context',
        'messages',
    ];

    protected $casts = [
        'context' => 'array',
        'messages' => 'array',
    ];

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }
}
