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
| `editor` | alias of `text` | Multi-line textarea. Add `editor: true` to swap it for a CKEditor instance instead. |
| `date` | alias of `birthday` | Date input. `type: 'date'` is an alias for this same partial. |
