<?php

namespace App\Models;

use App\Enums\EventFieldsEnum;
use App\Enums\LegFieldsEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Leg extends Model
{
    use HasFactory;

    protected $table = 'legs';
    protected $guarded = [];

    protected $casts = [
        LegFieldsEnum::ROUTE    => 'array',
        LegFieldsEnum::START_AT => 'datetime',
        LegFieldsEnum::END_AT   => 'datetime',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, LegFieldsEnum::SHIPMENT_ID);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, LegFieldsEnum::SOURCE_ID);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, EventFieldsEnum::LEG_ID);
    }
}
