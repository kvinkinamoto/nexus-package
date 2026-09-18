<?php

namespace Nodex\Nexus\Enums;

enum AdminAvailableFilterEnum: string {
    case FILTER_TRASHED = 'trashed';
    case FILTER_SEARCH = 'search';
    case FILTER_PUBLISH = 'is_published';
    case FILTER_RELATION = 'relation';
    case FILTER_DEPTH = 'depth';
    case FILTER_DATE = 'date';
}
