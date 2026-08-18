<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class PrintFlow extends Model
{
    use BelongsToEvent, HasFactory;

    public const TYPES = [
        'main_print' => 'Impressão principal',
        'reevaluation' => 'Reavaliação de cartas',
        'testimonial_search' => 'Busca de depoimentos',
    ];

    public const STATUSES = [
        'distributed' => 'Distribuído',
        'in_review' => 'Em revisão',
        'ready_to_print' => 'Pronto para imprimir',
        'printing' => 'Impressão aberta',
        'completed' => 'Concluído',
        'cancelled' => 'Cancelado',
    ];

    public const OPEN_STATUSES = ['distributed', 'in_review', 'ready_to_print', 'printing'];

    protected $fillable = [
        'event_id', 'participant_id', 'team_member_id', 'type', 'status',
        'current_step', 'distributed_by', 'distributed_at', 'completed_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'distributed_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PrintFlow $flow): void {
            if (! $flow->event_id || ! $flow->participant_id || ! $flow->team_member_id) {
                return;
            }

            $participantEvent = Participant::withoutGlobalScopes()->whereKey($flow->participant_id)->value('event_id');

            if ((int) $participantEvent !== (int) $flow->event_id) {
                throw new LogicException('O fluxo e o participante devem pertencer ao mesmo evento.');
            }

            $authorized = TeamMember::query()->whereKey($flow->team_member_id)
                ->where('status', 'active')
                ->whereHas('events', fn ($query) => $query
                    ->whereKey($flow->event_id)
                    ->where('event_team_member.is_active', true))
                ->exists();

            if (! $authorized) {
                throw new LogicException('O membro da equipe não está autorizado neste evento.');
            }
        });
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function teamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class);
    }

    public function distributedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'distributed_by');
    }

    public function testimonials(): BelongsToMany
    {
        return $this->belongsToMany(Testimonial::class, 'print_flow_testimonial')
            ->withPivot('event_id')
            ->withTimestamps();
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(PrintFlowToken::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(PrintFlowReview::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(PrintFlowAudit::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }
}
