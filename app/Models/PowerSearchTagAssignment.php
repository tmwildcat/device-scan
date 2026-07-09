<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'compiled_device_record_id',
    'power_search_option_id',
    'source',
    'notes',
    'assigned_by',
    'assigned_at',
])]
class PowerSearchTagAssignment extends Model
{
    /**
     * @return BelongsTo<CompiledDeviceRecord,self>
     */
    public function record(): BelongsTo
    {
        return $this->belongsTo(CompiledDeviceRecord::class, 'compiled_device_record_id');
    }

    /**
     * @return BelongsTo<PowerSearchOption,self>
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(PowerSearchOption::class, 'power_search_option_id');
    }

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
        ];
    }
}
