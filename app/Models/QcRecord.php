<?php

namespace App\Models;

use App\Enums\QcResult;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Hasil QC finishing (MOD-05).
 */
#[Fillable([
    'finishing_job_id',
    'result',
    'notes',
    'checked_by',
    'checked_at',
])]
class QcRecord extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'result' => QcResult::class,
            'checked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<FinishingJob, $this>
     */
    public function finishingJob(): BelongsTo
    {
        return $this->belongsTo(FinishingJob::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    /**
     * @return HasOne<Rework, $this>
     */
    public function rework(): HasOne
    {
        return $this->hasOne(Rework::class);
    }
}
