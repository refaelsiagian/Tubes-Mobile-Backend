<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookmarkFolder extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function items()
    {
        return $this->hasMany(BookmarkItem::class, 'folder_id');
    }

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'bookmark_items', 'folder_id', 'post_id');
    }
}