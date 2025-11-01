<?php

namespace App\Enums\Shipment;

use App\Base\BaseEnum;

class ShipmentFiltersEnum extends BaseEnum
{
    const ID                   = 'id';
    const MODE                 = 'mode';
    const CURRENT_STATUS       = 'current_status';
    const STATUS_SOURCE_ID     = 'status_source_id'; // FK -> sources.id
    const DELIVERED_AT         = 'delivered_at';
    const DELIVERED_AT_COURIER = 'delivered_at_courier';
    const STATUS_DISCREPANCY   = 'status_discrepancy';
    const LAST_EVENT_AT        = 'last_event_at';
    const LAST_SYNCED_AT       = 'last_synced_at';
    const SUMMARY_TIMESTAMPS   = 'summary_timestamps';
    const CREATED_AT           = 'created_at';
    const UPDATED_AT           = 'updated_at';
    const IDENTIFIER           = 'identifier';
}
