<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';

    protected $fillable = [
        'errand_id',
        'client_id',
        'tasker_id',
        'amount',
        'method',
        'status',
        'reference',
        'proof_path',
        'proof_submitted_at',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'proof_submitted_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function errand(): BelongsTo
    {
        return $this->belongsTo(Errand::class, 'errand_id');
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