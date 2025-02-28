<?php

namespace App\Services;

use App\Models\Character;
use App\Models\CharClassSpellSlots;
use App\Models\CharRace;
use App\Models\GameSpell;
use phpDocumentor\Reflection\Types\Collection;

class MagicService
{
    public function getAvailableSpells(Character $character): array
    {
        $raceSpells = $this->getAvailableSpellsForRace($character->race_id);
        $classSpells = $this->getAvailableSpellsForClass($character->class_id, $character->level);
        // TODO add in class path spells based on selected character class path

        $availableSpells = $classSpells;
        if (!empty($availableSpells['spells']) && !empty($raceSpells['spells']))
        {
            $availableSpells['spells']->merge($raceSpells['spells']);
        }

        foreach ($raceSpells as $key => $value)
        {
            if (strstr($key, 'level') !== false)
            {
                $availableSpells[$key] += $value;
            }
        }

        return $availableSpells;
    }

    private function getAvailableSpellsForRace(int $raceId): array
    {
        $spellTraits = CharRace::where('id', $raceId)->first()->RaceTraits->where('type', 'spell') ?? null;

        foreach ($spellTraits as $spellTrait)
        {
            try {
                $details = json_decode($spellTrait->ability_details);

                if (
                    property_exists($details, 'spellIncrease') &&
                    property_exists($details, 'fromClass') &&
                    property_exists($details, 'spellLevel')
                )
                {
                    // annoying to have to specify the table name in a whereRelation clause,
                    // as the whole point of using models is to not have to do that
                    $spells = GameSpell::where('level', $details->spellLevel)
                        ->whereRelation('CharClasses', 'char_classes.id', '=', $details->fromClass)->get();

                    return [
                        'spells' => $spells,
                        "level_{$details->spellLevel}" => $details->spellIncrease,
                    ];
                }
            } catch (\Exception $e) {
                // TODO this will mean bad json in the DB
                var_dump($e->getMessage());
            }
        }

        return [];
    }

    private function getAvailableSpellsForClass(int $classId, int $level): array
    {
        $slots = CharClassSpellSlots::where('class_id', $classId)->where('char_level', $level)->first();

        if (!$slots)
            return [];

        // get highest level of spells available
        $highestSpellLevel = 0;
        for ($i = 9; $i > 0; $i --)
        {
            if ($slots->{"level_$i"} > 0)
            {
                $highestSpellLevel = $i;
                break;
            }
        }

        $spells = GameSpell::where('level', '<=', $highestSpellLevel)
            ->whereRelation('CharClasses', 'char_classes.id', '=', $classId)->orderBy('level')->get();

        return [
            'spells' => $spells,
            'level_0' => $slots->cantrips_known,
            'level_1' => $slots->level_1,
            'level_2' => $slots->level_2,
            'level_3' => $slots->level_3,
            'level_4' => $slots->level_4,
            'level_5' => $slots->level_5,
            'level_6' => $slots->level_6,
            'level_7' => $slots->level_7,
            'level_8' => $slots->level_8,
            'level_9' => $slots->level_9,
            'spells_known' => $slots->spells_known,
        ];
    }
}
