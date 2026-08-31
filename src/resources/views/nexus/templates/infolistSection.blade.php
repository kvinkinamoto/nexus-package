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
    <div class="card {{ $section->class ?? null }} mb-3">
        <div class="card-header">
            <h3 class="card-title d-flex align-items-center gap-1">
                @if($section->icon ?? false)
                    <i class="{{ nexus_icon($section->icon) }} text-primary fs-20"></i>
                @endif
                @if(str_contains($section->name, '::'))
                    @lang($section->name)
                @else
                    @lang(lcfirst($module->name) . '::translate.' . $section->name)
                @endif
            </h3>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
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
            </dl>
        </div>
    </div>
@endforeach
