<?php

namespace App\Models;

use App\Enums\EventFieldsEnum;
use App\Enums\IdentifierFieldsEnum;
use App\Enums\LegFieldsEnum;
use App\Enums\ShipmentFieldsEnum;
use App\Enums\ShipmentSourceFieldsEnum;
use App\Enums\SourceEventFieldsEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    use HasFactory;

    protected $table = 'shipments';
    protected $guarded = [];

    protected $casts = [
        ShipmentFieldsEnum::DELIVERED_AT         => 'datetime',
        ShipmentFieldsEnum::DELIVERED_AT_COURIER => 'datetime',
        ShipmentFieldsEnum::STATUS_DISCREPANCY   => 'boolean',
        ShipmentFieldsEnum::LAST_EVENT_AT        => 'datetime',
        ShipmentFieldsEnum::LAST_SYNCED_AT       => 'datetime',
        ShipmentFieldsEnum::SUMMARY_TIMESTAMPS   => 'array',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, ShipmentFieldsEnum::STATUS_SOURCE_ID);
    }

    public function identifiers(): HasMany
    {
        return $this->hasMany(Identifier::class, IdentifierFieldsEnum::SHIPMENT_ID);
    }

    public function legs(): HasMany
    {
        return $this->hasMany(Leg::class, LegFieldsEnum::SHIPMENT_ID);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, EventFieldsEnum::SHIPMENT_ID);
    }

    public function sourceEvents(): HasMany
    {
        return $this->hasMany(SourceEvent::class, SourceEventFieldsEnum::SHIPMENT_ID);
    }

    public function shipmentSources(): HasMany
    {
        return $this->hasMany(ShipmentSource::class, ShipmentSourceFieldsEnum::SHIPMENT_ID);
    }

    /* Scopes */
    public function scopeDelivered($q)
    {
        return $q->where(ShipmentFieldsEnum::CURRENT_STATUS, 'DELIVERED');
    }

    public function scopeNeedsRefresh($q, int $staleMinutes = 90)
    {
        return $q->where(function ($w) use ($staleMinutes) {
            $w->whereNull(ShipmentFieldsEnum::LAST_SYNCED_AT)
                ->orWhere(ShipmentFieldsEnum::LAST_SYNCED_AT, '<', now()->subMinutes($staleMinutes));
        });
    }
}
