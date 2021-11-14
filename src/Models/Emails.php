<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Emails extends Model
{
    protected $table = 'cms_mail_confirm';

    public $timestamps = false;
}
