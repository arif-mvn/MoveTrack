<?php

namespace App\Http\Resources\V1\Source;

use App\Enums\ResourceObjectEnum;
use Illuminate\Http\Request;
use MoveOn\Core\Http\Resources\BaseCollectionResource;

class SourceCollectionResource extends BaseCollectionResource
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
            'object' => ResourceObjectEnum::SOURCE_LIST,
            'data'   => SourceResource::collection($this->items()),
        ];
    }
}
