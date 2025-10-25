<?php

namespace App\Models;

use App\Enums\SourceEventFieldsEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourceEvent extends Model
{
    use HasFactory;

    protected $table = 'source_events';
    protected $guarded = [];

    protected $casts = [
        SourceEventFieldsEnum::PAYLOAD     => 'array',
        SourceEventFieldsEnum::OCCURRED_AT => 'datetime',
        SourceEventFieldsEnum::RECEIVED_AT => 'datetime',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, SourceEventFieldsEnum::SHIPMENT_ID);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, SourceEventFieldsEnum::EVENT_ID);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, SourceEventFieldsEnum::SOURCE_ID);
    }
}
