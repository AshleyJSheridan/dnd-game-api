<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameSpellResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'level' => $this->level,
            'school' => GameSpellSchoolResource::make($this->SpellSchool),
            'cast_time' => $this->getParsedCastTime($this->cast_time),
            'duration' => $this->getParsedDuration($this->duration),
            'range' => $this->range,
            'components' => $this->parseSpellComponents($this->components),
            'concentration' => $this->concentration,
            'ritual' => $this->ritual,
            'description' => $this->description,
        ];
    }

    private function parseSpellComponents(string $componentString): array
    {
        // needs to be replaced in this specific order to avoid accidentally replacing a character that exists in a replacement
        $componentString = str_replace(['M', 'S', 'V'], ['Material', 'Somatic', 'Verbal'], $componentString);
        return explode(',', $componentString);
    }

    private function getParsedDuration(string $duration): array
    {
        if(in_array($duration, ['Instantaneous', 'Special', 'Until dispelled', 'Until dispelled or triggered']))
            return ['value' => 0, 'unit' => $duration];

        $concentrationString = 'Concentration, up to ';
        $upToString = 'Up to ';

        foreach ([$concentrationString, $upToString] as $string)
        {
            if(strstr($duration, $string) !== false)
            {
                $concentration = strstr($duration, $concentrationString) !== false;
                $duration = substr($duration, strpos($duration, $string) + strlen($string));

                return [
                    'concentration' => $concentration,
                    'up_to' => true,
                    'value' => intval($duration),
                    'unit' => $this->singularifyPluralUnit(substr($duration, strpos($duration, ' ') + 1)),
                ];
            }
        }

        return [
            'value' => intval($duration),
            'unit' => $this->singularifyPluralUnit(substr($duration, strpos($duration, ' ') + 1)),]
        ;
    }

    private function getParsedCastTime(string $castTime): array
    {
        if(strstr($castTime, ' or '))
        {
            $options = explode(' or ', $castTime);
            foreach ($options as &$option)
            {
                $option = $this->getParsedCastTime($option);
            }

            return $options;
        }

        return [
            'value' => intval($castTime),
            'unit' => $this->getUnitFromDurationString($castTime),
        ];
    }

    private function singularifyPluralUnit(string $unit): string
    {
        return str_replace(
            ['days', 'hours', 'minutes'],
            ['day', 'hour', 'minute'],
            $unit
        );
    }

    private function getUnitFromDurationString(string $duration): string
    {
        return $this->singularifyPluralUnit(substr($duration, strpos($duration, ' ') + 1));
    }
}
