<?php

namespace Modules\Branch\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean'
    ];
} 