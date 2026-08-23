<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'type',
        'message',
        'link',
        'is_read',
    ];

    const UPDATED_AT = null;

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
    ];
}