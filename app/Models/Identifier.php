<?php

namespace App\Models;

use App\Enums\Identifier\IdentifierFieldsEnum;
use App\Enums\Database\TableNameEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Identifier extends Model
{
    use HasFactory;

    protected $table = TableNameEnum::IDENTIFIERS;
    protected $guarded = [];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, IdentifierFieldsEnum::SHIPMENT_ID);
    }

    protected static function booted(): void
    {
        static::saving(function (self $id) {
            if ($id->{IdentifierFieldsEnum::CARRIER_CODE}) {
                $id->{IdentifierFieldsEnum::CARRIER_CODE} = strtolower($id->{IdentifierFieldsEnum::CARRIER_CODE});
            }
        });
    }
}
