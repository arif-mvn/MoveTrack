<?php

namespace App\Http\Resources\V1\Event;

use App\Enums\ResourceObjectEnum;
use App\Http\Resources\BaseCollectionResource;
use Illuminate\Http\Request;

class EventCollectionResource extends BaseCollectionResource
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
            'object' => ResourceObjectEnum::EVENT_LIST,
            'data'   => EventResource::collection($this->items()),
        ];
    }
}
