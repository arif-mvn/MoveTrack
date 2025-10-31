<?php

namespace App\Adapters;

use App\Enums\Event\EventFiltersEnum;
use App\Enums\Event\EventTypeEnum;
use App\Models\SourceEvent;
use Illuminate\Support\Arr;

class YunExpressAdapter implements ICourierAdapter
{
    public static function map(SourceEvent $se): array
    {
        $raw        = $se->payload;
        $sourceId   = $se->source_id;
        $shipmentId = $se->shipment_id;

        $details = Arr::get($raw, 'ResultList.0.TrackInfo.TrackEventDetails', []);
        if (!is_array($details)) $details = [];

        $rows = [];
        foreach ($details as $d) {
            $occur = static::t(Arr::get($d, 'CreatedOn') ?? Arr::get($d, 'ProcessDate'));
            $text  = (string) Arr::get($d, 'ProcessContent', '');

            [$type, $code] = match (true) {
                str_contains($text, 'Shipment picked up')                      => [EventTypeEnum::READY_FOR_TRANSPORT,        'picked_up'],
                str_contains($text, 'Departed from sort facility')             => [EventTypeEnum::READY_FOR_TRANSPORT,        'depart_sort'],
                str_contains($text, 'International flight has departed')       => [EventTypeEnum::DEPARTED_INTERNATIONAL,     'departed_intl'],
                str_contains($text, 'In clearance processing - Import')        => [EventTypeEnum::CUSTOMS_IMPORT_IN_PROGRESS, 'customs_processing'],
                str_contains($text, 'Clearance processing completed - Import') => [EventTypeEnum::CUSTOMS_IMPORT_CLEARED,     'customs_cleared'],
                str_contains($text, 'International flight has arrived')        => [EventTypeEnum::ARRIVED_DEST_AIRPORT,       'arrived_intl'],
                str_contains($text, 'Delivered to local carrier')              => [EventTypeEnum::HANDED_TO_LAST_MILE,        'to_last_mile'],
                str_contains($text, 'Shipment out for delivery')               => [EventTypeEnum::OUT_FOR_DELIVERY_STARTED,   'ofd'],
                str_contains($text, 'Delivered')                                => [EventTypeEnum::DELIVERED,                  'delivered'],
                default                                                         => [null, null],
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
     * Pull the latest tracking JSON by identifier:
     * - Prefer identifier scope 'courier' + carrier_code 'YunExpress'
     * Return [$rawPayload, $providerEventId].
     */
    public function pull(int $shipmentId): array
    {
        // TODO: Use credentials from sources table (config/credentials_encrypted) and hit the real API.
        // For now we return an empty payload; your job handles no-op safely.
        return [[], 'yunexpress-pull-placeholder'];
    }

    private static function t(?string $dt): ?string
    {
        if (!$dt) return null;
        try { return date('Y-m-d H:i:s', strtotime($dt)); } catch (\Throwable) { return null; }
    }
}
