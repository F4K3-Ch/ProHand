<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoryPaymentProof extends Model
{
    protected $table = 'history_payment_proofs';
    public $timestamps = false; 

    protected $fillable = [
        'history_errand_id',
        'file_path',
        'original_name',
        'uploaded_at'
    ];

    public function historyErrand(): BelongsTo
    {
        return $this->belongsTo(History::class, 'history_errand_id');
    }
}