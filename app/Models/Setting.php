<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Nilai pengaturan sistem (MOD-10, PH-00).
 *
 * @property string $key
 * @property string|null $value
 * @property string $type
 * @property string $group
 */
#[Fillable(['key', 'value', 'type', 'group'])]
class Setting extends Model
{
    //
}
