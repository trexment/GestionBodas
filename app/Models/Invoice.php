<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;
    protected $fillable = ['event_id', 'invoice_number', 'amount', 'tax', 'total', 'issue_date', 'status', 'pdf_path'];
    protected $casts = ['issue_date' => 'date'];
    public function event() { return $this->belongsTo(Event::class); }
}
