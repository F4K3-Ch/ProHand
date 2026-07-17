<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Errand extends Model
{
    protected $table = 'errands';
    public $timestamps = false;

    protected $fillable = [
        'client_id', 
        'tasker_id', 
        'title', 
        'description', 
        'deadline', 
        'contact', 
        'location', 
        'fee', 
        'status', 
        'payment_receipt_path', 
        'cancel_requested_by_tasker', 
        'cancel_requested_by_client',
        'accepted_at',
        'in_process_at',
        'completed_at',
        'failed_at'
    ];

    protected $casts = [
        'deadline'      => 'datetime',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',  
        'accepted_at'   => 'datetime',
        'in_process_at' => 'datetime',
        'completed_at'  => 'datetime',
        'failed_at'     => 'datetime',
    ];

    public function client(): BelongsTo 
    { 
        return $this->belongsTo(User::class, 'client_id'); 
    }

    public function tasker(): BelongsTo 
    { 
        return $this->belongsTo(User::class, 'tasker_id'); 
    }
}
