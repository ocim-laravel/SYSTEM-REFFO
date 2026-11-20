<?php

namespace App\Models;

use Database\Factories\DesignRevisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catatan satu siklus revisi desain (MOD-03, AC-03-4, BR-04).
 */
#[Fillable([
    'design_job_id',
    'cycle_no',
    'request_note',
    'requested_by',
    'requested_at',
])]
class DesignRevision extends Model
{
    /** @use HasFactory<DesignRevisionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cycle_no' => 'integer',
            'requested_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<DesignJob, $this>
     */
    public function designJob(): BelongsTo
    {
        return $this->belongsTo(DesignJob::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
