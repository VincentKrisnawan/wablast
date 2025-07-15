<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UploadContact extends Model
{
    protected $fillable = ['batch_id', 'no_hp', 'nama', 'data_json'];

    protected $casts = [
        'data_json' => 'array',
    ];

    public function batch()
    {
        return $this->belongsTo(UploadBatch::class, 'batch_id');
    }
}
