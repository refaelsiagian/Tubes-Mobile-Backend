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
        'folder_id',
        'post_id',
    ];

    public function folder()
    {
        return $this->belongsTo(BookmarkFolder::class, 'folder_id');
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}