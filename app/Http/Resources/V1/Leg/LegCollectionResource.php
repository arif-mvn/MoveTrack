<?php

namespace App\Http\Resources\V1\Leg;

use App\Enums\ResourceObjectEnum;
use Illuminate\Http\Request;
use MoveOn\Core\Http\Resources\BaseCollectionResource;

class LegCollectionResource extends BaseCollectionResource
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
            'object' => ResourceObjectEnum::LEG_LIST,
            'data'   => LegResource::collection($this->items()),
        ];
    }
}
