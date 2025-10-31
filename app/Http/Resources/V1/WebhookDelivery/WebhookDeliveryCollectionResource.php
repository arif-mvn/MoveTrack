<?php
namespace App\Http\Resources\V1\WebhookDelivery;

use App\Enums\ResourceObjectEnum;
use MoveOn\Core\Http\Resources\BaseCollectionResource;

class WebhookDeliveryCollectionResource extends BaseCollectionResource
{
    public function toArray($request): array
    {
        return [
            'object' => ResourceObjectEnum::WEBHOOK_DELIVERY_LIST,
            'data'   => WebhookDeliveryResource::collection($this->items()),
        ];
    }
}

