<?php

namespace App\Enums\Leg;

use App\Base\BaseEnum;

class LegTypeEnum extends BaseEnum
{
    const ORIGIN_PROCUREMENT    = 'origin_procurement';
    const LINEHAUL              = 'linehaul';
    const DESTINATION_WH        = 'destination_wh';
    const LAST_MILE             = 'last_mile';
}
