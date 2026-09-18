<?php

namespace Nodex\Nexus\Services;

use Nodex\Nexus\Dto\ModuleDtos\FieldConfigDto;

/**
 * Single source of truth for #[Field(showWhen:)] evaluation. The client-side
 * runtime (resources/publish/{skote,larkon}/js/nexus-conditional-fields.js)
 * mirrors this operator table in JS — keep both in sync if either changes.
 *
 * Normalized condition shape: ['field' => string, 'op' => string, 'value' => mixed].
 */
class FieldVisibilityEvaluator
{
    /**
     * @param  array<string, mixed>  $values  Flat map of fieldName => current value
     *                                        (submitted input or model attribute).
     */
    public function isVisible(FieldConfigDto $field, array $values): bool
    {
        if (empty($field->showWhen)) {
            return true;
        }

        $results = array_map(
            fn (array $condition) => $this->evaluateCondition($condition, $values),
            $field->showWhen,
        );

        return $field->showWhenLogic === 'or'
            ? in_array(true, $results, true)
            : !in_array(false, $results, true);
    }

    private function evaluateCondition(array $condition, array $values): bool
    {
        $fieldName = $condition['field'] ?? null;
        $op = $condition['op'] ?? 'truthy';
        $expected = $condition['value'] ?? null;
        $actual = $fieldName !== null ? ($values[$fieldName] ?? null) : null;

        return match ($op) {
            'eq' => $actual == $expected,
            'neq' => $actual != $expected,
            'in' => is_array($expected) && in_array($actual, $expected),
            'notIn' => is_array($expected) && !in_array($actual, $expected),
            'truthy' => (bool) $actual,
            'falsy' => !$actual,
            'gt' => is_numeric($actual) && is_numeric($expected) && $actual > $expected,
            'lt' => is_numeric($actual) && is_numeric($expected) && $actual < $expected,
            'contains' => is_string($actual) && is_string($expected) && str_contains($actual, $expected),
            default => true,
        };
    }
}
