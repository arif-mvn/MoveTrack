<?php

namespace App\Models;

use App\Enums\EventFieldsEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $table = 'events';
    protected $guarded = [];

    protected $casts = [
        EventFieldsEnum::OCCURRED_AT   => 'datetime',
        EventFieldsEnum::LOCATION      => 'array',
        EventFieldsEnum::ACTOR         => 'array',
        EventFieldsEnum::EVIDENCE      => 'array',
        EventFieldsEnum::AUTHORITATIVE => 'boolean',
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
        return $this->hasMany(SourceEvent::class, \App\Enums\SourceEventFieldsEnum::EVENT_ID);
    }
}
