<?php

namespace App\Models;

use Database\Factories\DesignApprovalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pencatatan ACC desain pada satu versi file final (MOD-03, AC-03-5, BR-05, BR-17).
 */
#[Fillable([
    'design_job_id',
    'design_file_id',
    'approved_by',
    'approved_at',
    'method',
    'note',
])]
class DesignApproval extends Model
{
    /** @use HasFactory<DesignApprovalFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
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
     * @return BelongsTo<DesignFile, $this>
     */
    public function designFile(): BelongsTo
    {
        return $this->belongsTo(DesignFile::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
