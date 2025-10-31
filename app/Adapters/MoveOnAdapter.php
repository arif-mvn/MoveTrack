<?php

namespace App\Adapters;

use App\Enums\Event\EventFiltersEnum;
use App\Enums\Event\EventTypeEnum;
use App\Models\SourceEvent;
use Illuminate\Support\Arr;

class MoveOnAdapter implements ICourierAdapter
{
    /**
     * Map MoveOn webhook payload → array of canonical events.
     * Expected $se->payload similar to your “tracking events” list.
     */
    public static function map(SourceEvent $se): array
    {
        $raw        = $se->payload;
        $sourceId   = $se->source_id;     // MoveOn Source ID
        $shipmentId = $se->shipment_id;

        $events = Arr::get($raw, 'data.tracking_events.data', []);
        if (!is_array($events)) $events = [];

        $rows = [];

        foreach ($events as $ev) {
            $label   = (string) Arr::get($ev, 'label', '');
            $message = (string) Arr::get($ev, 'message', '');
            $occur   = static::t(Arr::get($ev, 'created_at'));

            // Map MoveOn labels → canonical event types
            [$type, $statusCode] = match (true) {
                str_contains($label, 'Buy product created')                  => [EventTypeEnum::PROCUREMENT_IN_PROGRESS,       'buy_created'],
                str_contains($label, 'Buy product approved')                 => [EventTypeEnum::PROCUREMENT_IN_PROGRESS,       'buy_approved'],
                str_contains($label, 'Buy product is processing')            => [EventTypeEnum::PROCUREMENT_IN_PROGRESS,       'buy_processing'],
                str_contains($label, 'Buy product successfully purchased')   => [EventTypeEnum::PROCUREMENT_IN_PROGRESS,       'buy_purchased'],
                str_contains($label, 'handover to shipping')                 => [EventTypeEnum::READY_FOR_TRANSPORT,           'buy_handover_to_shipping'],
                str_contains($label, 'arrived at warehouse')                 => [EventTypeEnum::ARRIVED_ORIGIN_WAREHOUSE,      'arrived_origin_wh'],
                str_contains($label, 'Delivery request created')             => [EventTypeEnum::DELIVERY_REQUEST_CREATED,      'delivery_request_created'],
                str_contains($label, 'Delivery request is processing')       => [EventTypeEnum::DELIVERY_PROCESSING,           'delivery_processing'],
                str_contains($label, 'ready')                                 => [EventTypeEnum::DELIVERY_READY,                'delivery_ready'],
                str_contains($label, 'has been shipped')                     => [EventTypeEnum::DELIVERY_SHIPPED,              'delivery_shipped'],
                str_contains($label, 'successfully delivered')               => [EventTypeEnum::DELIVERED,                     'delivered'],
                default                                                       => [null, null],
            };

            if (!$type || !$occur) {
                continue;
            }

            $rows[] = [
                EventFiltersEnum::SHIPMENT_ID => $shipmentId,
                EventFiltersEnum::SOURCE_ID   => $sourceId,
                EventFiltersEnum::TYPE        => $type,
                EventFiltersEnum::OCCURRED_AT => $occur,
                EventFiltersEnum::SOURCE_KIND => 'internal',
                EventFiltersEnum::STATUS_CODE => $statusCode,
                EventFiltersEnum::RAW_TEXT    => $message ?: $label,
            ];
        }

        return $rows;
    }

    /**
     * Pull (optional) — internal systems usually POST to us.
     * Here we keep signature to match the job flow.
     */
    public function pull(int $shipmentId): array
    {
        // Typically not needed for internal (MoveOn will push webhooks).
        // Return an empty batch to keep method compatibility.
        return [[], 'moveon-webhook-only'];
    }

    /** Normalize ISO8601 or MySQL datetime to 'Y-m-d H:i:s' */
    private static function t(?string $dt): ?string
    {
        if (!$dt) return null;
        try { return date('Y-m-d H:i:s', strtotime($dt)); } catch (\Throwable) { return null; }
    }
}
