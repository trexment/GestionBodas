<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class WhatsAppMessage extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'event_id',
        'user_id',
        'phone',
        'sender_name',
        'direction',
        'message',
        'status',
        'external_id',
        'brand',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getIsInboundAttribute(): bool
    {
        return $this->direction === 'inbound';
    }

    public function getIsOutboundAttribute(): bool
    {
        return $this->direction === 'outbound';
    }

    public function getFormattedTimeAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('d/m/Y H:i') : '';
    }

    public function getTimeShortAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('H:i') : '';
    }
}
