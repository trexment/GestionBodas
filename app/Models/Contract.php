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
        });
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
