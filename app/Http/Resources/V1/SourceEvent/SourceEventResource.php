<?php

namespace App\Http\Resources\V1\SourceEvent;

use App\Enums\ResourceObjectEnum;
use App\Enums\SourceEvent\SourceEventExpandsEnum;
use App\Enums\SourceEvent\SourceEventFieldsEnum;
use App\Http\Resources\V1\Source\SourceResource;
use App\Http\Resources\V1\Shipment\ShipmentResource;
use App\Http\Resources\V1\Event\EventResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class SourceEventResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array
     */
    public function prepare(Request $request): array
    {
        return [
            'object'                               => ResourceObjectEnum::SOURCE_EVENT,
            SourceEventFieldsEnum::ID              => $this->{SourceEventFieldsEnum::ID},
            SourceEventFieldsEnum::SHIPMENT_ID     => $this->{SourceEventFieldsEnum::SHIPMENT_ID},
            SourceEventFieldsEnum::EVENT_ID        => $this->{SourceEventFieldsEnum::EVENT_ID},
            SourceEventFieldsEnum::SOURCE_ID       => $this->{SourceEventFieldsEnum::SOURCE_ID},
            SourceEventFieldsEnum::SOURCE_EVENT_ID => $this->{SourceEventFieldsEnum::SOURCE_EVENT_ID},
            SourceEventFieldsEnum::OCCURRED_AT     => optional($this->{SourceEventFieldsEnum::OCCURRED_AT}),
            SourceEventFieldsEnum::PAYLOAD_HASH    => $this->{SourceEventFieldsEnum::PAYLOAD_HASH},
            SourceEventFieldsEnum::PAYLOAD         => $this->{SourceEventFieldsEnum::PAYLOAD},
            SourceEventFieldsEnum::RECEIVED_AT     => optional($this->{SourceEventFieldsEnum::RECEIVED_AT}),
            SourceEventFieldsEnum::CREATED_AT      => optional($this->{SourceEventFieldsEnum::CREATED_AT}),
            SourceEventFieldsEnum::UPDATED_AT      => optional($this->{SourceEventFieldsEnum::UPDATED_AT}),
            SourceEventExpandsEnum::SOURCE         => $this->whenLoaded(SourceEventExpandsEnum::SOURCE,
                                                    fn() => new SourceResource($this->{SourceEventExpandsEnum::SOURCE})),
            SourceEventExpandsEnum::SHIPMENT       => $this->whenLoaded(SourceEventExpandsEnum::SHIPMENT,
                                                    fn() => new ShipmentResource($this->{SourceEventExpandsEnum::SHIPMENT})),
            SourceEventExpandsEnum::EVENT          => $this->whenLoaded(SourceEventExpandsEnum::EVENT,
                                                    fn() => new EventResource($this->{SourceEventExpandsEnum::EVENT})),
        ];
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $result = $this->prepare($request);
        if ($request->has('fields')) {
            $result = Arr::only($result, array_map('trim', explode(',', $request->get('fields'))));
        }
        return Arr::where($result, fn($v) => $v !== null);
    }
}
