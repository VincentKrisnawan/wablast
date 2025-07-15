<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageSession extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     * This is the crucial line to prevent the 'updated_at' error.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'batch_id',
        'session_number',
        'status',
        'started_at',
        'ended_at',
    ];

    /**
     * Get the batch that the session belongs to.
     */
    public function batch()
    {
        return $this->belongsTo(UploadBatch::class, 'batch_id');
    }

    /**
     * Get the messages for the message session.
     */
    public function messages()
    {
        return $this->hasMany(Message::class, 'session_id');
    }
}
