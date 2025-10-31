<?php

namespace App\Models;

use App\Enums\Database\TableNameEnum;
use App\Enums\Event\EventFieldsEnum;
use App\Enums\Leg\LegFieldsEnum;
use App\Enums\ValueTypEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Leg extends Model
{
    use HasFactory;
    protected $table = TableNameEnum::LEGS;
    protected $guarded = [];

    protected $casts = [
        LegFieldsEnum::ROUTE    => ValueTypEnum::ARRAY_VALUE,
        LegFieldsEnum::START_AT => ValueTypEnum::DATETIME,
        LegFieldsEnum::END_AT   => ValueTypEnum::DATETIME,
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
