<?php

namespace App\Enums;

use App\Base\BaseEnum;

class ResourceObjectEnum extends BaseEnum
{
    const SHIPMENT       = 'shipment';
    const SHIPMENT_LIST  = 'shipment_list';
    const SOURCE       = 'source';
    const SOURCE_LIST  = 'source_list';
    const IDENTIFIER       = 'identifier';
    const IDENTIFIER_LIST  = 'identifier_list';
    const EVENT       = 'event';
    const EVENT_LIST  = 'event_list';
    const SOURCE_EVENT = 'source_event';
    const SOURCE_EVENT_LIST = 'source_event_list';
    const LEG = 'leg';
    const LEG_LIST = 'leg_list';
    const SHIPMENT_SOURCE = 'shipment_source';
    const SHIPMENT_SOURCE_LIST = 'shipment_source_list';
    const WEBHOOK_DELIVERY = 'webhook_delivery';
    const WEBHOOK_DELIVERY_LIST = 'webhook_delivery_list';
    const USER = 'user';
    const USER_LIST = 'user_list';
}
