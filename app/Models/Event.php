<?php

namespace App\Models;

use App\Enums\Database\TableNameEnum;
use App\Enums\Event\EventFieldsEnum;
use App\Enums\SourceEvent\SourceEventFieldsEnum;
use App\Enums\ValueTypEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $table = TableNameEnum::EVENTS;

    protected $guarded = [];

    protected $casts = [
        EventFieldsEnum::OCCURRED_AT   => ValueTypEnum::DATETIME,
        EventFieldsEnum::LOCATION      => ValueTypEnum::ARRAY_VALUE,
        EventFieldsEnum::ACTOR         => ValueTypEnum::ARRAY_VALUE,
        EventFieldsEnum::EVIDENCE      => ValueTypEnum::ARRAY_VALUE,
        EventFieldsEnum::AUTHORITATIVE => ValueTypEnum::BOOLEAN,
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, EventFieldsEnum::SHIPMENT_ID);
    }

    public function leg(): BelongsTo
    {
        return $this->belongsTo(Leg::class, EventFieldsEnum::LEG_ID);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, EventFieldsEnum::SOURCE_ID);
    }

    public function sourceEvents(): HasMany
    {
        return $this->hasMany(SourceEvent::class, SourceEventFieldsEnum::EVENT_ID);
    }
}
