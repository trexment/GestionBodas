<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteItem extends Model
{
    use HasFactory;

    protected static bool $schemaChecked = false;
    protected static bool $supportsTextDescription = false;

    protected $fillable = [
        'quote_id',
        'service_name',
        'description',
        'price',
        'quantity'
    ];

    protected static function booted(): void
    {
        static::saving(function (QuoteItem $item) {
            if (!self::$schemaChecked) {
                self::$schemaChecked = true;
                try {
                    $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
                    if (in_array($driver, ['mysql', 'mariadb'])) {
                        \Illuminate\Support\Facades\DB::statement('ALTER TABLE quote_items MODIFY description TEXT NULL');
                        self::$supportsTextDescription = true;
                    } elseif ($driver === 'sqlite') {
                        self::$supportsTextDescription = true;
                    }
                } catch (\Throwable $e) {
                    self::$supportsTextDescription = false;
                }
            }

            if (!self::$supportsTextDescription && !empty($item->description) && mb_strlen($item->description) > 250) {
                $item->description = mb_substr($item->description, 0, 247) . '...';
            }

            if (!empty($item->service_name) && mb_strlen($item->service_name) > 250) {
                $item->service_name = mb_substr($item->service_name, 0, 250);
            }
        });
    }

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }
}
