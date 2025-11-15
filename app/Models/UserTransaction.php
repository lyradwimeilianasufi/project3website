<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTransaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';

    protected $fillable = [
        'user_id', 
        'transaction_id', 
        'total', 
        'status', 
        'payment_url',
        'snap_token', // ✅ DITAMBAHKAN
        'invoice_url',
        'expiry_time',
        'payment_method', // ✅ DITAMBAHKAN
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'expiry_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'is_expired',
        'payment_method_name'
    ];

    public function items()
    {
        return $this->hasMany(TransactionItem::class, 'transaction_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // ✅ ACCESSOR UNTUK FLUTTER
    public function getIsExpiredAttribute()
    {
        return $this->expiry_time && $this->expiry_time->isPast();
    }

    // ✅ ACCESSOR UNTUK PAYMENT METHOD NAME
    public function getPaymentMethodNameAttribute()
    {
        $methodNames = [
            'bank_transfer' => 'Bank Transfer',
            'qris' => 'QRIS',
            'credit_card' => 'Kartu Kredit',
            'gopay' => 'Gopay',
            'shopeepay' => 'ShopeePay',
            'bca_va' => 'BCA Virtual Account',
            'bni_va' => 'BNI Virtual Account',
            'bri_va' => 'BRI Virtual Account',
        ];

        return $methodNames[$this->payment_method] ?? 
               ucfirst(str_replace('_', ' ', $this->payment_method));
    }

    // ✅ HELPER METHODS
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isSuccess()
    {
        return in_array($this->status, ['success', 'settlement', 'capture']);
    }

    public function isFailed()
    {
        return in_array($this->status, ['failed', 'deny', 'cancel', 'expire']);
    }

    public function isExpired()
    {
        return $this->expiry_time && $this->expiry_time->isPast();
    }

    // ✅ UPDATE STATUS METHOD
    public function updateStatus($status)
    {
        $this->update(['status' => $status]);
        return $this;
    }

    // ✅ MARK AS SUCCESS
    public function markAsSuccess()
    {
        return $this->updateStatus('success');
    }

    // ✅ MARK AS FAILED
    public function markAsFailed()
    {
        return $this->updateStatus('failed');
    }

    // ✅ MARK AS EXPIRED
    public function markAsExpired()
    {
        return $this->updateStatus('expire');
    }
}