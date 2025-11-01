<?php

namespace App\Http\Resources\V1\Shipment;

use App\Enums\ResourceObjectEnum;
use App\Enums\Shipment\ShipmentFieldsEnum;
use App\Enums\Shipment\ShipmentExpandsEnum;
use App\Http\Resources\V1\Identifier\IdentifierResource;
use App\Http\Resources\V1\Source\SourceResource;
use App\Http\Resources\V1\Event\EventResource;
use App\Http\Resources\V1\Leg\LegResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class ShipmentResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array
     */
    public function prepare(Request $request): array
    {
        return [
            'object'                                 => ResourceObjectEnum::SHIPMENT,
            ShipmentFieldsEnum::ID                   => $this->{ShipmentFieldsEnum::ID},
            ShipmentFieldsEnum::MODE                 => $this->{ShipmentFieldsEnum::MODE},
            ShipmentFieldsEnum::CURRENT_STATUS       => $this->{ShipmentFieldsEnum::CURRENT_STATUS},
            ShipmentFieldsEnum::STATUS_SOURCE_ID     => $this->{ShipmentFieldsEnum::STATUS_SOURCE_ID},
            ShipmentFieldsEnum::DELIVERED_AT         => optional($this->{ShipmentFieldsEnum::DELIVERED_AT}),
            ShipmentFieldsEnum::DELIVERED_AT_COURIER => optional($this->{ShipmentFieldsEnum::DELIVERED_AT_COURIER}),
            ShipmentFieldsEnum::STATUS_DISCREPANCY   => $this->{ShipmentFieldsEnum::STATUS_DISCREPANCY},
            ShipmentFieldsEnum::LAST_EVENT_AT        => optional($this->{ShipmentFieldsEnum::LAST_EVENT_AT}),
            ShipmentFieldsEnum::LAST_SYNCED_AT       => optional($this->{ShipmentFieldsEnum::LAST_SYNCED_AT}),
            ShipmentFieldsEnum::SUMMARY_TIMESTAMPS   => $this->{ShipmentFieldsEnum::SUMMARY_TIMESTAMPS},
            ShipmentFieldsEnum::CREATED_AT           => optional($this->{ShipmentFieldsEnum::CREATED_AT}),
            ShipmentFieldsEnum::UPDATED_AT           => optional($this->{ShipmentFieldsEnum::UPDATED_AT}),
            // Expansions
            ShipmentExpandsEnum::STATUS_SOURCE       => $this->whenLoaded(ShipmentExpandsEnum::STATUS_SOURCE,
                                                        fn() => new SourceResource($this->{ShipmentExpandsEnum::STATUS_SOURCE})
                                                    ),
            ShipmentExpandsEnum::IDENTIFIERS         => $this->whenLoaded(ShipmentExpandsEnum::IDENTIFIERS, fn() => [
                                                        'object' => ResourceObjectEnum::IDENTIFIER_LIST,
                                                        'data'   => IdentifierResource::collection($this->{ShipmentExpandsEnum::IDENTIFIERS})
                                                    ]),
            ShipmentExpandsEnum::SOURCES             => $this->whenLoaded(ShipmentExpandsEnum::SOURCES, fn() => [
                                                        'object' => ResourceObjectEnum::SOURCE_LIST,
                                                        'data'   => SourceResource::collection($this->{ShipmentExpandsEnum::SOURCES})
                                                    ]),
            ShipmentExpandsEnum::EVENTS              => $this->whenLoaded(ShipmentExpandsEnum::EVENTS, fn() => [
                                                        'object' => ResourceObjectEnum::EVENT_LIST,
                                                        'data'   => EventResource::collection($this->{ShipmentExpandsEnum::EVENTS})
                                                    ]),
            ShipmentExpandsEnum::LEGS                => $this->whenLoaded(ShipmentExpandsEnum::LEGS, fn() => [
                                                        'object' => ResourceObjectEnum::LEG_LIST,
                                                        'data'   => LegResource::collection($this->{ShipmentExpandsEnum::LEGS})
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
