<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'content',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likes()
    {
        return $this->belongsToMany(User::class, 'likes', 'post_id', 'user_id');
    }

    public function series()
    {
        return $this->belongsToMany(Series::class, 'series_posts', 'post_id', 'series_id')
                    ->withPivot('position'); // Untuk mengambil data urutan 'position'
    }
    
    public function bookmarkItems()
    {
        return $this->hasMany(BookmarkItem::class);
    }
}