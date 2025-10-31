<?php

namespace App\Models;

use App\Enums\Database\TableNameEnum;
use App\Enums\Event\EventFieldsEnum;
use App\Enums\Leg\LegFieldsEnum;
use App\Enums\ShipmentSource\ShipmentSourceFieldsEnum;
use App\Enums\Source\SourceFieldsEnum;
use App\Enums\SourceEvent\SourceEventFieldsEnum;
use App\Enums\ValueTypEnum;
use App\Enums\WebhookDelivery\WebhookDeliveryFieldsEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Source extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = TableNameEnum::SOURCES;
    protected $guarded = [];

    protected $casts = [
        SourceFieldsEnum::CONFIG  => ValueTypEnum::ARRAY_VALUE,
        SourceFieldsEnum::ENABLED => ValueTypEnum::BOOLEAN,
    ];

    public function shipments(): BelongsToMany
    {
        return $this->belongsToMany(
            Shipment::class,
            TableNameEnum::SHIPMENT_SOURCES,
            ShipmentSourceFieldsEnum::SHIPMENT_ID,
            ShipmentSourceFieldsEnum::SOURCE_ID
        );
    }

    public function legs(): HasMany
    {
        return $this->hasMany(Leg::class, LegFieldsEnum::SOURCE_ID);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, EventFieldsEnum::SOURCE_ID);
    }

    public function sourceEvents(): HasMany
    {
        return $this->hasMany(SourceEvent::class, SourceEventFieldsEnum::SOURCE_ID);
    }

    public function shipmentSources(): HasMany
    {
        return $this->hasMany(ShipmentSource::class, ShipmentSourceFieldsEnum::SOURCE_ID);
    }

    public function webhookDeliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, WebhookDeliveryFieldsEnum::SOURCE_ID);
    }
}
