<?php

namespace App\Enums\Leg;

use App\Base\BaseEnum;

class LegFiltersEnum extends BaseEnum
{
    const ID           = 'id';
    const SHIPMENT_ID  = 'shipment_id'; // FK
    const TYPE         = 'type';
    const SOURCE_ID    = 'source_id';   // FK -> sources.id (nullable)
    const CARRIER_NAME = 'carrier_name';
    const ROUTE        = 'route';
    const START_AT     = 'start_at';
    const END_AT       = 'end_at';
    const CREATED_AT   = 'created_at';
    const UPDATED_AT   = 'updated_at';
}
