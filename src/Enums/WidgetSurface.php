<?php

namespace Nodex\Nexus\Enums;

/**
 * Where a widget can appear. A widget declares which surfaces it supports
 * via #[Widget(surfaces: [...])] — the registry filters on this before a
 * surface even looks at the widget's data.
 */
enum WidgetSurface: string
{
    case Front = 'front';
    case Admin = 'admin';
    case Api = 'api';
}
