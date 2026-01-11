<?php

namespace App\Http\Resources;

use App\Services\CreatureService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreatureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $creatureService = app(CreatureService::class);
        $overrides = $this->additional['stats'] ?? [];

        return [
            'id' => $this->id,
            'guid' => $this->when(!is_null($this->guid), $this->guid),
            'name' => $this->name,
            'description' => $this->description,
            'size' => $this->size,
            'type' => $this->type,
            'alignment' => $this->Alignment->alignment ?? 'Unaligned',
            'armor' => [
                'armor_class' => $this->armor_class,
                'wears_armor' => $this->wears_armor === 1,
            ],
            'hit_points' => [
                'dice_amount' => $this->hit_points_dice,
                'dice_sides' => $this->hit_points_dice_sides,
                'plus_fixed' => $this->hit_point_additional,
                'max_hp' => $overrides->max_hp ?? $creatureService->getCreatureHp(
                    $this->hit_points_dice,
                    $this->hit_points_dice_sides,
                    $this->hit_point_additional,
                ),
                'hp' => $overrides->hp ?? 0,
            ],
            'speed' => $this->getParsedSpeeds(),
            'challenge_rating' => $this->challenge_rating,
            'abilities' => $this->abilities,
            'saving_throws' => $this->saving_throw_abilities,
            'skill_modifiers' => $this->skill_modifiers,
            'resistances' => json_decode($this->resistances) ?? [],
            'senses' => $this->senses,
            'languages' => CharLanguageResource::collection($this->Languages),
        ];
    }

    private function getParsedSpeeds(): array {
        $speeds = [
            'walk' => $this->speed,
        ];

        if (!is_null($this->other_speeds))
        {
            $speeds = array_merge($speeds, json_decode($this->other_speeds, true));
        }

        return $speeds;
    }
}
