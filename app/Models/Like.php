<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    use HasFactory;

    // 1. Izinkan Mass Assignment
    protected $fillable = ['user_id', 'post_id'];

    // 2. MATIKAN TIMESTAMP UPDATE (PENTING!)
    // Karena di migration kamu cuma bikin 'created_at' dan tidak ada 'updated_at',
    // Laravel defaultnya akan error kalau ini tidak diset null.
    const UPDATED_AT = null; 

    // Relasi balik ke Post (Opsional)
    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}