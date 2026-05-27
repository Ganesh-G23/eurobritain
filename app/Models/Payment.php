<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $table = 'payments';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    protected static function booted()
    {
        static::saved(function ($payment) {
            $payment->syncInvoiceAmounts();
        });

        static::updated(function ($payment) {
            if ($payment->wasChanged('invoice_id')) {
                $oldInvoiceId = $payment->getOriginal('invoice_id');
                if ($oldInvoiceId) {
                    $invoice = Invoice::find($oldInvoiceId);
                    if ($invoice) {
                        $paid = Payment::query()->where('invoice_id', $invoice->id)->sum('amount');
                        $invoice->update([
                            'paid_amount' => $paid,
                            'pending_amount' => max(0, $invoice->total_amount - $paid),
                        ]);
                    }
                }
            }
            $payment->syncInvoiceAmounts();
        });

        static::deleted(function ($payment) {
            $payment->syncInvoiceAmounts();
        });
    }

    public function syncInvoiceAmounts()
    {
        if ($this->invoice_id) {
            $invoice = Invoice::find($this->invoice_id);
            if ($invoice) {
                $paid = Payment::query()->where('invoice_id', $invoice->id)->sum('amount');
                $invoice->update([
                    'paid_amount' => $paid,
                    'pending_amount' => max(0, $invoice->total_amount - $paid),
                ]);
            }
        }
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function associate(): BelongsTo
    {
        return $this->belongsTo(Associate::class, 'associate_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
