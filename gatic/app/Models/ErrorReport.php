<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ErrorReport extends Model
{
    protected $fillable = [
        'error_id',
        'environment',
        'user_id',
        'user_role',
        'method',
        'url',
        'route',
        'exception_class',
        'exception_message',
        'stack_trace',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];
}
