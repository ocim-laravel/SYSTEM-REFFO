<?php

namespace App\Models;

use App\Enums\ReprintStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pengajuan cetak ulang (MOD-04, BR-11).
 */
#[Fillable([
    'print_job_id',
    'qty',
    'reason',
    'requested_by',
    'approved_by',
    'approved_at',
    'status',
    'actual_qty',
])]
class ReprintRequest extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty' => 'decimal:2',
            'actual_qty' => 'decimal:2',
            'status' => ReprintStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PrintJob, $this>
     */
    public function printJob(): BelongsTo
    {
        return $this->belongsTo(PrintJob::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
