<?php

namespace App\Http\Resources\V1\Identifier;

use App\Enums\ResourceObjectEnum;
use App\Enums\Identifier\IdentifierFieldsEnum;
use App\Enums\Identifier\IdentifierExpandsEnum;
use App\Http\Resources\V1\Shipment\ShipmentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IdentifierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'object'                          => ResourceObjectEnum::IDENTIFIER,
            IdentifierFieldsEnum::ID          => $this->{IdentifierFieldsEnum::ID},
            IdentifierFieldsEnum::SHIPMENT_ID => $this->{IdentifierFieldsEnum::SHIPMENT_ID},
            IdentifierFieldsEnum::SCOPE       => $this->{IdentifierFieldsEnum::SCOPE},
            IdentifierFieldsEnum::CARRIER_CODE=> $this->{IdentifierFieldsEnum::CARRIER_CODE},
            IdentifierFieldsEnum::VALUE       => $this->{IdentifierFieldsEnum::VALUE},
            IdentifierFieldsEnum::CREATED_AT  => optional($this->{IdentifierFieldsEnum::CREATED_AT}),
            IdentifierFieldsEnum::UPDATED_AT  => optional($this->{IdentifierFieldsEnum::UPDATED_AT}),
            // Expansions
            IdentifierExpandsEnum::SHIPMENT   => $this->whenLoaded(IdentifierExpandsEnum::SHIPMENT,
                                                fn() => new ShipmentResource($this->{IdentifierExpandsEnum::SHIPMENT})),
        ];
    }
}
