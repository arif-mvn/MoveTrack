<?php

namespace App\Enums;

use App\Base\BaseEnum;

class EventFieldsEnum extends BaseEnum
{
    const ID             = 'id';
    const SHIPMENT_ID    = 'shipment_id'; // FK
    const LEG_ID         = 'leg_id';      // FK nullable
    const SOURCE_ID      = 'source_id';   // FK nullable
    const TYPE           = 'type';
    const OCCURRED_AT    = 'occurred_at';
    const SOURCE_KIND    = 'source';      // 'internal' | 'courier'
    const STATUS_CODE    = 'status_code';
    const RAW_TEXT       = 'raw_text';
    const LOCATION       = 'location';
    const ACTOR          = 'actor';
    const EVIDENCE       = 'evidence';
    const VISIBILITY     = 'visibility';
    const AUTHORITATIVE  = 'authoritative';
    const CREATED_AT     = 'created_at';
    const UPDATED_AT     = 'updated_at';
}
