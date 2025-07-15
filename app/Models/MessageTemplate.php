<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;

    class MessageTemplate extends Model
    {
        // Pastikan 'user_id' ada di sini dan hapus 'batch_id'
        protected $fillable = ['user_id', 'template'];

        // Relasi ke User (opsional tapi bagus untuk dimiliki)
        public function user()
        {
            return $this->belongsTo(User::class);
        }
    }