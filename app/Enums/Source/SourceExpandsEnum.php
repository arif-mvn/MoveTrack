<?php

namespace App\Enums\Source;

use App\Base\BaseEnum;

class SourceExpandsEnum extends BaseEnum
{
    const SHIPMENTS          = 'shipments';
    const LEGS               = 'legs';
    const EVENTS             = 'events';
    const SOURCE_EVENTS      = 'sourceEvents';
    const SHIPMENT_SOURCES   = 'shipmentSources';
    const WEBHOOK_DELIVERIES = 'webhookDeliveries';
}

