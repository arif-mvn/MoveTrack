<?php

namespace App\Models;

use App\Enums\Database\TableNameEnum;
use App\Enums\Event\EventFieldsEnum;
use App\Enums\Event\EventTypeEnum;
use App\Enums\Identifier\IdentifierFieldsEnum;
use App\Enums\Leg\LegFieldsEnum;
use App\Enums\Shipment\ShipmentFieldsEnum;
use App\Enums\ShipmentSource\ShipmentSourceFieldsEnum;
use App\Enums\SourceEvent\SourceEventFieldsEnum;
use App\Enums\ValueTypEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    use HasFactory;

    protected $table = TableNameEnum::SHIPMENTS;
    protected $guarded = [];

    protected $casts = [
        ShipmentFieldsEnum::DELIVERED_AT         => ValueTypEnum::DATETIME,
        ShipmentFieldsEnum::DELIVERED_AT_COURIER => ValueTypEnum::DATETIME,
        ShipmentFieldsEnum::STATUS_DISCREPANCY   => ValueTypEnum::BOOLEAN,
        ShipmentFieldsEnum::LAST_EVENT_AT        => ValueTypEnum::DATETIME,
        ShipmentFieldsEnum::LAST_SYNCED_AT       => ValueTypEnum::DATETIME,
        ShipmentFieldsEnum::SUMMARY_TIMESTAMPS   => ValueTypEnum::ARRAY_VALUE,
    ];

    public function statusSource(): BelongsTo
    {
        return $this->belongsTo(Source::class, ShipmentFieldsEnum::STATUS_SOURCE_ID);
    }
    public function sources(): BelongsToMany
    {
        return $this->belongsToMany(
            Source::class,
            TableNameEnum::SHIPMENT_SOURCES,
            ShipmentSourceFieldsEnum::SHIPMENT_ID,
            ShipmentSourceFieldsEnum::SOURCE_ID
        );
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
        return $q->where(ShipmentFieldsEnum::CURRENT_STATUS, EventTypeEnum::DELIVERED);
    }

    public function scopeNeedsRefresh($q, int $staleMinutes = 90)
    {
        return $q->where(function ($w) use ($staleMinutes) {
            $w->whereNull(ShipmentFieldsEnum::LAST_SYNCED_AT)
                ->orWhere(ShipmentFieldsEnum::LAST_SYNCED_AT, '<', now()->subMinutes($staleMinutes));
        });
    }
}
