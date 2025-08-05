<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $fillable = ['prompt_uuid', 'name', 'path', 'mime_type', 'size'];

    public function prompt()
    {
        return $this->belongsTo(Prompt::class, 'prompt_uuid', 'uuid');
    }
}
