<?php

namespace App\Enums\Database;

use App\Base\BaseEnum;

final class TableNameEnum extends BaseEnum
{
    const SHIPMENTS          = 'shipments'; // fixed
    const SOURCES            = 'sources';
    const LEGS               = 'legs';
    const EVENTS             = 'events';
    const IDENTIFIERS        = 'identifiers';
    const SHIPMENT_SOURCES   = 'shipment_sources';
    const SOURCE_EVENTS      = 'source_events';
    const WEBHOOK_DELIVERIES = 'webhook_deliveries';
    const USERS              = 'users';
}
