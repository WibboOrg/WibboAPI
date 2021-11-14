<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForumPosts extends Model
{
    protected $table = 'cms_forum_post';

    public $timestamps = false;
    
    protected $fillable = ['threadid', 'message', 'author', 'look', 'date', 'motto', 'rank', 'id_auteur'];
}
