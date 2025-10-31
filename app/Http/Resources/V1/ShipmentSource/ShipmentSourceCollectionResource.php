<?php
namespace App\Http\Resources\V1\ShipmentSource;

use App\Enums\ResourceObjectEnum;
use MoveOn\Core\Http\Resources\BaseCollectionResource;

class ShipmentSourceCollectionResource extends BaseCollectionResource
{
    public function toArray($request): array
    {
        return [
            'object' => ResourceObjectEnum::SHIPMENT_SOURCE_LIST,
            'data'   => ShipmentSourceResource::collection($this->items()),
        ];
    }
}

