<?php

namespace App\Services;

use App\Models\CharAbility;
use App\Models\CharSkill;
use App\Models\CharTrait;
use Illuminate\Database\Eloquent\Collection;

class CreatureService
{
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

    private function processTraits($traits): array
    {
        if ($traits === '')
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
        if ($skills === '')
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
        if($abilities === '')
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
}
