<?php

namespace Rmsramos\Activitylog\Infolists\Components;

use Filament\Infolists\Components\Entry;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Rmsramos\Activitylog\Infolists\Concerns\HasModifyState;

class TimeLinePropertiesEntry extends Entry
{
    use HasModifyState;

    protected string $view = 'activitylog::filament.infolists.components.time-line-propertie-entry';

    protected function setup(): void
    {
        parent::setup();
        $this->configurePropertieEntry();
    }

    private function configurePropertieEntry(): void
    {
        $this
            ->hiddenLabel()
            ->modifyState(fn($state) => $this->modifiedProperties($state));
    }

    private function modifiedProperties($state): ?HtmlString
    {
        $properties = $state['properties'] ?? [];

        if (! empty($properties)) {
            $changes    = $this->getPropertyChanges($properties);
            $causerName = $this->getCauserName($state['causer'] ?? null);

            return new HtmlString(trans("activitylog::infolists.components.updater_updated", [
                'causer'  => $causerName,
                'event'   => __("activitylog::action.event.{$state['event']}"),
                'changes' => implode('<br>', $changes),
            ]));
        }

        return null;
    }

    private function getPropertyChanges(array $properties): array
    {
        if (isset($properties['old'], $properties['attributes'])) {
            return $this->compareOldAndNewValues($properties['old'], $properties['attributes']);
        }

        if (isset($properties['attributes'])) {
            return $this->getNewValues($properties['attributes']);
        }

        return [];
    }

    private function compareOldAndNewValues(array $oldValues, array $newValues): array
    {
        $changes = [];

        foreach ($newValues as $key => $rawNew) {
            $rawOld = $oldValues[$key] ?? null;
            $rawOld = is_array($rawOld) ? json_encode($rawOld, JSON_UNESCAPED_UNICODE) : $rawOld;
            $rawNew = $this->formatNewValue($rawNew);

            $keyLabel = trans("activitylog::properties.{$key}") 
                ?: Str::headline($key);

            $oldDisplay = is_scalar($rawOld)
                ? (trans("activitylog::values.{$rawOld}") ?: $rawOld)
                : $rawOld;
            $newDisplay = is_scalar($rawNew)
                ? (trans("activitylog::values.{$rawNew}") ?: $rawNew)
                : $rawNew;

            if ($rawOld !== null && $rawOld != $rawNew) {
                $changes[] = trans("activitylog::infolists.components.from_oldvalue_to_newvalue", [
                    'key'       => $keyLabel,
                    'old_value' => htmlspecialchars($oldDisplay),
                    'new_value' => htmlspecialchars($newDisplay),
                ]);
            } else {
                $changes[] = trans("activitylog::infolists.components.to_newvalue", [
                    'key'       => $keyLabel,
                    'new_value' => htmlspecialchars($newDisplay),
                ]);
            }
        }

        return $changes;
    }

    private function getNewValues(array $newValues): array
    {
        return array_map(
            function (string $key, $value): string {
                $keyLabel = trans("activitylog::properties.{$key}") 
                    ?: Str::headline($key);

                $raw     = $this->formatNewValue($value);
                $display = is_scalar($raw)
                    ? (trans("activitylog::values.{$raw}") ?: $raw)
                    : $raw;

                return sprintf(
                    '- %s <strong>%s</strong>',
                    $keyLabel,
                    htmlspecialchars($display)
                );
            },
            array_keys($newValues),
            $newValues
        );
    }

    private function formatNewValue($value): string
    {
        return is_array($value)
            ? json_encode($value, JSON_UNESCAPED_UNICODE)
            : ((string) $value ?: '—');
    }
}