<?php

namespace Nodex\Nexus\Enums;

enum AvailableActionEnum : string
{
    case SHOW_SIDE_MENU = 'SHOW_SIDE_MENU';
    case INDEX_ACTION = 'index';
    case CREATE_ACTION = 'create';
    case STORE_ACTION = 'store';
    case EDIT_ACTION = 'edit';
    case UPDATE_ACTION = 'update';
    case DELETE_ACTION = 'delete';
    case DELETE_PERMANENT_ACTION = 'deletePermanent';
    case RESTORET_ACTION = 'restore';
    case DELETE_GROUP_ACTION = 'deleteGroup';
    case BOOL_TOGGLE_ACTION = 'boolToggle';
    case ORDERING_ACTION = 'ordering';
    case VIEW_ACTION = 'view';
    case DUPLICATE_ACTION = 'duplicate';
    case DUPLICATE_GROUP_ACTION = 'duplicateGroup';
    case IMPERSONATE_ACTION = 'impersonate';
}
