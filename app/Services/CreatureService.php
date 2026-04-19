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

    public function createEncounter(array $charLevels, int $difficulty, string $environment): array | null
    {
        // to allow for a ± difference around matching creatures to characters challenge ratings
        $differenceThresholdMax = 0.1;
        $difficulty = max(min($difficulty, 4), 1); // clamp the difficulty between 1 and 4.
        $expThreshold = array_reduce($charLevels, function ($carry, $charLevel) use ($difficulty) {
            return $carry + $this->difficulties[$charLevel][$difficulty - 1];
        });

        $maxExpThreshold = $expThreshold + ($expThreshold * $differenceThresholdMax);

        $creaturesForEnvironment = $this->getCreaturesForEnvironment($environment, $expThreshold);

        // sort the creatures by exp to make it more efficient to create an encounter.
        usort($creaturesForEnvironment, function ($a, $b) {
            return $a->exp <=> $b->exp;
        });

        if (count($creaturesForEnvironment) === 0)
            return [];

        $encounterCreatures = [];
        $encounterComplete = false;
        $maxLoops = count($charLevels) * 10; // fail-safe to prevent infinite loops, should be more than enough iterations to find a suitable encounter.
        $currentLoop = 1;
        $currentTotalExp = 0;

        // start the encounter around this creature.
        $startingCreature = $creaturesForEnvironment[rand(0, count($creaturesForEnvironment) - 1)];
        $startingCreature->hp = $this->getCreatureHp(
            $startingCreature->hit_points_dice,
            $startingCreature->hit_points_dice_sides,
            $startingCreature->hit_point_additional
        );
        $encounterCreatures[] = $startingCreature;

        while (!$encounterComplete) {
            $possibleCreature = $creaturesForEnvironment[rand(0, count($creaturesForEnvironment) - 1)];

            if ($currentTotalExp + $possibleCreature->exp < $maxExpThreshold) {
                $possibleCreature->hp = $this->getCreatureHp(
                    $possibleCreature->hit_points_dice,
                    $possibleCreature->hit_points_dice_sides,
                    $possibleCreature->hit_point_additional
                );
                $encounterCreatures[] = $possibleCreature;
                $currentTotalExp += $possibleCreature->exp;
            }

            $currentLoop ++;

            if ($currentLoop >= $maxLoops || $currentTotalExp >= $expThreshold) {
                $encounterComplete = true;
            }
        }

        return [
            'creatures' => $encounterCreatures,
            'difficulty' => $currentTotalExp,
            'partyDifficulty' => $expThreshold,
            'environment' => $environment,
        ];
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

    public function processTraits($traits): array
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

    public function processSkills($skills): array
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

    public function processAbilities($abilities): array
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

    public function getCreatureHp(int $diceAmount, string $sides, int $additionalFixedValue): int
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
