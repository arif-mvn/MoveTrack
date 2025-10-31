<?php
namespace App\Enums\Event;
use App\Base\BaseEnum;
class EventExpandsEnum extends BaseEnum
{
    const SHIPMENT      = 'shipment';
    const LEG           = 'leg';
    const SOURCE        = 'source';
    const SOURCE_EVENTS = 'sourceEvents';
}

