<?php

namespace App\Http\Resources\V1\Source;

use App\Enums\ResourceObjectEnum;
use App\Enums\Source\SourceFieldsEnum;
use App\Enums\Source\SourceExpandsEnum;
use App\Http\Resources\V1\Shipment\ShipmentResource;
use App\Http\Resources\V1\Leg\LegResource;
use App\Http\Resources\V1\Event\EventResource;
use App\Http\Resources\V1\SourceEvent\SourceEventResource;
use App\Http\Resources\V1\ShipmentSource\ShipmentSourceResource;
use App\Http\Resources\V1\WebhookDelivery\WebhookDeliveryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class SourceResource extends JsonResource
{
    public function prepare(Request $request): array
    {
        return [
            'object'                                 => ResourceObjectEnum::SOURCE,
            SourceFieldsEnum::ID                     => $this->{SourceFieldsEnum::ID},
            SourceFieldsEnum::CODE                   => $this->{SourceFieldsEnum::CODE},
            SourceFieldsEnum::NAME                   => $this->{SourceFieldsEnum::NAME},
            SourceFieldsEnum::TYPE                   => $this->{SourceFieldsEnum::TYPE},
            SourceFieldsEnum::CONFIG                 => $this->{SourceFieldsEnum::CONFIG},
            SourceFieldsEnum::CREDENTIALS_ENCRYPTED  => $this->{SourceFieldsEnum::CREDENTIALS_ENCRYPTED},
            SourceFieldsEnum::WEBHOOK_SECRET         => $this->{SourceFieldsEnum::WEBHOOK_SECRET},
            SourceFieldsEnum::ENABLED                => (bool) $this->{SourceFieldsEnum::ENABLED},
            SourceFieldsEnum::CREATED_AT             => optional($this->{SourceFieldsEnum::CREATED_AT}),
            SourceFieldsEnum::UPDATED_AT             => optional($this->{SourceFieldsEnum::UPDATED_AT}),
            SourceFieldsEnum::DELETED_AT             => optional($this->{SourceFieldsEnum::DELETED_AT}),
            // Expansions
            SourceExpandsEnum::SHIPMENTS             => $this->whenLoaded(SourceExpandsEnum::SHIPMENTS, fn() => [
                                                        'object' => ResourceObjectEnum::SHIPMENT_LIST,
                                                        'data'   => ShipmentResource::collection($this->{SourceExpandsEnum::SHIPMENTS})
                                                    ]),
            SourceExpandsEnum::LEGS                  => $this->whenLoaded(SourceExpandsEnum::LEGS, fn() => [
                                                        'object' => ResourceObjectEnum::LEG_LIST,
                                                        'data'   => LegResource::collection($this->{SourceExpandsEnum::LEGS})
                                                    ]),
            SourceExpandsEnum::EVENTS                => $this->whenLoaded(SourceExpandsEnum::EVENTS, fn() => [
                                                        'object' => ResourceObjectEnum::EVENT_LIST,
                                                        'data'   => EventResource::collection($this->{SourceExpandsEnum::EVENTS})
                                                    ]),
            SourceExpandsEnum::SOURCE_EVENTS         => $this->whenLoaded(SourceExpandsEnum::SOURCE_EVENTS, fn() => [
                                                        'object' => ResourceObjectEnum::SOURCE_EVENT_LIST,
                                                        'data'   => SourceEventResource::collection($this->{SourceExpandsEnum::SOURCE_EVENTS})
                                                    ]),
            SourceExpandsEnum::SHIPMENT_SOURCES      => $this->whenLoaded(SourceExpandsEnum::SHIPMENT_SOURCES, fn() => [
                                                        'object' => ResourceObjectEnum::SHIPMENT_SOURCE_LIST,
                                                        'data'   => ShipmentSourceResource::collection($this->{SourceExpandsEnum::SHIPMENT_SOURCES})
                                                    ]),
            SourceExpandsEnum::WEBHOOK_DELIVERIES    => $this->whenLoaded(SourceExpandsEnum::WEBHOOK_DELIVERIES, fn() => [
                                                        'object' => ResourceObjectEnum::WEBHOOK_DELIVERY_LIST,
                                                        'data'   => WebhookDeliveryResource::collection($this->{SourceExpandsEnum::WEBHOOK_DELIVERIES})
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
        if (is_string($this->resource)) {
            return [];
        }
        $result = $this->prepare($request);
        if ($request->has('fields')) {
            $result = Arr::only($result, array_map('trim', explode(',', $request->get('fields'))));
        }
        return Arr::where($result, fn($v) => $v !== null);
    }
}
