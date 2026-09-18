{{--
    Read-only counterpart of templates/section.blade.php. Groups fields into
    the same section/card structure the edit form uses (reusing $module->sections
    and $formData['fields'] as-is — no separate "infolist schema"), but renders
    each field's current value instead of an input. Deliberately does not
    reuse sections/base.blade.php + templates/field_types/*.blade.php: those
    28 partials are edit-form inputs (validation, JS widgets, old() handling)
    with no read-only counterpart, and threading a $readonly flag through
    every one of them would touch far more files than this single template.
--}}
<?php
$showTranslate = false;
$uniqTypeSection = [];
foreach ($module->sections as $key => $section) {
    foreach ($formData['fields'] as $field) {
        if ($key != $field->section) {
            continue;
        }
        $sectionOfField = $module->sections[$field->section] ?? null;
        if (isset($column)) {
            if (!isset($sectionOfField)) {
                continue;
            }
            if ($sectionOfField->column != $column->name) {
                continue;
            }
        } else {
            if (isset($sectionOfField->column)) {
                continue;
            }
            if (!isset($sectionOfField)) {
                $sectionOfField->name = $sectionOfField->section ?? 'default';
            }
        }

        if (!isset($uniqTypeSection[$field->section]) && isset($sectionOfField)) {
            $uniqTypeSection[$sectionOfField->name] = $sectionOfField;
            $uniqTypeSection[$sectionOfField->name]->name = $field->section;
        }
    }
}
$uniqTypeSection = array_values($uniqTypeSection);
?>

@foreach($uniqTypeSection as $section)
    <div class="mb-4 rounded-2xl border border-gray-200 bg-white {{ $section->class ?? '' }} dark:border-gray-800 dark:bg-white/[0.02]">
        <div class="border-b border-gray-100 px-5 py-4 dark:border-white/5">
            <h3 class="flex items-center gap-2 text-base font-semibold text-gray-800 dark:text-white/90">
                @if($section->icon ?? false)
                    <i class="{{ nexus_icon($section->icon) }} text-brand-500"></i>
                @endif
                {{ nexus_trans_label($module->name, $section->name, $section->name) }}
            </h3>
        </div>
        <div class="grid grid-cols-1 gap-x-6 gap-y-3 p-5 sm:grid-cols-3">
            @foreach($formData['fields'] as $field)
                @continue(!($field->showInInfolist ?? true))
                @continue($field->section != $section->name)
                @if($field->isTranslate ?? false)
                    @foreach($formData['languages'] ?? [] as $lang)
                        @include('nexus::'. config('nexus.template').'.templates.infolist.field', [
                            'field' => $field,
                            'module' => $module,
                            'model' => $model ?? null,
                            'formData' => $formData ?? [],
                            'tab_lang' => $lang,
                        ])
                    @endforeach
                @else
                    @include('nexus::'. config('nexus.template').'.templates.infolist.field', [
                        'field' => $field,
                        'module' => $module,
                        'model' => $model ?? null,
                        'formData' => $formData ?? [],
                    ])
                @endif
            @endforeach
        </div>
    </div>
@endforeach
