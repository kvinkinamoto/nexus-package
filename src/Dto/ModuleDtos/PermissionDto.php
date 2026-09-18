<?php

namespace Nodex\Nexus\Dto\ModuleDtos;

class PermissionDto extends \stdClass
{

    public function __construct(
        public array $adminPanel,
        public array $api,
        public array $frontend,

    )
    {

    }

    public static function fromArray(array|\stdClass $field)
    {
        $field = (object) $field;
        return new self(
            adminPanel: (array)($field->adminPanel ?? []),
            api: (array)($field->api ?? []),
            frontend: (array)($field->frontend ?? []),
        );
    }

    public function toArray(): array
    {
        return [
            'adminPanel' => $this->adminPanel,
            'api' => $this->api,
            'frontend' => $this->frontend,
        ];
    }

    public function addAdminPermissions(array $newPermissions): void
    {
        $this->adminPanel = array_merge($this->adminPanel, $newPermissions);
    }

    public function addApiPermissions(array $newPermissions): void
    {
        $this->api = array_merge($this->api, $newPermissions);
    }

    public function addFrontendPermissions(array $newPermissions): void
    {
        $this->frontend = array_merge($this->frontend, $newPermissions);
    }


}
