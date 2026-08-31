<?php

namespace Nodex\Nexus\Events;

use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;

/**
 * Fired by FormBuilder just before a module's edit/create form is assembled.
 *
 * Any listener may modify $config (add fields, tabs, sections, relations)
 * without touching the module's own ModuleConfiguration class.
 *
 * This enables zero-coupling between modules:
 * - The SEO module can inject meta fields into any model that implements HasSeoInterface.
 * - The Tags module can inject a tags tab into any model that uses the HasTags trait.
 * - etc.
 *
 * Example listener registration (in a module's ServiceProvider or via auto-discovery):
 *
 *   Event::listen(AdminFormBuilding::class, InjectSeoFields::class);
 *
 * Example listener:
 *
 *   class InjectSeoFields {
 *       public function handle(AdminFormBuilding $event): void {
 *           if (!is_a($event->config->model, HasSeoInterface::class, true)) return;
 *           $event->config->tab('seo')->label('SEO');
 *           $event->config->section('seo')->column('seo_col')->tab('seo');
 *           $event->config->sectionColumn('seo_col')->class('col-lg-12');
 *           $event->config->form(function($form) {
 *               $form->field('meta_title', 'string', 'seo')->translated()->required(false);
 *               $form->field('meta_description', 'text', 'seo')->translated()->required(false);
 *           });
 *       }
 *   }
 */
class AdminFormBuilding
{
    public function __construct(
        /** The name of the module whose form is being built (e.g. 'shopProduct') */
        public readonly string $moduleName,

        /**
         * The live DTO that will be used to render the form.
         * Listeners should mutate this object to add fields, tabs, sections, etc.
         */
        public readonly DefaultModuleConfigurationDto $config,

        /**
         * The Eloquent model instance being edited, or null when creating a new record.
         * Useful for conditional logic (e.g. only show a field if the record has a certain status).
         */
        public readonly ?\Illuminate\Database\Eloquent\Model $model,

        /**
         * Nodex\Nexus\Livewire\ModuleForm's own live $data array (keyed by
         * field name), when this event was dispatched from there — null on
         * the legacy FormBuilder::build() path, which has no equivalent
         * "currently being typed" state to offer (only the persisted $model
         * and the previous full-page-load's old() input). A listener that
         * needs the value the admin is looking at *right now* — not what was
         * last saved — should prefer this over old()/$model when present
         * (e.g. Widget's PopulateWidgetSelectOptions, whose widget_view
         * options depend on whichever widget_key is currently selected, not
         * necessarily the persisted one).
         */
        public readonly ?array $liveData = null,
    ) {}
}
