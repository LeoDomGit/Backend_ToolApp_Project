<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Key extends Model
{
    use HasFactory;

    protected $table = 'keys';
    protected $fillable = [
        'api',
        'key',
        'gmail',
    ];
}
