<?php

namespace Nodex\Nexus\Enums;

enum AdminPanelPermissionEnum: string
{
    case ALL = 'ALL';
    case SHOW_SIDE_MENU = 'SHOW_SIDE_MENU';
    case INDEX = 'INDEX';
    case EDITE = 'EDITE';
    case UPDATE = 'UPDATE';
    case CREATE = 'CREATE';
    case STORE = 'STORE';
    case DELETE = 'DELETE';
    case RESTORE = 'RESTORE';
    case DELETE_PERMANENT = 'DELETE_PERMANENT';
    case DELETE_GROUP = 'DELETE_GROUP';
    case ORDERING = 'ORDERING';
    case BOOL_TOGGLE = 'BOOL_TOGGLE';
    case ADMIN_PANEL = 'ADMIN_PANEL';


}
