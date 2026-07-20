<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentProof extends Model
{
    protected $table = 'payment_proofs';
    public $timestamps = false; 

    protected $fillable = [
        'errand_id',
        'file_path',
        'original_name',
        'uploaded_at'
    ];

    public function errand(): BelongsTo
    {
        return $this->belongsTo(Errand::class, 'errand_id');
    }
}