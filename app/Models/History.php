<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class History extends Model
{
    protected $table = 'history';
    public $timestamps = true; 

    protected $fillable = [
        'errand_id',
        'title',
        'category',
        'description',
        'expected_hours',
        'fee',
        'deadline',
        'location',
        'contact',
        'status',
        'client_id',
        'client_last_name',
        'client_first_name',
        'client_middle_name',
        'client_email',
        'client_contact_number',
        'client_profile_picture',
        'tasker_id',
        'tasker_last_name',
        'tasker_first_name',
        'tasker_middle_name',
        'tasker_email',
        'tasker_contact_number',
        'tasker_profile_picture',
        'created_at',
        'accepted_at',
        'in_process_at',
        'completed_at',
        'failed_at',
        'tasker_deleted',
        'client_deleted'
    ];

    protected $casts = [
        'deadline'       => 'datetime',
        'created_at'     => 'datetime',
        'accepted_at'    => 'datetime',
        'in_process_at'  => 'datetime',
        'completed_at'   => 'datetime',
        'failed_at'      => 'datetime',
        'fee'            => 'decimal:2',
        'expected_hours' => 'decimal:2',
        'tasker_deleted' => 'boolean',
        'client_deleted' => 'boolean',
    ];

    public function attachment(): HasOne 
    {
        return $this->hasOne(HistoryAttachment::class, 'history_errand_id');
    }

    public function paymentProof(): HasOne 
    {
        return $this->hasOne(HistoryPaymentProof::class, 'history_errand_id');
    }
}