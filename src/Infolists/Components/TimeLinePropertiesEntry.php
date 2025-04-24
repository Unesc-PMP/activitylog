<?php

namespace Rmsramos\Activitylog\Infolists\Components;

use Filament\Infolists\Components\Entry;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Carbon\Carbon;
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
                    'event'   => trans("activitylog::action.event.{$state['event']}}"),
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
                        : $this->formatNewValue($item),
                    $rawOld
                );
                $oldDisplay = implode(', ', $items);
            } else {
                $formattedOld = $this->formatNewValue($rawOld);
                $oldDisplay = Lang::has("activitylog::values.{$formattedOld}")
                    ? trans("activitylog::values.{$formattedOld}")
                    : $formattedOld;
            }

            if (is_array($rawNew)) {
                $items = array_map(fn($item) =>
                    Lang::has("activitylog::values.{$item}")
                        ? trans("activitylog::values.{$item}")
                        : $this->formatNewValue($item),
                    $rawNew
                );
                $newDisplay = implode(', ', $items);
            } else {
                $formattedNew = $this->formatNewValue($rawNew);
                $newDisplay = Lang::has("activitylog::values.{$formattedNew}")
                    ? trans("activitylog::values.{$formattedNew}")
                    : $formattedNew;
            }

            if ($rawOld !== null && $rawOld != (is_array($rawNew) ? $rawNew : $formattedNew)) {
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
                            : $this->formatNewValue($item),
                        $value
                    );
                    $display = implode(', ', $items);
                } else {
                    $formatted = $this->formatNewValue($value);
                    $display = Lang::has("activitylog::values.{$formatted}")
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
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        $string = (string) ($value ?? '');

        try {
            $dt = Carbon::parse($string);
            $format = preg_match('/\d{2}:\d{2}:\d{2}/', $string) ? 'd/m/Y H:i:s' : 'd/m/Y';
            return $dt->format($format);
        } catch (\Exception $e) {
        }

        return $string !== '' ? $string : '—';
    }
}