{{--
    Card/column layout for a set of fields, grouping them by their
    #[Section]/#[SectionColumn] the same way the read-only Infolist
    (templates/infolistSection.blade.php) already groups them for the view
    screen — same card markup, adapted for a single Livewire pass instead of
    a read-only value dump. Used two ways from module-form.blade.php:
    - the non-wizard @else branch, with the module's full field list (a
      module with no #[Section] attributes at all still gets the DTO's
      built-in 'information'/'relations' cards — DefaultModuleConfigurationDto's
      constructor — so every existing module gains cards instead of a flat list).
    - each wizard step, with $fields pre-filtered to that tab
      (fieldsForTab()) — so a step with >1 section still shows them as
      separate cards instead of one flat run-on list.
    Any field whose section isn't declared falls into one trailing
    "General" card so nothing is silently dropped.

    Column widths: #[SectionColumn(class:)] (or the 'col-lg-N'/'col-lg-6'
    default AttributeSchemaReader derives) are Bootstrap-era class strings —
    this Tailwind theme has no Bootstrap grid, so only the trailing width
    number is read and mapped onto a 12-col Tailwind grid; unmatched widths
    just fall back to an even split.
--}}
@php
    $sections = $moduleConfig->sections;
    $columns = $moduleConfig->sectionColumns;
    $fieldsToRender = $fields ?? $moduleConfig->form->fields;

    $fieldsBySection = [];
    $unsectioned = [];
    foreach ($fieldsToRender as $f) {
        if (! $this->isFieldVisible($f)) {
            continue;
        }
        if (isset($sections[$f->section])) {
            $fieldsBySection[$f->section][] = $f;
        } else {
            $unsectioned[] = $f;
        }
    }
@endphp
<div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
    @foreach($columns as $column)
        @php
            $sectionsInColumn = array_filter($sections, fn ($s) => $s->column === $column->name);
            preg_match('/(\d+)$/', $column->class ?? '', $m);
            $span = $m[1] ?? (int) round(12 / max(count($columns), 1));
        @endphp
        @if(! empty($sectionsInColumn))
            <div class="flex flex-col gap-4 lg:col-span-{{ $span }}">
                @foreach($sectionsInColumn as $section)
                    @continue(empty($fieldsBySection[$section->name]))
                    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.02]">
                        <div class="border-b border-gray-100 px-5 py-4 dark:border-white/5">
                            <h3 class="flex items-center gap-2 text-base font-semibold text-gray-800 dark:text-white/90">
                                @if($section->icon)
                                    <i class="{{ nexus_icon($section->icon) }} text-brand-500"></i>
                                @endif
                                @if(str_contains($section->name, '::'))
                                    @lang($section->name)
                                @else
                                    @lang(\Illuminate\Support\Str::lcfirst($module->name) . '::translate.' . $section->name)
                                @endif
                            </h3>
                        </div>
                        <div class="grid grid-cols-1 gap-4 p-5 {{ $section->type === 'columns_2' ? 'sm:grid-cols-2' : '' }}">
                            @foreach($fieldsBySection[$section->name] as $field)
                                <div wire:key="field-{{ $field->name }}">
                                    @include('nexus::' . config('nexus.template') . '.livewire.field_types.dispatch', ['field' => $field])
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endforeach

    @if(! empty($unsectioned))
        <div class="lg:col-span-12">
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.02]">
                <div class="border-b border-gray-100 px-5 py-4 dark:border-white/5">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">General</h3>
                </div>
                <div class="grid grid-cols-1 gap-4 p-5">
                    @foreach($unsectioned as $field)
                        <div wire:key="field-{{ $field->name }}">
                            @include('nexus::' . config('nexus.template') . '.livewire.field_types.dispatch', ['field' => $field])
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
