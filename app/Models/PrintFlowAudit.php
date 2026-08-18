<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintFlowAudit extends Model
{
    use BelongsToEvent;

    public const UPDATED_AT = null;

    protected $fillable = [
        'event_id', 'print_flow_id', 'actor_type', 'actor_id', 'action',
        'before_data', 'after_data', 'ip_address', 'user_agent', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'before_data' => 'array',
            'after_data' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function printFlow(): BelongsTo
    {
        return $this->belongsTo(PrintFlow::class);
    }
}
