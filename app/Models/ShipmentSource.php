<?php

namespace App\Models;

use App\Enums\ShipmentSourceFieldsEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentSource extends Model
{
    use HasFactory;

    protected $table = 'shipment_sources';
    protected $guarded = [];

    protected $casts = [
        ShipmentSourceFieldsEnum::LAST_SYNCED_AT => 'datetime',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, ShipmentSourceFieldsEnum::SHIPMENT_ID);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, ShipmentSourceFieldsEnum::SOURCE_ID);
    }
}
