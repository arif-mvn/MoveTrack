<?php

namespace App\Enums;

use App\Base\BaseEnum;

class ShipmentSourceFieldsEnum extends BaseEnum
{
    const ID             = 'id';
    const SHIPMENT_ID    = 'shipment_id'; // FK
    const SOURCE_ID      = 'source_id';   // FK
    const LAST_SYNCED_AT = 'last_synced_at';
    const CREATED_AT     = 'created_at';
    const UPDATED_AT     = 'updated_at';
}
