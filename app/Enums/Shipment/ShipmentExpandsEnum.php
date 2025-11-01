<?php

namespace App\Enums\Shipment;

use App\Base\BaseEnum;

class ShipmentExpandsEnum extends BaseEnum
{
    const LEGS           = 'legs';
    const LEGS_SOURCE    = 'legs.source';
    const EVENTS         = 'events';
    const SOURCES        = 'sources';
    const STATUS_SOURCE  = 'statusSource';
    const EVENTS_SOURCE  = 'events.source';
    const EVENTS_LEG     = 'events.leg';
    const IDENTIFIERS    = 'identifiers';

}
