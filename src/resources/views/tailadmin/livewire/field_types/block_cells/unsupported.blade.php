{{--
    Fallback for a block-type field whose type has no block_cells/{type}.blade.php
    partial — visible on purpose, matching repeater_cells/unsupported.blade.php's
    "never disappear silently" convention.
--}}
<div class="text-xs text-error-500">
    "{{ $blockField->name }}" ({{ $blockField->type }}): не підтримується в редакторі блоків.
</div>
