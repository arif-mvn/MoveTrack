<?php

namespace App\Adapters;

use App\Enums\Event\EventFiltersEnum;
use App\Enums\Event\EventTypeEnum;
use App\Models\SourceEvent;
use Illuminate\Support\Arr;

class RedxAdapter implements ICourierAdapter
{
    /**
     * Map Redx payload to canonical events.
     * Example expected payload (simplified):
     * [
     *   "events" => [
     *     ["description"=>"Picked by courier", "event_time"=>"..."],
     *     ["description"=>"In transit to hub", "event_time"=>"..."],
     *     ["description"=>"Out for delivery", "event_time"=>"..."],
     *     ["description"=>"Delivered", "event_time"=>"..."],
     *   ]
     * ]
     */
    public static function map(SourceEvent $se): array
    {
        $raw        = $se->payload;
        $sourceId   = $se->source_id;
        $shipmentId = $se->shipment_id;

        $list = Arr::get($raw, 'events', []);
        if (!is_array($list)) $list = [];

        $rows = [];
        foreach ($list as $ev) {
            $text  = (string) ($ev['description'] ?? '');
            $occur = static::t($ev['event_time'] ?? null);

            [$type, $code] = match (true) {
                str_contains($text, 'Picked') || str_contains($text, 'Received')
                => [EventTypeEnum::DELIVERY_PROCESSING, 'received'],
                str_contains($text, 'Transit') || str_contains($text, 'Hub')
                => [EventTypeEnum::DELIVERY_PROCESSING, 'in_transit'],
                str_contains($text, 'Out for delivery')
                => [EventTypeEnum::OUT_FOR_DELIVERY_STARTED, 'ofd'],
                str_contains($text, 'Delivery failed') || str_contains($text, 'Attempted')
                => [EventTypeEnum::DELIVERY_FAILED, 'failed'],
                str_contains($text, 'Returned')
                => [EventTypeEnum::DELIVERY_RETURNED, 'returned'],
                str_contains($text, 'Delivered')
                => [EventTypeEnum::DELIVERED, 'delivered'],
                default
                => [null, null],
            };

            if (!$type || !$occur) continue;

            $rows[] = [
                EventFiltersEnum::SHIPMENT_ID => $shipmentId,
                EventFiltersEnum::SOURCE_ID   => $sourceId,
                EventFiltersEnum::TYPE        => $type,
                EventFiltersEnum::OCCURRED_AT => $occur,
                EventFiltersEnum::SOURCE_KIND => 'courier',
                EventFiltersEnum::STATUS_CODE => $code,
                EventFiltersEnum::RAW_TEXT    => $text,
            ];
        }

        return $rows;
    }

    public function pull(int $shipmentId): array
    {
        // TODO: call Redx API with tracking code from identifiers.
        return [[], 'redx-pull-placeholder'];
    }

    private static function t(?string $dt): ?string
    {
        if (!$dt) return null;
        try { return date('Y-m-d H:i:s', strtotime($dt)); } catch (\Throwable) { return null; }
    }
}
