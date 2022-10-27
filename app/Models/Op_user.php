<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Op_user extends Model
{
    use HasFactory;
    protected $table = 'users';
    protected $fillable = [
        'phone',
        'phone_confirmed',
        'type',
        'is_active',
        'created_at',
        'access_token'
    ];
}
