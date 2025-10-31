<?php

namespace App\Http\Resources\V1;

use App\Http\Resources\V1\Event\EventResource;
use App\Http\Resources\V1\Shipment\ShipmentResource;
use App\Http\Resources\V1\Source\SourceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimelineResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'shipment' => new ShipmentResource($this->resource['shipment']->loadMissing('statusSource')),
            'segments' => collect($this->resource['segments'])->map(function ($seg) {
                return [
                    'source' => $seg['source'] ? new SourceResource($seg['source']) : null,
                    'events' => EventResource::collection($seg['events']),
                ];
            }),
        ];
    }
}
