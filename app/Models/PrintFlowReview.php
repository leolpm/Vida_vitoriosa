<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class PrintFlowReview extends Model
{
    use BelongsToEvent, HasFactory;

    protected $fillable = [
        'event_id', 'print_flow_id', 'testimonial_id', 'team_member_id',
        'decision', 'rejection_reason', 'decided_at',
    ];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saving(function (PrintFlowReview $review): void {
            if (! $review->event_id || ! $review->print_flow_id || ! $review->testimonial_id) {
                return;
            }

            $flowEvent = PrintFlow::withoutGlobalScopes()->whereKey($review->print_flow_id)->value('event_id');
            $testimonialEvent = Testimonial::withoutGlobalScopes()->whereKey($review->testimonial_id)->value('event_id');

            if ((int) $flowEvent !== (int) $review->event_id || (int) $testimonialEvent !== (int) $review->event_id) {
                throw new LogicException('A revisão, o fluxo e o depoimento devem pertencer ao mesmo evento.');
            }
        });
    }

    public function printFlow(): BelongsTo
    {
        return $this->belongsTo(PrintFlow::class);
    }

    public function testimonial(): BelongsTo
    {
        return $this->belongsTo(Testimonial::class);
    }

    public function teamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class);
    }
}
