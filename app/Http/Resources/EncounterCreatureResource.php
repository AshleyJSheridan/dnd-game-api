<?php

namespace App\Http\Resources;

use App\Services\CreatureService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EncounterCreatureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $creatureService = app(CreatureService::class);
        $overrides = $this->Creature->additional['stats'] ?? [];

        return [
            'guid' => $this->guid,
            'creature_id' => $this->creature_id,
            'creature_details' => [
                'name' => $this->Creature->name,
                'unique_name' => $this->unique_name,
                'description' => $this->Creature->description,
                'size' => $this->Creature->size,
                'type' => $this->Creature->type,
                'alignment' => $this->Creature->Alignment->alignment ?? 'Unaligned',
                'armor' => [
                    'armor_class' => $this->Creature->armor_class,
                    'wears_armor' => $this->Creature->wears_armor === 1,
                ],
                'hit_points' => [
                    'dice_amount' => $this->Creature->hit_points_dice,
                    'dice_sides' => $this->Creature->hit_points_dice_sides,
                    'plus_fixed' => $this->Creature->hit_point_additional,
                    'max_hp' => $this->max_hp ?? $creatureService->getCreatureHp(
                            $this->Creature->hit_points_dice,
                            $this->Creature->hit_points_dice_sides,
                            $this->Creature->hit_point_additional,
                        ),
                    'hp' => $this->current_hp,
                ],
                'speed' => $this->Creature->speed,
                'challenge_rating' => $this->Creature->challenge_rating,
                'abilities' => json_decode($this->Creature->abilities),
                'saving_throws' => $this->Creature->saving_throw_abilities,
                'skill_modifiers' => $this->Creature->skill_modifiers,
                'resistances' => json_decode($this->Creature->resistances) ?? [],
                'senses' => $this->Creature->senses,
                'languages' => CharLanguageResource::collection($this->Creature->Languages ?? []),
                'overrides' => $this->overrides,
            ],
        ];
    }
}
