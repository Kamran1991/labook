<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Op_phone_confirmation extends Model
{
    use HasFactory;
    protected $table = "phone_confirmation";
    protected $fillable = [
        phone,
        code,
        ip
    ];
}
