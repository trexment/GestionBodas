<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'quote_id',
        'invoice_number',
        'type', // 'factura' o 'recibo'
        'amount',
        'tax',
        'tax_rate',
        'total',
        'issue_date',
        'status', // 'unpaid', 'paid', 'cancelled'
        'pdf_path',
        'notes',
        'payment_method',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'amount' => 'decimal:2',
        'tax' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * Comprueba si este documento es un Recibo
     */
    public function isReceipt(): bool
    {
        return ($this->type === 'recibo') || ((float)$this->tax === 0.0 && str_starts_with($this->invoice_number, 'REC'));
    }

    /**
     * Comprueba si este documento es una Factura
     */
    public function isInvoice(): bool
    {
        return !$this->isReceipt();
    }

    /**
     * Etiqueta del tipo de documento
     */
    public function getDocumentTypeLabelAttribute(): string
    {
        return $this->isReceipt() ? 'Recibo' : 'Factura';
    }

    /**
     * Generador automático del siguiente número correlativo de Factura o Recibo
     * Ej: FAC-2026-001 o REC-2026-001
     */
    public static function nextNumber(string $type = 'factura'): string
    {
        $year = date('Y');
        $prefix = ($type === 'recibo') ? 'REC-' . $year . '-' : 'FAC-' . $year . '-';

        // Buscar el último emitido de ese año con ese prefijo
        $last = self::where('invoice_number', 'LIKE', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($last) {
            $lastNumberPart = substr($last->invoice_number, strlen($prefix));
            $nextSeq = (int)$lastNumberPart + 1;
        } else {
            $nextSeq = 1;
        }

        return $prefix . str_pad($nextSeq, 3, '0', STR_PAD_LEFT);
    }
}
