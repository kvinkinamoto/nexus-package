# Nexus field-type reference

| Type | Source | Description |
| --- | --- | --- |
| `string` | built-in | Single-line text input. |
| `text` | built-in | Multi-line textarea. Add `editor: true` to swap it for a CKEditor instance instead. |
| `number` | built-in | Numeric input. |
| `email` | built-in | Email input. |
| `password` | built-in | Password input. Typically paired with `required: false` on the update form and `showInInfolist: false`. |
| `boolean` | built-in | Toggle switch. |
| `birthday` | built-in | Date input. `type: 'date'` is an alias for this same partial. |
| `datetime` | built-in | Date+time input. |
| `enum` | built-in | Dropdown backed by a PHP backed enum — set via `#[Field(enum: SomeEnum::class)]`. |
| `select` | built-in | Dropdown whose options are populated at runtime by an `AdminFormBuilding` listener setting `$field->customData`. |
| `phone` | built-in | Phone number input. |
| `json` | built-in | Read-only, pretty-printed JSON viewer. |
| `image` | built-in | Single-file elFinder picker for an image. |
| `video` | built-in | Single-file elFinder picker for a video. |
| `images` | built-in | Multi-file elFinder gallery picker — the column stores an array of paths. |
| `videos` | built-in | Multi-file elFinder gallery picker for videos — the column stores an array of paths. |
| `relation` | built-in | Generic relation picker driven by `#[Relation(type: 'belongsTo'|'belongsToMany'|'hasOne'|'hasMany', ...)]`. `ajax: true` switches from preload to search-as-you-type; `ajaxMode: 'load'` keeps it searchable while still preloading. |
| `relationManager` | built-in | Read-only list of related records with links into the related module. Never posts data — excluded from validation coverage. |
| `repeater` | built-in | Child-row repeater table, driven by `#[RepeaterField]` columns on a hasMany relation. Matched by the presence of repeater columns, not by this type string. |
| `causer` | built-in | Read-only "performed by user" link (activity-log style fields). |
| `view` | built-in | Injects an arbitrary Blade view: `#[Field(type: 'view', view: 'your::view')]`. The view receives `model`, `field`, `module`. |
| `gender` | built-in | Hardcoded male/female dropdown wired to the `User` model — not usable on an arbitrary module. |
| `widgetConfigFields` | built-in | Dashboard-widget configuration sub-form — framework-internal, not for module data. |
| `wishlist_table` | built-in | Hardcoded to this app's wishlist model — not usable on an arbitrary module. |
| `cart_table` | built-in | Hardcoded to this app's cart model — not usable on an arbitrary module. |
| `wishlistable_type_field` | built-in | Hardcoded to this app's wishlist polymorphic type — not usable on an arbitrary module. |
| `addresses_table` | built-in | Hardcoded to this app's address model — not usable on an arbitrary module. |
| `attribute_options_field` | built-in | Hardcoded to this app's product-attribute model — not usable on an arbitrary module. |
| `custom_history` | built-in | Hardcoded activity-history viewer for this app's models — not usable on an arbitrary module. |
| `custom_delivery_type` | built-in | Hardcoded to this app's delivery model — not usable on an arbitrary module. |
| `custom_delivery_method` | built-in | Hardcoded to this app's delivery model — not usable on an arbitrary module. |
| `custom_payment_method` | built-in | Hardcoded to this app's payment model — not usable on an arbitrary module. |
| `color` | built-in | Color picker with a synced hex text input. |
| `currency` | built-in | Numeric input with a currency symbol prefix — symbol configurable via `customData['symbol']` (default `$`). |
| `file` | built-in | Single-file elFinder picker for any document type — same mechanism as `image`, showing a filename/link instead of a preview. |
| `gallery` | built-in | Media-library-backed image collection (dedup, thumbnails, reordering) via `MediaLibraryInterface` — unlike `images`' plain JSON array of paths. Requires the model to already be persisted. |
| `icon` | built-in | Free-text icon key (e.g. `solar:widget-bold`) resolved by `nexus_icon()`/`IconManager`, with a live preview — no browsable icon gallery. |
| `location` | built-in | Plain labeled text input with a location icon — not an interactive map/coordinate picker. |
| `markdown` | built-in | Plain monospace textarea storing raw Markdown — no live preview or rendering; rendering is left to the consuming app. |
| `multiple_string` | built-in | Tag/chip list input backed by an array column — type a value and press Enter to add a chip. |
| `radio` | built-in | Radio-button group — options come from a backed enum (`#[Field(enum:)]`) or `customData`, the same sources `select`/`enum` use. |
| `range` | built-in | Slider input — bounds configurable via `customData['min'|'max'|'step']` (defaults 0/100/1). |
| `rating` | built-in | Star-rating control — max stars configurable via `customData['max']` (default 5). |
| `slug` | built-in | Text input with a "generate" button that runs Laravel's `Str::slug()` server-side against `slugSource`. Still directly editable. |
| `time` | built-in | HTML5 time-only input — the counterpart to `datetime`, which combines date and time. |
| `url` | built-in | URL input with a link icon and a built-in URL validation rule. |
| `blockEditor` | built-in | Ordered list of heterogeneous content-block rows (Hero/Text/Image/CTA-style), each rendered by its own `BlockTypeRegistry` entry — a visual page/block editor, not a repeater over one fixed column set. |
| `editor` | alias of `text` | Multi-line textarea. Add `editor: true` to swap it for a CKEditor instance instead. |
| `date` | alias of `birthday` | Date input. `type: 'date'` is an alias for this same partial. |
| `examplePluginAlias` | alias of `string` | Single-line text input. |
