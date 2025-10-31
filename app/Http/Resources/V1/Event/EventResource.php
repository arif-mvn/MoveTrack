<?php

namespace App\Http\Resources\V1\Event;

use App\Enums\ResourceObjectEnum;
use App\Enums\Event\EventFieldsEnum;
use App\Enums\Event\EventExpandsEnum;
use App\Http\Resources\V1\Source\SourceResource;
use App\Http\Resources\V1\Leg\LegResource;
use App\Http\Resources\V1\Shipment\ShipmentResource;
use App\Http\Resources\V1\SourceEvent\SourceEventResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * @return array<string, mixed>
     */
    public function prepare(Request $request): array
    {
        return [
            'object'                               => ResourceObjectEnum::EVENT,
            EventFieldsEnum::ID                    => $this->{EventFieldsEnum::ID},
            EventFieldsEnum::SHIPMENT_ID           => $this->{EventFieldsEnum::SHIPMENT_ID},
            EventFieldsEnum::LEG_ID                => $this->{EventFieldsEnum::LEG_ID},
            EventFieldsEnum::SOURCE_ID             => $this->{EventFieldsEnum::SOURCE_ID},
            EventFieldsEnum::TYPE                  => $this->{EventFieldsEnum::TYPE},
            EventFieldsEnum::OCCURRED_AT           => optional($this->{EventFieldsEnum::OCCURRED_AT}),
            // Alias to avoid collision with expansion key 'source'
            'source_kind'                          => $this->{EventFieldsEnum::SOURCE_KIND},
            EventFieldsEnum::STATUS_CODE           => $this->{EventFieldsEnum::STATUS_CODE},
            EventFieldsEnum::RAW_TEXT              => $this->{EventFieldsEnum::RAW_TEXT},
            EventFieldsEnum::LOCATION              => $this->{EventFieldsEnum::LOCATION},
            EventFieldsEnum::ACTOR                 => $this->{EventFieldsEnum::ACTOR},
            EventFieldsEnum::EVIDENCE              => $this->{EventFieldsEnum::EVIDENCE},
            EventFieldsEnum::VISIBILITY            => $this->{EventFieldsEnum::VISIBILITY},
            EventFieldsEnum::AUTHORITATIVE         => (bool) $this->{EventFieldsEnum::AUTHORITATIVE},
            EventFieldsEnum::CREATED_AT            => optional($this->{EventFieldsEnum::CREATED_AT}),
            EventFieldsEnum::UPDATED_AT            => optional($this->{EventFieldsEnum::UPDATED_AT}),
            // Expansions
            EventExpandsEnum::SOURCE               => $this->whenLoaded(EventExpandsEnum::SOURCE,
                                                    fn() => new SourceResource($this->{EventExpandsEnum::SOURCE})),
            EventExpandsEnum::LEG                  => $this->whenLoaded(EventExpandsEnum::LEG,
                                                    fn() => new LegResource($this->{EventExpandsEnum::LEG})),
            EventExpandsEnum::SHIPMENT             => $this->whenLoaded(EventExpandsEnum::SHIPMENT,
                                                    fn() => new ShipmentResource($this->{EventExpandsEnum::SHIPMENT})),
            EventExpandsEnum::SOURCE_EVENTS        => $this->whenLoaded(EventExpandsEnum::SOURCE_EVENTS, fn() => [
                'object' => ResourceObjectEnum::SOURCE_EVENT_LIST,
                'data'   => SourceEventResource::collection($this->{EventExpandsEnum::SOURCE_EVENTS})
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
