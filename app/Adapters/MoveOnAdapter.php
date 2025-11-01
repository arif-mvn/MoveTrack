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
        $raw = $se->payload;
        $sourceId = $se->source_id;     // MoveOn Source ID
        $shipmentId = $se->shipment_id;

        $events = Arr::get($raw, 'data.tracking_events.data', []);
        if (!is_array($events)) $events = [];

        $rows = [];

        foreach ($events as $ev) {
            $label = (string)Arr::get($ev, 'label', '');
            $message = (string)Arr::get($ev, 'message', '');
            $occur = static::t(Arr::get($ev, 'created_at'));

            // Map MoveOn labels → canonical event types
            [$type, $statusCode] = match (true) {
                str_contains($label, 'Buy product created') => [EventTypeEnum::PROCUREMENT_IN_PROGRESS, 'buy_created'],
                str_contains($label, 'Buy product approved') => [EventTypeEnum::PROCUREMENT_IN_PROGRESS, 'buy_approved'],
                str_contains($label, 'Buy product is processing') => [EventTypeEnum::PROCUREMENT_IN_PROGRESS, 'buy_processing'],
                str_contains($label, 'Buy product successfully purchased') => [EventTypeEnum::PROCUREMENT_IN_PROGRESS, 'buy_purchased'],
                str_contains($label, 'handover to shipping') => [EventTypeEnum::READY_FOR_TRANSPORT, 'buy_handover_to_shipping'],
                str_contains($label, 'arrived at warehouse') => [EventTypeEnum::ARRIVED_ORIGIN_WAREHOUSE, 'arrived_origin_wh'],
                str_contains($label, 'Delivery request created') => [EventTypeEnum::DELIVERY_REQUEST_CREATED, 'delivery_request_created'],
                str_contains($label, 'Delivery request is processing') => [EventTypeEnum::DELIVERY_PROCESSING, 'delivery_processing'],
                str_contains($label, 'ready') => [EventTypeEnum::DELIVERY_READY, 'delivery_ready'],
                str_contains($label, 'has been shipped') => [EventTypeEnum::DELIVERY_SHIPPED, 'delivery_shipped'],
                str_contains($label, 'successfully delivered') => [EventTypeEnum::DELIVERED, 'delivered'],
                default => [null, null],
            };

            if (!$type || !$occur) {
                continue;
            }

            $rows[] = [
                EventFiltersEnum::SHIPMENT_ID => $shipmentId,
                EventFiltersEnum::SOURCE_ID => $sourceId,
                EventFiltersEnum::TYPE => $type,
                EventFiltersEnum::OCCURRED_AT => $occur,
                EventFiltersEnum::SOURCE_KIND => 'internal',
                EventFiltersEnum::STATUS_CODE => $statusCode,
                EventFiltersEnum::RAW_TEXT => $message ?: $label,
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

    /**
     * Pull (optional) — internal systems usually POST to us.
     * Here we keep signature to match the job flow.
     */
    public function find(string $identifier): array
    {

        $data = [
            "object" => "Tracker",
            "id" => 552844,
            "track_for" => "admin",
            "tracking_events" => [
                "object" => "TrackingEventCollection",
                "data" => [
                    [
                        "object" => "TrackingEvent",
                        "id" => 5577809,
                        "visibility" => "public",
                        "label" => "Buy product created",
                        "message" => "Buy product PB10251314FE09 has been created",
                        "template" => null,
                        "template_configuration" => null,
                        "display_order" => 1,
                        "causer" => [
                            "object" => "User",
                            "id" => 96506,
                            "name" => "Ayman Siddiquee",
                            "phone" => "+8801829443244",
                            "email" => "aymansiddiquee2@gmail.com",
                            "email_verified_at" => null,
                            "type" => "customer",
                            "is_active" => 1,
                            "status" => "active",
                            "created_at" => "2025-07-20T18:28:16.000000Z",
                        ],
                        "event_images" => [
                            "object" => "TrackingEventImageCollection",
                            "data" => [],
                        ],
                        "mentions" => [
                            "object" => "TrackingEventMentionCollection",
                            "data" => [],
                        ],
                        "acls" => [
                            "object" => "TrackingEventAclCollection",
                            "data" => [],
                        ],
                        "children" => [],
                        "created_at" => "2025-10-13T05:00:03.000000Z",
                    ],
                    [
                        "object" => "TrackingEvent",
                        "id" => 5582986,
                        "visibility" => "public",
                        "label" => "Buy product approved",
                        "message" => "Buy product PB10251314FE09 has been approved",
                        "template" => null,
                        "template_configuration" => null,
                        "display_order" => 1,
                        "causer" => [
                            "object" => "User",
                            "id" => 42352,
                            "name" => "MD. Dil Arosh Islam",
                            "phone" => null,
                            "email" => "dislam@moveon.global",
                            "email_verified_at" => null,
                            "type" => "admin",
                            "is_active" => 1,
                            "status" => null,
                            "created_at" => "2024-06-05T03:57:42.000000Z",
                        ],
                        "event_images" => [
                            "object" => "TrackingEventImageCollection",
                            "data" => [],
                        ],
                        "mentions" => [
                            "object" => "TrackingEventMentionCollection",
                            "data" => [],
                        ],
                        "acls" => [
                            "object" => "TrackingEventAclCollection",
                            "data" => [],
                        ],
                        "children" => [],
                        "created_at" => "2025-10-13T07:46:13.000000Z",
                    ],
                    [
                        "object" => "TrackingEvent",
                        "id" => 5584200,
                        "visibility" => "public",
                        "label" => "Buy product is processing",
                        "message" => "Buy product PB10251314FE09 is currently being processed",
                        "template" => null,
                        "template_configuration" => null,
                        "display_order" => 1,
                        "causer" => [
                            "object" => "User",
                            "id" => 54190,
                            "name" => "Md Dil Arosh Islam",
                            "phone" => null,
                            "email" => "dislam+agent@moveon.global",
                            "email_verified_at" => null,
                            "type" => "agent",
                            "is_active" => 1,
                            "status" => null,
                            "created_at" => "2024-11-05T13:22:26.000000Z",
                        ],
                        "event_images" => [
                            "object" => "TrackingEventImageCollection",
                            "data" => [],
                        ],
                        "mentions" => [
                            "object" => "TrackingEventMentionCollection",
                            "data" => [],
                        ],
                        "acls" => [
                            "object" => "TrackingEventAclCollection",
                            "data" => [],
                        ],
                        "children" => [],
                        "created_at" => "2025-10-13T08:19:11.000000Z",
                    ],
                    [
                        "object" => "TrackingEvent",
                        "id" => 5599626,
                        "visibility" => "public",
                        "label" => "Buy product successfully purchased",
                        "message" => "Buy product PB10251314FE09 has been successfully purchased",
                        "template" => null,
                        "template_configuration" => null,
                        "display_order" => 1,
                        "causer" => [
                            "object" => "User",
                            "id" => 54190,
                            "name" => "Md Dil Arosh Islam",
                            "phone" => null,
                            "email" => "dislam+agent@moveon.global",
                            "email_verified_at" => null,
                            "type" => "agent",
                            "is_active" => 1,
                            "status" => null,
                            "created_at" => "2024-11-05T13:22:26.000000Z",
                        ],
                        "event_images" => [
                            "object" => "TrackingEventImageCollection",
                            "data" => [],
                        ],
                        "mentions" => [
                            "object" => "TrackingEventMentionCollection",
                            "data" => [],
                        ],
                        "acls" => [
                            "object" => "TrackingEventAclCollection",
                            "data" => [],
                        ],
                        "children" => [],
                        "created_at" => "2025-10-13T13:20:09.000000Z",
                    ],
                    [
                        "object" => "TrackingEvent",
                        "id" => 5631438,
                        "visibility" => "public",
                        "label" => "Buy product handover to shipping",
                        "message" => "Buy product PB10251314FE09 has been handed over for shipping",
                        "template" => null,
                        "template_configuration" => null,
                        "display_order" => 1,
                        "causer" => [
                            "object" => "User",
                            "id" => 55060,
                            "name" => "Fahmida Faiza",
                            "phone" => "+8801309372090",
                            "email" => "faizafahmida908@gmail.com",
                            "email_verified_at" => null,
                            "type" => "customer",
                            "is_active" => 1,
                            "status" => null,
                            "created_at" => "2024-11-11T06:11:15.000000Z",
                        ],
                        "event_images" => [
                            "object" => "TrackingEventImageCollection",
                            "data" => [],
                        ],
                        "mentions" => [
                            "object" => "TrackingEventMentionCollection",
                            "data" => [],
                        ],
                        "acls" => [
                            "object" => "TrackingEventAclCollection",
                            "data" => [],
                        ],
                        "children" => [],
                        "created_at" => "2025-10-14T10:14:16.000000Z",
                    ]
                ],
            ],
        ];

        return $data;
    }

    /** Normalize ISO8601 or MySQL datetime to 'Y-m-d H:i:s' */
    private static function t(?string $dt): ?string
    {
        if (!$dt) return null;
        try {
            return date('Y-m-d H:i:s', strtotime($dt));
        } catch (\Throwable) {
            return null;
        }
    }
}
