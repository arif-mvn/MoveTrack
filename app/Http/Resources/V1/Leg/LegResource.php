<?php

namespace App\Http\Resources\V1\Leg;

use App\Enums\ResourceObjectEnum;
use App\Enums\Leg\LegFieldsEnum;
use App\Enums\Leg\LegExpandsEnum;
use App\Http\Resources\V1\Source\SourceResource;
use App\Http\Resources\V1\Shipment\ShipmentResource;
use App\Http\Resources\V1\Event\EventResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class LegResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function prepare(Request $request): array
    {
        return [
            'object'                         => ResourceObjectEnum::LEG,
            LegFieldsEnum::ID                => $this->{LegFieldsEnum::ID},
            LegFieldsEnum::SHIPMENT_ID       => $this->{LegFieldsEnum::SHIPMENT_ID},
            LegFieldsEnum::TYPE              => $this->{LegFieldsEnum::TYPE},
            LegFieldsEnum::SOURCE_ID         => $this->{LegFieldsEnum::SOURCE_ID},
            LegFieldsEnum::CARRIER_NAME      => $this->{LegFieldsEnum::CARRIER_NAME},
            LegFieldsEnum::ROUTE             => $this->{LegFieldsEnum::ROUTE},
            LegFieldsEnum::START_AT          => optional($this->{LegFieldsEnum::START_AT}),
            LegFieldsEnum::END_AT            => optional($this->{LegFieldsEnum::END_AT}),
            LegFieldsEnum::CREATED_AT        => optional($this->{LegFieldsEnum::CREATED_AT}),
            LegFieldsEnum::UPDATED_AT        => optional($this->{LegFieldsEnum::UPDATED_AT}),
            // Expansions
            LegExpandsEnum::SOURCE           => $this->whenLoaded(LegExpandsEnum::SOURCE,
                                                fn() => new SourceResource($this->{LegExpandsEnum::SOURCE})),
            LegExpandsEnum::SHIPMENT         => $this->whenLoaded(LegExpandsEnum::SHIPMENT,
                                                fn() => new ShipmentResource($this->{LegExpandsEnum::SHIPMENT})),
            LegExpandsEnum::EVENTS           => $this->whenLoaded(LegExpandsEnum::EVENTS, fn() => [
                'object' => ResourceObjectEnum::EVENT_LIST,
                'data'   => EventResource::collection($this->{LegExpandsEnum::EVENTS})
            ]),
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
