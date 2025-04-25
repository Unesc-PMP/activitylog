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
        $properties = array_filter(
            $state['properties'] ?? [],
            fn($v) => ! (
                 $v === null
              || $v === ''
              || (is_array($v) && count($v) === 0)
            )
        );
    
        if (count($properties) === 0) {
            return null;
        }
    
        $changes    = $this->getPropertyChanges($properties);
        $causerName = $this->getCauserName($state['causer'] ?? null);
    
        return new HtmlString(
            trans('activitylog::infolists.components.updater_updated', [
                'causer'  => $causerName,
                'event'   => trans('activitylog::action.event.' . $state['event']),
                'changes' => implode('<br>', $changes),
            ])
        );
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

            $oldDisplay = $this->translateValue($rawOld);

            $newDisplay = $this->translateValue($rawNew);

            if ($rawOld !== null && $rawOld != $rawNew) {
                $changes[] = trans('activitylog::infolists.components.from_oldvalue_to_newvalue', [
                    'key' => $keyLabel,
                    'old_value' => htmlspecialchars($oldDisplay),
                    'new_value' => htmlspecialchars($newDisplay),
                ]);
            } else {
                $changes[] = trans('activitylog::infolists.components.to_newvalue', [
                    'key' => $keyLabel,
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

                $display = $this->translateValue($value);

                return sprintf('- %s <strong>%s</strong>', $keyLabel, htmlspecialchars($display));
            },
            array_keys($newValues),
            $newValues
        );
    }

    private function translateValue($raw): string
    {
        if (is_array($raw)) {
            return implode(', ', array_map(fn($item) => $this->translateValue($item), $raw));
        }

        $string = (string) ($raw ?? '');

        if (preg_match('/^\d{4}-\d{2}-\d{2}(?: \d{2}:\d{2}:\d{2})?$/', $string)) {
            try {
                $dt = Carbon::parse($string);
                $format = strpos($string, ':') !== false ? 'd/m/Y H:i:s' : 'd/m/Y';
                return $dt->format($format);
            } catch (\Exception $e) {
            }
        }

        if (Lang::has("activitylog::values.{$string}")) {
            return trans("activitylog::values.{$string}");
        }

        return $string !== '' ? $string : '—';
    }
}