<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Errand extends Model
{
    use HasFactory;
    
    protected $table = 'errands';
    public $timestamps = true; 

    protected $fillable = [
        'client_id',
        'tasker_id',
        'title',
        'category',
        'description',
        'fee',
        'deadline',
        'location',
        'contact',
        'status',
        'cancel_requested_by_tasker',
        'cancel_requested_by_client',
        'created_at',
        'updated_at',
        'accepted_at',
        'in_progress_at',
        'completed_at',
        'failed_at'
    ];

    protected $casts = [
        'deadline'      => 'datetime',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
        'accepted_at'   => 'datetime',
        'in_progress_at' => 'datetime',
        'completed_at'  => 'datetime',
        'failed_at'     => 'datetime',

        'cancel_requested_by_tasker' => 'boolean',
        'cancel_requested_by_client' => 'boolean',
    ];

    public function attachment(): HasOne 
    {
        return $this->hasOne(ErrandAttachment::class, 'errand_id');
    }

    public function client(): BelongsTo 
    { 
        return $this->belongsTo(User::class, 'client_id'); 
    }

    public function tasker(): BelongsTo 
    { 
        return $this->belongsTo(User::class, 'tasker_id'); 
    }
}