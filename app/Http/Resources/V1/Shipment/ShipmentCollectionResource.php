<?php

namespace App\Http\Resources\V1\Shipment;

use App\Enums\ResourceObjectEnum;
use App\Http\Resources\BaseCollectionResource;
use Illuminate\Http\Request;

class ShipmentCollectionResource extends BaseCollectionResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  Request  $request
     *
     * @return array
     */
    public function toArray($request): array
    {
        return [
            "object" => ResourceObjectEnum::SHIPMENT_LIST,
            "data"   => ShipmentResource::collection($this->items())
        ];
    }
}
