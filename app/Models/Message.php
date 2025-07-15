<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    public $timestamps = false;

    protected $fillable = ['session_id', 'contact_id', 'message_text', 'status', 'sent_at', 'read_at', 'replied_at'];

    public function session()
    {
        return $this->belongsTo(MessageSession::class, 'session_id');
    }

    public function contact()
    {
        return $this->belongsTo(UploadContact::class, 'contact_id');
    }
}

