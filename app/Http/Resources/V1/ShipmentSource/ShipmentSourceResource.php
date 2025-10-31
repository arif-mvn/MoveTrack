<?php
namespace App\Http\Resources\V1\ShipmentSource;

use App\Enums\ResourceObjectEnum;
use App\Enums\ShipmentSource\ShipmentSourceFieldsEnum;
use App\Enums\ShipmentSource\ShipmentSourceExpandsEnum;
use App\Http\Resources\V1\Shipment\ShipmentResource;
use App\Http\Resources\V1\Source\SourceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class ShipmentSourceResource extends JsonResource
{
    public function prepare(Request $request): array
    {
        return [
            'object'                                => ResourceObjectEnum::SHIPMENT_SOURCE,
            ShipmentSourceFieldsEnum::ID            => $this->{ShipmentSourceFieldsEnum::ID},
            ShipmentSourceFieldsEnum::SHIPMENT_ID   => $this->{ShipmentSourceFieldsEnum::SHIPMENT_ID},
            ShipmentSourceFieldsEnum::SOURCE_ID     => $this->{ShipmentSourceFieldsEnum::SOURCE_ID},
            ShipmentSourceFieldsEnum::LAST_SYNCED_AT=> optional($this->{ShipmentSourceFieldsEnum::LAST_SYNCED_AT}),
            ShipmentSourceFieldsEnum::CREATED_AT    => optional($this->{ShipmentSourceFieldsEnum::CREATED_AT}),
            ShipmentSourceFieldsEnum::UPDATED_AT    => optional($this->{ShipmentSourceFieldsEnum::UPDATED_AT}),
            // Expansions
            ShipmentSourceExpandsEnum::SHIPMENT     => $this->whenLoaded(ShipmentSourceExpandsEnum::SHIPMENT,
                                                        fn() => new ShipmentResource($this->{ShipmentSourceExpandsEnum::SHIPMENT})),
            ShipmentSourceExpandsEnum::SOURCE       => $this->whenLoaded(ShipmentSourceExpandsEnum::SOURCE,
                                                        fn() => new SourceResource($this->{ShipmentSourceExpandsEnum::SOURCE})),
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
