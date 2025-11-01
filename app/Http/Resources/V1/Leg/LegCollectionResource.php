<?php

namespace App\Http\Resources\V1\Leg;

use App\Enums\ResourceObjectEnum;
use App\Http\Resources\BaseCollectionResource;
use Illuminate\Http\Request;

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
