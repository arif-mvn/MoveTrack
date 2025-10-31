<?php
namespace App\Http\Resources\V1\WebhookDelivery;

use App\Enums\ResourceObjectEnum;
use App\Enums\WebhookDelivery\WebhookDeliveryFieldsEnum;
use App\Enums\WebhookDelivery\WebhookDeliveryExpandsEnum;
use App\Http\Resources\V1\Source\SourceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class WebhookDeliveryResource extends JsonResource
{
    public function prepare(Request $request): array
    {
        return [
            'object'                              => ResourceObjectEnum::WEBHOOK_DELIVERY,
            WebhookDeliveryFieldsEnum::ID         => $this->{WebhookDeliveryFieldsEnum::ID},
            WebhookDeliveryFieldsEnum::SOURCE_ID  => $this->{WebhookDeliveryFieldsEnum::SOURCE_ID},
            WebhookDeliveryFieldsEnum::SIGNATURE  => $this->{WebhookDeliveryFieldsEnum::SIGNATURE},
            WebhookDeliveryFieldsEnum::REQUEST_BODY => $this->{WebhookDeliveryFieldsEnum::REQUEST_BODY},
            WebhookDeliveryFieldsEnum::STATUS     => $this->{WebhookDeliveryFieldsEnum::STATUS},
            WebhookDeliveryFieldsEnum::ERROR      => $this->{WebhookDeliveryFieldsEnum::ERROR},
            WebhookDeliveryFieldsEnum::RECEIVED_AT=> optional($this->{WebhookDeliveryFieldsEnum::RECEIVED_AT}),
            WebhookDeliveryFieldsEnum::PROCESSED_AT=> optional($this->{WebhookDeliveryFieldsEnum::PROCESSED_AT}),
            WebhookDeliveryFieldsEnum::CREATED_AT => optional($this->{WebhookDeliveryFieldsEnum::CREATED_AT}),
            WebhookDeliveryFieldsEnum::UPDATED_AT => optional($this->{WebhookDeliveryFieldsEnum::UPDATED_AT}),
            // Expansions
            WebhookDeliveryExpandsEnum::SOURCE    => $this->whenLoaded(WebhookDeliveryExpandsEnum::SOURCE,
                                                    fn() => new SourceResource($this->{WebhookDeliveryExpandsEnum::SOURCE})),
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
