<?php

namespace App\Enums\SourceEvent;

use App\Base\BaseEnum;

class SourceEventFieldsEnum extends BaseEnum
{
    const ID             = 'id';
    const SHIPMENT_ID    = 'shipment_id';   // FK nullable
    const EVENT_ID       = 'event_id';      // FK nullable
    const SOURCE_ID      = 'source_id';     // FK
    const SOURCE_EVENT_ID= 'source_event_id';
    const OCCURRED_AT    = 'occurred_at';
    const PAYLOAD_HASH   = 'payload_hash';
    const PAYLOAD        = 'payload';
    const RECEIVED_AT    = 'received_at';
    const CREATED_AT     = 'created_at';
    const UPDATED_AT     = 'updated_at';
}
