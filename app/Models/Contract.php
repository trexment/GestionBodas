<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'contract_number',
        'token',
        'signed_at',
        'status',
        'signature_type',
        'signature_data',
        'certificate_issuer',
        'certificate_serial',
        'certificate_hash',
        'certificate_subject',
        'signed_ip',
        'consent_rrss',
        'client_name_signed',
        'client_dni_signed',
        'client_phone_signed',
        'client_email_signed',
        'client_address_signed',
        'client_postal_code_signed',
        'client_city_signed',
        'client_province_signed',
        'pdf_path',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'consent_rrss' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($contract) {
            if (empty($contract->token)) {
                $contract->token = Str::random(32);
            }
            if (empty($contract->contract_number)) {
                $contract->contract_number = $contract->generateDefaultNumber();
            }
        });
    }

    /**
     * Genera un identificador representativo para el contrato basado en el nombre y año del evento.
     */
    public function generateDefaultNumber(): string
    {
        if ($this->event) {
            $year = $this->event->event_date ? \Carbon\Carbon::parse($this->event->event_date)->format('Y') : date('Y');
            $cleanName = Str::upper(Str::slug(Str::limit($this->event->name, 35, ''), '-'));
            $baseNumber = "CTR-{$year}-{$cleanName}";

            // Si ya existe otro contrato en este evento, añadir sufijo para diferenciarlos
            $count = static::where('event_id', $this->event_id)->where('id', '!=', $this->id ?? 0)->count();
            if ($count > 0) {
                return "{$baseNumber}-" . ($count + 1);
            }

            return $baseNumber;
        }

        return 'CTR-' . str_pad($this->id ?: (static::max('id') + 1), 5, '0', STR_PAD_LEFT);
    }

    /**
     * Accesor para el número o código de contrato (siempre disponible, incluso si la columna no se ha migrado aún)
     */
    public function getContractNumberAttribute(): string
    {
        if (!empty($this->attributes['contract_number'])) {
            return $this->attributes['contract_number'];
        }

        if ($this->event) {
            return $this->generateDefaultNumber();
        }

        return 'CTR-' . str_pad($this->id ?? 1, 5, '0', STR_PAD_LEFT);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
