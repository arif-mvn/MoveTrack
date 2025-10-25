<?php

namespace App\Models;

use App\Enums\EventFieldsEnum;
use App\Enums\LegFieldsEnum;
use App\Enums\ShipmentSourceFieldsEnum;
use App\Enums\SourceEventFieldsEnum;
use App\Enums\SourceFieldsEnum;
use App\Enums\ShipmentFieldsEnum;
use App\Enums\WebhookDeliveryFieldsEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sources';
    protected $guarded = [];

    protected $casts = [
        SourceFieldsEnum::CONFIG  => 'array',
        SourceFieldsEnum::ENABLED => 'boolean',
    ];

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, ShipmentFieldsEnum::STATUS_SOURCE_ID);
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
