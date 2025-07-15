<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageSession extends Model
{
    protected $fillable = ['batch_id', 'session_number', 'status', 'started_at', 'ended_at'];

    public function batch()
    {
        return $this->belongsTo(UploadBatch::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'session_id');
    }
}

