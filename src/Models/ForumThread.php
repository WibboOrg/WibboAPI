<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForumThread extends Model
{
    protected $table = 'cms_forum_thread';

    public $timestamps = false;
    
    protected $fillable = ['title', 'id', 'author', 'statut', 'type', 'statut', 'date', 'lastpost_author', 'lastpost_date', 'posts', 'main_post', 'categorie', 'views'];
}
