<?php

namespace App\Http\Resources\V1\SourceEvent;

use App\Enums\ResourceObjectEnum;
use Illuminate\Http\Request;
use MoveOn\Core\Http\Resources\BaseCollectionResource;

class SourceEventCollectionResource extends BaseCollectionResource
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
            'object' => ResourceObjectEnum::SOURCE_EVENT_LIST,
            'data'   => SourceEventResource::collection($this->items()),
        ];
    }
}
