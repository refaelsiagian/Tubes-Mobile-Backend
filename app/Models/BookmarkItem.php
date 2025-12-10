<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookmarkItem extends Model
{
    use HasFactory;

    const UPDATED_AT = null;
    
    protected $table = 'bookmark_items';

    protected $fillable = [
        'user_id',
        'post_id',
    ];
    
        public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}