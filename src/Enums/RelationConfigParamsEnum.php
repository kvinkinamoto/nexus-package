<?php

namespace Nodex\Nexus\Enums;

enum RelationConfigParamsEnum : string
{
    case HAS_ONE = 'HAS_ONE';
    case BELONGS_TO = 'BELONGS_TO';
    case HAS_MANY = 'HAS_MANY';
    case BELONGS_TO_MANY = 'BELONGS_TO_MANY';
    case CUSTOM = 'CUSTOM';
}
