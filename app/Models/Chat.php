<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chat extends Model
{
    protected $fillable = [
        'prompt_id',
        'role',
        'text',
    ];

    protected $casts = [
        'role' => 'string',
    ];

    public function prompt(): BelongsTo
    {
        return $this->belongsTo(Prompt::class, 'prompt_id', 'uuid');
    }
}
