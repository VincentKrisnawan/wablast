<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    protected $fillable = ['batch_id', 'template'];

    public function batch()
    {
        return $this->belongsTo(UploadBatch::class, 'batch_id');
    }
}

