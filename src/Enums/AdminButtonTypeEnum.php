<?php

namespace Nodex\Nexus\Enums;

enum AdminButtonTypeEnum: string {
    case SAVE = 'SAVE';
    case SAVE_AND_CLOSE = 'SAVE_AND_CLOSE';
    case SAVE_AND_NEW = 'SAVE_AND_NEW';
    case CLOSE = 'CLOSE';
}
