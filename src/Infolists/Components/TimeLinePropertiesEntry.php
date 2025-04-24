<?php

namespace Rmsramos\Activitylog\Infolists\Components;

use Filament\Infolists\Components\Entry;
use Illuminate\Support\Facades\Lang;
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

            return new HtmlString(
                trans('activitylog::infolists.components.updater_updated', [
                    'causer'  => $causerName,
                    'event'   => trans("activitylog::action.event.{$state['event']}"),
                    'changes' => implode('<br>', $changes),
                ])
            );
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

            $keyLabel = Lang::has("activitylog::properties.{$key}")
                ? trans("activitylog::properties.{$key}")
                : Str::headline($key);

            if (is_array($rawOld)) {
                $items = array_map(fn($item) =>
                    Lang::has("activitylog::values.{$item}")
                        ? trans("activitylog::values.{$item}")
                        : $item,
                    $rawOld
                );
                $oldDisplay = implode(', ', $items);
            } else {
                $oldDisplay = is_scalar($rawOld) && Lang::has("activitylog::values.{$rawOld}")
                    ? trans("activitylog::values.{$rawOld}")
                    : $rawOld;
            }

            if (is_array($rawNew)) {
                $items = array_map(fn($item) =>
                    Lang::has("activitylog::values.{$item}")
                        ? trans("activitylog::values.{$item}")
                        : $item,
                    $rawNew
                );
                $newDisplay = implode(', ', $items);
            } else {
                $formatted = $this->formatNewValue($rawNew);
                $newDisplay = is_scalar($formatted) && Lang::has("activitylog::values.{$formatted}")
                    ? trans("activitylog::values.{$formatted}")
                    : $formatted;
            }

            if ($rawOld !== null && $rawOld != (is_array($rawNew) ? $rawNew : $formatted)) {
                $changes[] = trans('activitylog::infolists.components.from_oldvalue_to_newvalue', [
                    'key'       => $keyLabel,
                    'old_value' => htmlspecialchars($oldDisplay),
                    'new_value' => htmlspecialchars($newDisplay),
                ]);
            } else {
                $changes[] = trans('activitylog::infolists.components.to_newvalue', [
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
                $keyLabel = Lang::has("activitylog::properties.{$key}")
                    ? trans("activitylog::properties.{$key}")
                    : Str::headline($key);

                if (is_array($value)) {
                    $items = array_map(fn($item) =>
                        Lang::has("activitylog::values.{$item}")
                            ? trans("activitylog::values.{$item}")
                            : $item,
                        $value
                    );
                    $display = implode(', ', $items);
                } else {
                    $formatted = $this->formatNewValue($value);
                    $display = is_scalar($formatted) && Lang::has("activitylog::values.{$formatted}")
                        ? trans("activitylog::values.{$formatted}")
                        : $formatted;
                }

                return sprintf('- %s <strong>%s</strong>', $keyLabel, htmlspecialchars($display));
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