<?php

namespace Database\Seeders;

use App\Enums\Event\EventFieldsEnum;
use App\Enums\Event\EventTypeEnum;
use App\Enums\Identifier\IdentifierFieldsEnum;
use App\Enums\Identifier\IdentifierScopeEnum;
use App\Enums\Leg\LegFieldsEnum;
use App\Enums\Leg\LegTypeEnum;
use App\Enums\Shipment\ShipmentFieldsEnum;
use App\Enums\Source\SourceCodeEnum;
use App\Enums\SourceEvent\SourceEventFieldsEnum;
use App\Enums\WebhookDelivery\WebhookDeliveryFieldsEnum;
use App\Models\Event;
use App\Models\Identifier;
use App\Models\Leg;
use App\Models\Shipment;
use App\Models\ShipmentSource;
use App\Models\Source;
use App\Models\SourceEvent;
use App\Models\WebhookDelivery;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class ShipmentSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // Resolve sources
        $srcMoveOn   = Source::query()->where('code', SourceCodeEnum::MOVEON)->firstOrFail();
        $srcYun      = Source::query()->where('code', SourceCodeEnum::YUN_EXPRESS)->first();
        $srcIMile    = Source::query()->where('code', SourceCodeEnum::IMILE)->first();
        $srcSteadfast= Source::query()->where('code', SourceCodeEnum::STEADFAST)->first();

        /** =========================================================
         *  Shipment A: International (CN → AU) — MoveOn + YunExpress + iMile
         *  ========================================================= */
        $shipA = Shipment::query()->updateOrCreate(
            [ShipmentFieldsEnum::ID => 1001], // fixed ID for stable seeds; remove if auto
            [
                ShipmentFieldsEnum::MODE             => 'international',
                ShipmentFieldsEnum::CURRENT_STATUS   => 'DELIVERED', // final after MoveOn confirms
                ShipmentFieldsEnum::STATUS_SOURCE_ID => $srcMoveOn->id,
                ShipmentFieldsEnum::DELIVERED_AT     => '2025-10-01 12:30:21', // MoveOn authoritative time
                ShipmentFieldsEnum::DELIVERED_AT_COURIER => '2025-10-01 11:30:21', // YunExpress earlier
                ShipmentFieldsEnum::STATUS_DISCREPANCY => false, // resolved after MoveOn confirmed
                ShipmentFieldsEnum::LAST_EVENT_AT    => '2025-10-01 12:30:21',
                ShipmentFieldsEnum::LAST_SYNCED_AT   => $now->copy()->subHours(2)->toDateTimeString(),
                ShipmentFieldsEnum::SUMMARY_TIMESTAMPS => [
                    'procurement_started_at' => '2025-09-07 15:16:55',
                    'linehaul_departed_at'   => '2025-09-27 01:03:00',
                    'arrived_dest_airport'   => '2025-09-27 13:00:00',
                    'handed_to_last_mile'    => '2025-09-28 16:38:00',
                    'delivered_at'           => '2025-10-01 12:30:21',
                ],
            ]
        );

        // identifiers for A (MoveOn PB..., Yun YT..., Last-mile iMile TN...)
        $idsA = [
            [IdentifierFieldsEnum::SCOPE => IdentifierScopeEnum::INTERNAL,  IdentifierFieldsEnum::CARRIER_CODE => SourceCodeEnum::MOVEON,    IdentifierFieldsEnum::VALUE => 'PB092507831664'],
            [IdentifierFieldsEnum::SCOPE => IdentifierScopeEnum::COURIER,   IdentifierFieldsEnum::CARRIER_CODE => SourceCodeEnum::YUN_EXPRESS,IdentifierFieldsEnum::VALUE => 'YT2526300703434402'],
            [IdentifierFieldsEnum::SCOPE => IdentifierScopeEnum::LAST_MILE, IdentifierFieldsEnum::CARRIER_CODE => SourceCodeEnum::IMILE,     IdentifierFieldsEnum::VALUE => '6092325739721'],
        ];
        foreach ($idsA as $row) {
            Identifier::query()->updateOrCreate(
                [
                    IdentifierFieldsEnum::SHIPMENT_ID  => $shipA->id,
                    IdentifierFieldsEnum::SCOPE        => $row[IdentifierFieldsEnum::SCOPE],
                    IdentifierFieldsEnum::CARRIER_CODE => $row[IdentifierFieldsEnum::CARRIER_CODE],
                    IdentifierFieldsEnum::VALUE        => $row[IdentifierFieldsEnum::VALUE],
                ],
                []
            );
        }

        // shipment_sources (MoveOn, Yun, iMile)
        foreach ([$srcMoveOn, $srcYun, $srcIMile] as $src) {
            if (!$src) continue;
            ShipmentSource::query()->updateOrCreate(
                ['shipment_id' => $shipA->id, 'source_id' => $src->id],
                ['last_synced_at' => $now->copy()->subHours(2)->toDateTimeString()]
            );
        }

        // legs for A
        $legA1 = Leg::query()->updateOrCreate(
            [LegFieldsEnum::SHIPMENT_ID => $shipA->id, LegFieldsEnum::TYPE => LegTypeEnum::ORIGIN_PROCUREMENT],
            [LegFieldsEnum::SOURCE_ID => $srcMoveOn->id, LegFieldsEnum::START_AT => '2025-09-07 15:16:55', LegFieldsEnum::END_AT => '2025-09-23 02:56:50']
        );
        $legA2 = Leg::query()->updateOrCreate(
            [LegFieldsEnum::SHIPMENT_ID => $shipA->id, LegFieldsEnum::TYPE => LegTypeEnum::LINEHAUL],
            [LegFieldsEnum::SOURCE_ID => $srcYun?->id, LegFieldsEnum::START_AT => '2025-09-23 03:54:29', LegFieldsEnum::END_AT => '2025-09-27 13:00:00']
        );
        $legA3 = Leg::query()->updateOrCreate(
            [LegFieldsEnum::SHIPMENT_ID => $shipA->id, LegFieldsEnum::TYPE => LegTypeEnum::DESTINATION_WH],
            [LegFieldsEnum::START_AT => '2025-09-27 13:00:00', LegFieldsEnum::END_AT => '2025-09-28 16:38:00']
        );
        $legA4 = Leg::query()->updateOrCreate(
            [LegFieldsEnum::SHIPMENT_ID => $shipA->id, LegFieldsEnum::TYPE => LegTypeEnum::LAST_MILE],
            [LegFieldsEnum::SOURCE_ID => $srcIMile?->id, LegFieldsEnum::START_AT => '2025-09-28 16:38:00', LegFieldsEnum::END_AT => '2025-10-01 12:30:21']
        );

        // events for A (subset of your big examples, canonicalized)
        $evRowsA = [
            // MoveOn procurement
            [EventFieldsEnum::SHIPMENT_ID=>$shipA->id, EventFieldsEnum::LEG_ID=>$legA1->id, EventFieldsEnum::SOURCE_ID=>$srcMoveOn->id, EventFieldsEnum::TYPE=>EventTypeEnum::PROCUREMENT_IN_PROGRESS, EventFieldsEnum::OCCURRED_AT=>'2025-09-07 15:16:55', EventFieldsEnum::SOURCE_KIND=>'internal', EventFieldsEnum::RAW_TEXT=>'Buy product created PB092507831664'],
            [EventFieldsEnum::SHIPMENT_ID=>$shipA->id, EventFieldsEnum::LEG_ID=>$legA1->id, EventFieldsEnum::SOURCE_ID=>$srcMoveOn->id, EventFieldsEnum::TYPE=>EventTypeEnum::PROCUREMENT_APPROVED,     EventFieldsEnum::OCCURRED_AT=>'2025-09-09 06:51:29',  EventFieldsEnum::SOURCE_KIND=>'internal', EventFieldsEnum::RAW_TEXT=>'Buy product approved'],
            [EventFieldsEnum::SHIPMENT_ID=>$shipA->id, EventFieldsEnum::LEG_ID=>$legA1->id, EventFieldsEnum::SOURCE_ID=>$srcMoveOn->id, EventFieldsEnum::TYPE=>EventTypeEnum::PROCUREMENT_PURCHASED,    EventFieldsEnum::OCCURRED_AT=>'2025-09-09 07:15:45',  EventFieldsEnum::SOURCE_KIND=>'internal', EventFieldsEnum::RAW_TEXT=>'Buy product purchased'],
            [EventFieldsEnum::SHIPMENT_ID=>$shipA->id, EventFieldsEnum::LEG_ID=>$legA1->id, EventFieldsEnum::SOURCE_ID=>$srcMoveOn->id, EventFieldsEnum::TYPE=>EventTypeEnum::HANDOVER_TO_SHIPPING,     EventFieldsEnum::OCCURRED_AT=>'2025-09-10 06:00:31',  EventFieldsEnum::SOURCE_KIND=>'internal', EventFieldsEnum::RAW_TEXT=>'Handover to shipping'],

            // YunExpress linehaul & customs
            [EventFieldsEnum::SHIPMENT_ID=>$shipA->id, EventFieldsEnum::LEG_ID=>$legA2->id, EventFieldsEnum::SOURCE_ID=>$srcYun?->id, EventFieldsEnum::TYPE=>EventTypeEnum::READY_FOR_TRANSPORT,        EventFieldsEnum::OCCURRED_AT=>'2025-09-22 15:59:19', EventFieldsEnum::SOURCE_KIND=>'courier', EventFieldsEnum::RAW_TEXT=>'Shipment picked up'],
            [EventFieldsEnum::SHIPMENT_ID=>$shipA->id, EventFieldsEnum::LEG_ID=>$legA2->id, EventFieldsEnum::SOURCE_ID=>$srcYun?->id, EventFieldsEnum::TYPE=>EventTypeEnum::DEPARTED_INTERNATIONAL,     EventFieldsEnum::OCCURRED_AT=>'2025-09-27 01:03:00', EventFieldsEnum::SOURCE_KIND=>'courier', EventFieldsEnum::RAW_TEXT=>'International flight has departed'],
            [EventFieldsEnum::SHIPMENT_ID=>$shipA->id, EventFieldsEnum::LEG_ID=>$legA2->id, EventFieldsEnum::SOURCE_ID=>$srcYun?->id, EventFieldsEnum::TYPE=>EventTypeEnum::CUSTOMS_IMPORT_IN_PROGRESS, EventFieldsEnum::OCCURRED_AT=>'2025-09-27 04:37:22', EventFieldsEnum::SOURCE_KIND=>'courier', EventFieldsEnum::RAW_TEXT=>'In clearance processing - Import'],
            [EventFieldsEnum::SHIPMENT_ID=>$shipA->id, EventFieldsEnum::LEG_ID=>$legA2->id, EventFieldsEnum::SOURCE_ID=>$srcYun?->id, EventFieldsEnum::TYPE=>EventTypeEnum::CUSTOMS_IMPORT_CLEARED,     EventFieldsEnum::OCCURRED_AT=>'2025-09-27 05:45:00', EventFieldsEnum::SOURCE_KIND=>'courier', EventFieldsEnum::RAW_TEXT=>'Clearance processing completed - Import'],
            [EventFieldsEnum::SHIPMENT_ID=>$shipA->id, EventFieldsEnum::LEG_ID=>$legA2->id, EventFieldsEnum::SOURCE_ID=>$srcYun?->id, EventFieldsEnum::TYPE=>EventTypeEnum::ARRIVED_DEST_AIRPORT,       EventFieldsEnum::OCCURRED_AT=>'2025-09-27 13:00:00', EventFieldsEnum::SOURCE_KIND=>'courier', EventFieldsEnum::RAW_TEXT=>'International flight has arrived'],

            // Handover to last mile
            [EventFieldsEnum::SHIPMENT_ID=>$shipA->id, EventFieldsEnum::LEG_ID=>$legA3->id, EventFieldsEnum::SOURCE_ID=>$srcYun?->id, EventFieldsEnum::TYPE=>EventTypeEnum::HANDED_TO_LAST_MILE,        EventFieldsEnum::OCCURRED_AT=>'2025-09-28 16:38:00', EventFieldsEnum::SOURCE_KIND=>'courier', EventFieldsEnum::RAW_TEXT=>'Delivered to local carrier'],

            // iMile last-mile
            [EventFieldsEnum::SHIPMENT_ID=>$shipA->id, EventFieldsEnum::LEG_ID=>$legA4->id, EventFieldsEnum::SOURCE_ID=>$srcIMile?->id, EventFieldsEnum::TYPE=>EventTypeEnum::OUT_FOR_DELIVERY_ASSIGNED, EventFieldsEnum::OCCURRED_AT=>'2025-09-30 21:22:48', EventFieldsEnum::SOURCE_KIND=>'courier', EventFieldsEnum::RAW_TEXT=>'Dispatch task assigned to DA'],
            [EventFieldsEnum::SHIPMENT_ID=>$shipA->id, EventFieldsEnum::LEG_ID=>$legA4->id, EventFieldsEnum::SOURCE_ID=>$srcIMile?->id, EventFieldsEnum::TYPE=>EventTypeEnum::OUT_FOR_DELIVERY_STARTED,  EventFieldsEnum::OCCURRED_AT=>'2025-09-30 21:45:50', EventFieldsEnum::SOURCE_KIND=>'courier', EventFieldsEnum::RAW_TEXT=>'Shipment out for delivery'],
            // Courier-delivered earlier
            [EventFieldsEnum::SHIPMENT_ID=>$shipA->id, EventFieldsEnum::LEG_ID=>$legA4->id, EventFieldsEnum::SOURCE_ID=>$srcIMile?->id, EventFieldsEnum::TYPE=>EventTypeEnum::DELIVERED,                 EventFieldsEnum::OCCURRED_AT=>'2025-10-01 11:30:21', EventFieldsEnum::SOURCE_KIND=>'courier', EventFieldsEnum::RAW_TEXT=>'Delivered.'],
            // Authoritative MoveOn confirms delivered a little later (optional mirror event on last-mile leg or no leg)
            [EventFieldsEnum::SHIPMENT_ID=>$shipA->id, EventFieldsEnum::LEG_ID=>$legA4->id, EventFieldsEnum::SOURCE_ID=>$srcMoveOn->id, EventFieldsEnum::TYPE=>EventTypeEnum::DELIVERED,                 EventFieldsEnum::OCCURRED_AT=>'2025-10-01 12:30:21', EventFieldsEnum::SOURCE_KIND=>'internal', EventFieldsEnum::RAW_TEXT=>'Delivery request successfully delivered'],
        ];

        foreach ($evRowsA as $row) {
            Event::query()->updateOrCreate(
                [
                    EventFieldsEnum::SHIPMENT_ID => $row[EventFieldsEnum::SHIPMENT_ID],
                    EventFieldsEnum::LEG_ID      => $row[EventFieldsEnum::LEG_ID],
                    EventFieldsEnum::TYPE        => $row[EventFieldsEnum::TYPE],
                    EventFieldsEnum::OCCURRED_AT => $row[EventFieldsEnum::OCCURRED_AT],
                ],
                Arr::except($row, [EventFieldsEnum::SHIPMENT_ID, EventFieldsEnum::LEG_ID, EventFieldsEnum::TYPE, EventFieldsEnum::OCCURRED_AT])
            );
        }

        // SourceEvents + WebhookDeliveries (one per provider for the sample)
        if ($srcYun) {
            $wd = WebhookDelivery::query()->updateOrCreate(
                [
                    WebhookDeliveryFieldsEnum::SOURCE_ID   => $srcYun->id,
                    WebhookDeliveryFieldsEnum::RECEIVED_AT => '2025-09-27 04:38:00',
                ],
                [
                    WebhookDeliveryFieldsEnum::STATUS       => 'processed',
                    WebhookDeliveryFieldsEnum::REQUEST_BODY => ['sample'=>'yunexpress payload (fixture)'],
                    WebhookDeliveryFieldsEnum::PROCESSED_AT => '2025-09-27 04:38:10',
                ]
            );
            SourceEvent::query()->updateOrCreate(
                [
                    SourceEventFieldsEnum::SOURCE_ID    => $srcYun->id,
                    SourceEventFieldsEnum::PAYLOAD_HASH => hash('sha256', json_encode(['sample'=>'yunexpress payload (fixture)'])),
                ],
                [
                    SourceEventFieldsEnum::SHIPMENT_ID  => $shipA->id,
                    SourceEventFieldsEnum::EVENT_ID     => Event::query()
                        ->where(EventFieldsEnum::SHIPMENT_ID, $shipA->id)
                        ->where(EventFieldsEnum::TYPE, EventTypeEnum::DELIVERED)
                        ->where(EventFieldsEnum::OCCURRED_AT, '2025-10-01 11:30:21')
                        ->value('id'),
                    SourceEventFieldsEnum::PAYLOAD      => ['sample'=>'yunexpress payload (fixture)'],
                    SourceEventFieldsEnum::RECEIVED_AT  => '2025-09-27 04:38:00',
                    SourceEventFieldsEnum::OCCURRED_AT  => '2025-10-01 11:30:21',
                ]
            );
        }

        if ($srcIMile) {
            $wd = WebhookDelivery::query()->updateOrCreate(
                [
                    WebhookDeliveryFieldsEnum::SOURCE_ID   => $srcIMile->id,
                    WebhookDeliveryFieldsEnum::RECEIVED_AT => '2025-09-30 21:20:00',
                ],
                [
                    WebhookDeliveryFieldsEnum::STATUS       => 'processed',
                    WebhookDeliveryFieldsEnum::REQUEST_BODY => ['sample'=>'imile payload (fixture)'],
                    WebhookDeliveryFieldsEnum::PROCESSED_AT => '2025-09-30 21:20:10',
                ]
            );
            SourceEvent::query()->updateOrCreate(
                [
                    SourceEventFieldsEnum::SOURCE_ID    => $srcIMile->id,
                    SourceEventFieldsEnum::PAYLOAD_HASH => hash('sha256', json_encode(['sample'=>'imile payload (fixture)'])),
                ],
                [
                    SourceEventFieldsEnum::SHIPMENT_ID  => $shipA->id,
                    SourceEventFieldsEnum::EVENT_ID     => Event::query()
                        ->where(EventFieldsEnum::SHIPMENT_ID, $shipA->id)
                        ->where(EventFieldsEnum::TYPE, EventTypeEnum::OUT_FOR_DELIVERY_STARTED)
                        ->where(EventFieldsEnum::OCCURRED_AT, '2025-09-30 21:45:50')
                        ->value('id'),
                    SourceEventFieldsEnum::PAYLOAD      => ['sample'=>'imile payload (fixture)'],
                    SourceEventFieldsEnum::RECEIVED_AT  => '2025-09-30 21:20:00',
                    SourceEventFieldsEnum::OCCURRED_AT  => '2025-09-30 21:45:50',
                ]
            );
        }

        /** =========================================================
         *  Shipment B: Domestic/Line-haul (BD last-mile) — MoveOn + Steadfast
         *  ========================================================= */
        $shipB = Shipment::query()->updateOrCreate(
            [ShipmentFieldsEnum::ID => 1002],
            [
                ShipmentFieldsEnum::MODE             => 'domestic',
                ShipmentFieldsEnum::CURRENT_STATUS   => 'DELIVERED',
                ShipmentFieldsEnum::STATUS_SOURCE_ID => $srcMoveOn->id,
                ShipmentFieldsEnum::DELIVERED_AT     => '2025-09-15 16:40:00',
                ShipmentFieldsEnum::DELIVERED_AT_COURIER => '2025-09-15 16:30:00',
                ShipmentFieldsEnum::STATUS_DISCREPANCY => false,
                ShipmentFieldsEnum::LAST_EVENT_AT    => '2025-09-15 16:40:00',
                ShipmentFieldsEnum::LAST_SYNCED_AT   => $now->copy()->subHours(3)->toDateTimeString(),
                ShipmentFieldsEnum::SUMMARY_TIMESTAMPS => [
                    'origin_procurement' => '2025-09-10 10:00:00',
                    'handed_last_mile'   => '2025-09-14 09:00:00',
                    'delivered_at'       => '2025-09-15 16:40:00',
                ],
            ]
        );

        // identifiers for B
        $idsB = [
            [IdentifierFieldsEnum::SCOPE => IdentifierScopeEnum::INTERNAL,  IdentifierFieldsEnum::CARRIER_CODE => SourceCodeEnum::MOVEON,     IdentifierFieldsEnum::VALUE => 'PB091012345678'],
            [IdentifierFieldsEnum::SCOPE => IdentifierScopeEnum::LAST_MILE, IdentifierFieldsEnum::CARRIER_CODE => SourceCodeEnum::STEADFAST,  IdentifierFieldsEnum::VALUE => 'STF-555-999-000'],
        ];
        foreach ($idsB as $row) {
            Identifier::query()->updateOrCreate(
                [
                    IdentifierFieldsEnum::SHIPMENT_ID  => $shipB->id,
                    IdentifierFieldsEnum::SCOPE        => $row[IdentifierFieldsEnum::SCOPE],
                    IdentifierFieldsEnum::CARRIER_CODE => $row[IdentifierFieldsEnum::CARRIER_CODE],
                    IdentifierFieldsEnum::VALUE        => $row[IdentifierFieldsEnum::VALUE],
                ],
                []
            );
        }

        foreach ([$srcMoveOn, $srcSteadfast] as $src) {
            if (!$src) continue;
            ShipmentSource::query()->updateOrCreate(
                ['shipment_id' => $shipB->id, 'source_id' => $src->id],
                ['last_synced_at' => $now->copy()->subHours(3)->toDateTimeString()]
            );
        }

        $legB1 = Leg::query()->updateOrCreate(
            [LegFieldsEnum::SHIPMENT_ID => $shipB->id, LegFieldsEnum::TYPE => LegTypeEnum::ORIGIN_PROCUREMENT],
            [LegFieldsEnum::SOURCE_ID => $srcMoveOn->id, LegFieldsEnum::START_AT => '2025-09-10 10:00:00', LegFieldsEnum::END_AT => '2025-09-14 09:00:00']
        );
        $legB2 = Leg::query()->updateOrCreate(
            [LegFieldsEnum::SHIPMENT_ID => $shipB->id, LegFieldsEnum::TYPE => LegTypeEnum::LAST_MILE],
            [LegFieldsEnum::SOURCE_ID => $srcSteadfast?->id, LegFieldsEnum::START_AT => '2025-09-14 09:00:00', LegFieldsEnum::END_AT => '2025-09-15 16:40:00']
        );

        $evRowsB = [
            [EventFieldsEnum::SHIPMENT_ID=>$shipB->id, EventFieldsEnum::LEG_ID=>$legB1->id, EventFieldsEnum::SOURCE_ID=>$srcMoveOn->id, EventFieldsEnum::TYPE=>EventTypeEnum::PROCUREMENT_IN_PROGRESS, EventFieldsEnum::OCCURRED_AT=>'2025-09-10 10:00:00', EventFieldsEnum::SOURCE_KIND=>'internal', EventFieldsEnum::RAW_TEXT=>'Buy product created'],
            [EventFieldsEnum::SHIPMENT_ID=>$shipB->id, EventFieldsEnum::LEG_ID=>$legB1->id, EventFieldsEnum::SOURCE_ID=>$srcMoveOn->id, EventFieldsEnum::TYPE=>EventTypeEnum::HANDOVER_TO_SHIPPING,     EventFieldsEnum::OCCURRED_AT=>'2025-09-14 09:00:00', EventFieldsEnum::SOURCE_KIND=>'internal', EventFieldsEnum::RAW_TEXT=>'Handover to courier'],
            [EventFieldsEnum::SHIPMENT_ID=>$shipB->id, EventFieldsEnum::LEG_ID=>$legB2->id, EventFieldsEnum::SOURCE_ID=>$srcSteadfast?->id, EventFieldsEnum::TYPE=>EventTypeEnum::OUT_FOR_DELIVERY_STARTED, EventFieldsEnum::OCCURRED_AT=>'2025-09-15 12:30:00', EventFieldsEnum::SOURCE_KIND=>'courier', EventFieldsEnum::RAW_TEXT=>'Shipment out for delivery'],
            [EventFieldsEnum::SHIPMENT_ID=>$shipB->id, EventFieldsEnum::LEG_ID=>$legB2->id, EventFieldsEnum::SOURCE_ID=>$srcSteadfast?->id, EventFieldsEnum::TYPE=>EventTypeEnum::DELIVERED,               EventFieldsEnum::OCCURRED_AT=>'2025-09-15 16:30:00', EventFieldsEnum::SOURCE_KIND=>'courier', EventFieldsEnum::RAW_TEXT=>'Delivered'],
            [EventFieldsEnum::SHIPMENT_ID=>$shipB->id, EventFieldsEnum::LEG_ID=>$legB2->id, EventFieldsEnum::SOURCE_ID=>$srcMoveOn->id,     EventFieldsEnum::TYPE=>EventTypeEnum::DELIVERED,               EventFieldsEnum::OCCURRED_AT=>'2025-09-15 16:40:00', EventFieldsEnum::SOURCE_KIND=>'internal', EventFieldsEnum::RAW_TEXT=>'MoveOn confirms delivered'],
        ];
        foreach ($evRowsB as $row) {
            Event::query()->updateOrCreate(
                [
                    EventFieldsEnum::SHIPMENT_ID => $row[EventFieldsEnum::SHIPMENT_ID],
                    EventFieldsEnum::LEG_ID      => $row[EventFieldsEnum::LEG_ID],
                    EventFieldsEnum::TYPE        => $row[EventFieldsEnum::TYPE],
                    EventFieldsEnum::OCCURRED_AT => $row[EventFieldsEnum::OCCURRED_AT],
                ],
                Arr::except($row, [EventFieldsEnum::SHIPMENT_ID, EventFieldsEnum::LEG_ID, EventFieldsEnum::TYPE, EventFieldsEnum::OCCURRED_AT])
            );
        }

        if ($srcSteadfast) {
            WebhookDelivery::query()->updateOrCreate(
                [
                    WebhookDeliveryFieldsEnum::SOURCE_ID   => $srcSteadfast->id,
                    WebhookDeliveryFieldsEnum::RECEIVED_AT => '2025-09-15 12:20:00',
                ],
                [
                    WebhookDeliveryFieldsEnum::STATUS       => 'processed',
                    WebhookDeliveryFieldsEnum::REQUEST_BODY => ['sample'=>'steadfast payload (fixture)'],
                    WebhookDeliveryFieldsEnum::PROCESSED_AT => '2025-09-15 12:20:10',
                ]
            );
            SourceEvent::query()->updateOrCreate(
                [
                    SourceEventFieldsEnum::SOURCE_ID    => $srcSteadfast->id,
                    SourceEventFieldsEnum::PAYLOAD_HASH => hash('sha256', json_encode(['sample'=>'steadfast payload (fixture)'])),
                ],
                [
                    SourceEventFieldsEnum::SHIPMENT_ID  => $shipB->id,
                    SourceEventFieldsEnum::EVENT_ID     => Event::query()
                        ->where(EventFieldsEnum::SHIPMENT_ID, $shipB->id)
                        ->where(EventFieldsEnum::TYPE, EventTypeEnum::OUT_FOR_DELIVERY_STARTED)
                        ->where(EventFieldsEnum::OCCURRED_AT, '2025-09-15 12:30:00')
                        ->value('id'),
                    SourceEventFieldsEnum::PAYLOAD      => ['sample'=>'steadfast payload (fixture)'],
                    SourceEventFieldsEnum::RECEIVED_AT  => '2025-09-15 12:20:00',
                    SourceEventFieldsEnum::OCCURRED_AT  => '2025-09-15 12:30:00',
                ]
            );
        }
    }
}
