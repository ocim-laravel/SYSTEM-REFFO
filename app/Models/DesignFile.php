<?php

namespace App\Models;

use App\Enums\DesignFileKind;
use Database\Factories\DesignFileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * File desain versioned per pekerjaan desain (MOD-03, AC-03-3).
 */
#[Fillable([
    'design_job_id',
    'version',
    'kind',
    'path',
    'uploaded_by',
])]
class DesignFile extends Model
{
    /** @use HasFactory<DesignFileFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'kind' => DesignFileKind::class,
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
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * @return HasOne<DesignApproval, $this>
     */
    public function approval(): HasOne
    {
        return $this->hasOne(DesignApproval::class);
    }
}
