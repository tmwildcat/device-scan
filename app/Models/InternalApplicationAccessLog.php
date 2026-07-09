<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'internal_application_id',
    'endpoint',
    'method',
    'scope_used',
    'status_code',
    'ip',
    'user_agent',
])]
class InternalApplicationAccessLog extends Model
{
    public $timestamps = false;

    /**
     * @return BelongsTo<InternalApplication,self>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(InternalApplication::class, 'internal_application_id');
    }
}
