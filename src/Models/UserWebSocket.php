<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserWebSocket extends Model
{
    protected $primaryKey = 'user_id';

    protected $table = 'user_websocket';

    public $timestamps = false;

    protected $fillable = ['user_id', 'auth_ticket', 'is_staff', 'langue'];
}
