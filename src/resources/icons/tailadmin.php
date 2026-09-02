<?php

/**
 * Icons mapping for TailAdmin theme.
 *
 * Uses Boxicons (already bundled as a webfont via nexus/css/icons.min.css
 * — see nexus_icon()/IconManager) rather than Tabler Icons: the published
 * asset set (packages/nodex/nexus/src/resources/publish/nexus/{css,fonts})
 * only ships Boxicons/dripicons/Font Awesome/Material Design Icons — the
 * "views/nexus/fonts" Tabler files are dead vendor-template leftovers that
 * never get published to public/, so they render nothing in the browser.
 *
 * Example: 'key' => 'bx bx-icon-name'
 */
return [
    // Core Actions
    'edit' => 'bx bx-pencil',
    'delete' => 'bx bx-trash',
    'delete_permanent' => 'bx bx-trash-alt',
    'save' => 'bx bx-save',
    'plus' => 'bx bx-plus-circle',
    'restore' => 'bx bx-archive-out',
    'archive' => 'bx bx-archive-in',
    'refresh' => 'bx bx-refresh',
    'success' => 'bx bx-check-circle',
    'error' => 'bx bx-error-circle',
    'info' => 'bx bx-info-circle',
    'copy' => 'bx bx-copy',

    // Objects
    'user' => 'bx bx-user-circle',
    'users' => 'bx bx-group',
    'default_icon' => 'bx bx-file',
    'folder' => 'bx bx-folder',
    'database' => 'bx bx-data',
    'layers' => 'bx bx-layer',
    'notebook' => 'bx bx-book-content',
    'shop' => 'bx bx-store-alt',
    'cart' => 'bx bx-cart',
    'bill' => 'bx bx-receipt',
    'delivery' => 'bx bx-package',
    'deliveryType' => 'bx bx-box',
    'paymentMethod' => 'bx bx-credit-card',
    'disk' => 'bx bx-hdd',
    'api_doc' => 'bx bx-code-block',
    'image' => 'bx bx-image',
    'video_play' => 'bx bx-video',
    'camera' => 'bx bx-camera',

    // Communication & UI
    'letter' => 'bx bx-envelope',
    'letter_opened' => 'bx bx-envelope-open',
    'email' => 'bx bx-envelope',
    'phone' => 'bx bx-phone-call',
    'map_point' => 'bx bx-map-pin',
    'global' => 'bx bx-globe',
    'notifications' => 'bx bx-bell',
    'messages' => 'bx bx-chat',
    'search' => 'bx bx-search',
    'search_alt' => 'bx bx-zoom-in',

    // Security
    'lock' => 'bx bx-lock-alt',
    'shield' => 'bx bx-shield-quarter',
    'shield_warning' => 'bx bx-shield-x',
    'check_verified' => 'bx bx-badge-check',
    'block' => 'bx bx-block',

    // Navigation & UI Elements
    'menu' => 'bx bx-menu',
    'menu_toggle' => 'bx bx-menu-alt-left',
    'parent_menu' => 'bx bx-list-ul',
    'list' => 'bx bx-list-check',
    'sort' => 'bx bx-sort',
    'sort_inactive' => 'bx bx-sort-alt-2',
    'arrow_up' => 'bx bx-chevron-up',
    'arrow_down' => 'bx bx-chevron-down',
    'arrow_right_double' => 'bx bx-chevrons-right',
    'chevron_left' => 'bx bx-chevron-left',
    'chevron_right' => 'bx bx-chevron-right',
    'expand' => 'bx bx-expand',
    'fullscreen' => 'bx bx-fullscreen',

    // Tools & Dev
    'settings' => 'bx bx-cog',
    'tools' => 'bx bx-wrench',
    'terminal' => 'bx bx-terminal',
    'code' => 'bx bx-code',
    'bug' => 'bx bx-bug',
    'translation' => 'bx bx-world',
    'seo' => 'bx bx-search-alt',
    'attributes' => 'bx bx-slider-alt',
    'apps' => 'bx bx-grid-alt',
    'tuning' => 'bx bx-slider',
    'bookmark' => 'bx bx-bookmark',
    'server' => 'bx bx-server',
    'structure' => 'bx bx-sitemap',
    'code_file' => 'bx bx-file-blank',
    'code_square' => 'bx bx-bracket',
    'recycle' => 'bx bx-recycle',
    'plug' => 'bx bx-plug',

    // Other
    'history' => 'bx bx-history',
    'clock' => 'bx bx-time',
    'star' => 'bx bx-star',
    'box' => 'bx bx-box',
    'link' => 'bx bx-link',
    'logout' => 'bx bx-log-out',
    'eye' => 'bx bx-show',
    'eye_closed' => 'bx bx-hide',
    'impersonate' => 'bx bx-log-in-circle',
    'bag_heart' => 'bx bx-heart-square',
    'heart' => 'bx bx-heart',
    'bolt_circle' => 'bx bx-bolt-circle',
    'api' => 'bx bx-terminal',
    'test_tube' => 'bx bx-vial',
    'chart_square' => 'bx bx-stats',
    'folder_open' => 'bx bx-folder-open',
    'profile' => 'bx bx-user',
    'location' => 'bx bx-map-pin',
];
