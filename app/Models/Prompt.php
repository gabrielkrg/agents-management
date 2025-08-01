<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prompt extends Model
{
    use HasUuids;

    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'description',
        'user_id',
        'json_schema',
    ];

    protected $casts = [
        'json_schema' => 'array',
    ];

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class, 'prompt_id', 'uuid');
    }
}
