<?php

namespace App\Services;

use App\Models\CharAbility;
use App\Models\CharSkill;
use App\Models\CharTrait;
use App\Models\GameCreature;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class CreatureService
{
    // encounter difficulty experience lookup table
    private $difficulties = [
        '1' =>  [25, 50, 75, 100],
        '2' =>  [50, 100, 150, 200],
        '3' =>  [75, 150, 225, 400],
        '4' =>  [125, 250, 375, 500],
        '5' =>  [250, 500, 750, 1100],
        '6' =>  [300, 600, 900, 1400],
        '7' =>  [350, 750, 1100, 1700],
        '8' =>  [450, 900, 1400, 2100],
        '9' =>  [550, 1100, 1600, 2400],
        '10' => [600, 1200, 1900, 2800],
        '11' => [800, 1600, 2400, 3600],
        '12' => [1000, 2000, 3000, 4500],
        '13' => [1100, 2200, 3400, 5100],
        '14' => [1250, 2500, 3800, 5700],
        '15' => [1400, 2800, 4300, 6400],
        '16' => [1600, 3200, 4800, 7200],
        '17' => [2000, 3900, 5900, 8800],
        '18' => [2100, 4200, 6300, 9500],
        '19' => [2400, 4900, 7300, 10900],
        '20' => [2800, 5700, 8500, 12700],
    ];
    private $difficultyMultipliers = [
        1 => 1, 2 => 1.5, 3 => 2, 4 => 2, 5 => 2,
        6 => 2, 7 => 2.5, 8 => 2.5, 9 => 2.5, 10 => 2.5,
        11 => 3, 12 => 3, 13 => 3, 14 => 3, 15 => 4,
    ];

    private Collection $abilities;
    private Collection $skills;
    private Collection $traits;

    public function __construct()
    {
        $this->abilities = CharAbility::all();
        $this->skills = CharSkill::all();
        $this->traits = CharTrait::all();
    }
    public function addProcessedFields(Collection $creatures): Collection
    {
        foreach ($creatures as &$creature)
        {
            $creature->abilities = $this->processAbilities($creature->abilities);
            $creature->saving_throw_abilities = $this->processAbilities($creature->saving_throw_abilities);
            $creature->skill_modifiers = $this->processSkills($creature->skill_modifiers);
            $creature->senses = $this->processTraits($creature->senses);
        }

        return $creatures;
    }

    public function createEncounter(array $charLevels, int $difficulty, string $environment): array
    {
        $differenceThreshold = 0.2; // to allow for a ± difference around matching creatures to characters challenge ratings
        $difficulty = max(min($difficulty, 4), 1);
        $expThreshold = array_reduce($charLevels, function ($carry, $charLevel) use ($difficulty) {
            return $carry + $this->difficulties[$charLevel][$difficulty - 1];
        });

        $creaturesForEnvironment = $this->getCreaturesForEnvironment($environment, $expThreshold);

        if (count($creaturesForEnvironment) === 0)
            return [];


        $possibleEncounterCreatures = [];

        // TODO allow for more complex encounters including mixes of creatures and creature sub-types
        for ($amount = 1; $amount <= 15; $amount ++)
        {
            $difficultyMultiplier = $this->difficultyMultipliers[$amount];

            foreach ($creaturesForEnvironment as $creature)
            {
                $min = ($amount * $creature->exp * $difficultyMultiplier * (1 - $differenceThreshold));
                $max = ($amount * $creature->exp * $difficultyMultiplier * (1 + $differenceThreshold));

                if ($min <= $expThreshold && $expThreshold <= $max)
                {
                    $possibleEncounterCreatures[] = [
                        'creatures' => $this->getGroupOfCreatures($creature, $amount),
                        'difficulty' => intval(round($amount * $creature->exp * $difficultyMultiplier)),
                        'partyDifficulty' => $expThreshold,
                        'environment' => $environment,
                    ];
                }
            }
        }

        return $possibleEncounterCreatures[array_rand($possibleEncounterCreatures)];
    }

    private function getGroupOfCreatures(GameCreature $creature, int $amount): array
    {
        $clones = [];

        for ($i = 0; $i < $amount; $i ++)
        {
            $clonedCreature = clone $creature;
            $clonedCreature->guid = Str::uuid()->toString();
            $clonedCreature->hp = $this->getCreatureHp(
                $clonedCreature->hit_points_dice,
                $clonedCreature->hit_points_dice_sides,
                $clonedCreature->hit_point_additional
            );
            $clones[] = $clonedCreature;
        }

        return $clones;
    }

    private function getCreaturesForEnvironment(string $environment, int $exp)//: Collection | array
    {
        $creatures = GameCreature::where('exp', '<=', $exp)
            ->whereRelation('Environment', 'environment', '=', $environment)
            ->get();

        if ($creatures)
            return $creatures->all();

        return [];
    }

    private function processTraits($traits): array
    {
        if (empty($traits))
            return [];

        $newTraits = [];
        $traits = json_decode($traits);

        foreach ($traits->traits as $trait)
        {
            $newTraits[$this->traits->where('id', $trait->trait)->first()->name] = $trait->range;
        }

        return $newTraits;
    }

    private function processSkills($skills): array
    {
        if (empty($skills))
            return [];

        $newSkills = [];
        $skills = json_decode($skills);

        foreach ($skills as $skillKey => $skillValue)
        {
            $newSkills[$this->skills->where('id', $skillKey)->first()->name] = $skillValue;
        }

        return $newSkills;
    }

    private function processAbilities($abilities): array
    {
        if(empty($abilities))
            return [];

        $newAbilities = [];
        $abilities = json_decode($abilities, true);

        foreach ($this->abilities as $ability)
        {
            if (isset($abilities[$ability->id]))
            {
                $newAbilities[$ability->short_name] = [
                    'base' => $abilities[$ability->id],
                    'modifier' => floor(($abilities[$ability->id] - 10) / 2),
                ];
            }
        }

        return $newAbilities;
    }

    private function getCreatureHp(int $diceAmount, string $sides, int $additionalFixedValue): int
    {
        $hp = $additionalFixedValue;
        $sides = intval(substr($sides, 1));

        for ($i = 0; $i < $diceAmount; $i++)
        {
            $hp += rand(1, $sides);
        }

        // force the returned value to be a minimum of 1, as some creatures have a negative additional hp
        return max(1, $hp);
    }
}
