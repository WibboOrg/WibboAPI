<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmdLogs extends Model
{
    protected $table = 'log_command';

    public $timestamps = false;
}
