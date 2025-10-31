<?php

namespace App\Adapters;

use App\Enums\Event\EventFiltersEnum;
use App\Enums\Event\EventTypeEnum;
use App\Models\SourceEvent;
use Illuminate\Support\Arr;

class IMileAdapter implements ICourierAdapter
{
    /**
     * Map iMile webhook/pull payload to canonical events.
     * Expect payload shape similar to iMile’s standard:
     * [
     *   "trackList" => [
     *      ["status" => "...", "time" => "...", "desc" => "...", "station"=>"..."],
     *      ...
     *   ]
     * ]
     */
    public static function map(SourceEvent $se): array
    {
        $raw        = $se->payload;
        $sourceId   = $se->source_id;
        $shipmentId = $se->shipment_id;

        $list = Arr::get($raw, 'trackList', []);
        if (!is_array($list)) $list = [];

        $rows = [];
        foreach ($list as $item) {
            $text  = (string) ($item['desc'] ?? $item['status'] ?? '');
            $occur = static::t($item['time'] ?? null);

            [$type, $code] = match (true) {
                str_contains($text, 'Picked up') || str_contains($text, 'Received at facility')
                => [EventTypeEnum::DELIVERY_PROCESSING, 'received'],
                str_contains($text, 'In transit') || str_contains($text, 'Transferred')
                => [EventTypeEnum::DELIVERY_PROCESSING, 'in_transit'],
                str_contains($text, 'Dispatch task assigned') || str_contains($text, 'Assigned to courier')
                => [EventTypeEnum::OUT_FOR_DELIVERY_ASSIGNED, 'ofd_assigned'],
                str_contains($text, 'Out for delivery') || str_contains($text, 'Shipment out for delivery')
                => [EventTypeEnum::OUT_FOR_DELIVERY_STARTED, 'ofd'],
                str_contains($text, 'Delivery failed') || str_contains($text, 'Attempted delivery')
                => [EventTypeEnum::DELIVERY_FAILED, 'failed'],
                str_contains($text, 'Returned to sender') || str_contains($text, 'Return initiated')
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

    /**
     * Pull by last-mile tracking number (identifier scope 'last_mile', carrier 'imile').
     * Return [$rawPayload, $providerEventId].
     */
    public function pull(int $shipmentId): array
    {
        // TODO: call iMile API using credentials from sources table.
        return [[], 'imile-pull-placeholder'];
    }

    private static function t(?string $dt): ?string
    {
        if (!$dt) return null;
        try { return date('Y-m-d H:i:s', strtotime($dt)); } catch (\Throwable) { return null; }
    }
}
