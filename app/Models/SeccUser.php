<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeccUser extends Model
{
    protected $connection = 'erp';
    protected $table = 'secc_users';
    protected $primaryKey = 'login';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;
}
