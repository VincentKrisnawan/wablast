<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UploadBatch extends Model
{
    protected $fillable = ['user_id', 'filename', 'total_contacts', 'uploaded_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(UploadContact::class, 'batch_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(MessageSession::class, 'batch_id');
    }

    public function template()
    {
        return $this->hasOne(MessageTemplate::class, 'batch_id');
    }
}

