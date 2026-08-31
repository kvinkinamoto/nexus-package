<?php

namespace Nodex\Nexus\Enums;

enum AdminActionTypeEnum: string {
    case ACTION_SINGLE = 'single';
    case ACTION_GROUP = 'group';
    case ACTION_SINGLE_GROUP = 'single_group';
    case ACTION_SINGLE_IN_COLUMN = 'single_in_column';
}
