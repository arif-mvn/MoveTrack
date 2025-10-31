<?php

namespace App\Adapters;

use App\Enums\Event\EventFiltersEnum;
use App\Enums\Event\EventTypeEnum;
use App\Models\SourceEvent;
use Illuminate\Support\Arr;

class SteadfastAdapter implements ICourierAdapter
{
    /**
     * Map Steadfast payload to canonical events.
     * Example expected payload (simplified):
     * [
     *   "tracking" => [
     *     ["status"=>"Picked", "time"=>"...", "note"=>"..."],
     *     ["status"=>"In transit", ...],
     *     ["status"=>"Out for delivery", ...],
     *     ["status"=>"Delivered", ...],
     *   ]
     * ]
     */
    public static function map(SourceEvent $se): array
    {
        $raw        = $se->payload;
        $sourceId   = $se->source_id;
        $shipmentId = $se->shipment_id;

        $list = Arr::get($raw, 'tracking', []);
        if (!is_array($list)) $list = [];

        $rows = [];
        foreach ($list as $st) {
            $status = (string) ($st['status'] ?? '');
            $text   = (string) ($st['note'] ?? $status);
            $occur  = static::t($st['time'] ?? null);

            [$type, $code] = match (true) {
                str_contains($status, 'Picked') || str_contains($status, 'Received')
                => [EventTypeEnum::DELIVERY_PROCESSING, 'received'],
                str_contains($status, 'In transit')
                => [EventTypeEnum::DELIVERY_PROCESSING, 'in_transit'],
                str_contains($status, 'Out for delivery')
                => [EventTypeEnum::OUT_FOR_DELIVERY_STARTED, 'ofd'],
                str_contains($status, 'Delivery failed') || str_contains($status, 'Hold')
                => [EventTypeEnum::DELIVERY_FAILED, 'failed'],
                str_contains($status, 'Returned')
                => [EventTypeEnum::DELIVERY_RETURNED, 'returned'],
                str_contains($status, 'Delivered')
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
        // TODO: call Steadfast API with AWB from identifiers.
        return [[], 'steadfast-pull-placeholder'];
    }

    private static function t(?string $dt): ?string
    {
        if (!$dt) return null;
        try { return date('Y-m-d H:i:s', strtotime($dt)); } catch (\Throwable) { return null; }
    }
}
