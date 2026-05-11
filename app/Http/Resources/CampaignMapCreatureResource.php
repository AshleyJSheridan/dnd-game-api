<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Services\CreatureService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class CampaignMapCreatureResource extends JsonResource
{
    private User $user;

    public function __construct($resource)
    {
        parent::__construct($resource);

        try {
            $this->user = JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {}
    }

    public function toArray(Request $request): array
    {
        $creatureService = app(CreatureService::class);
        $this->Creature->abilities = $creatureService->processAbilities($this->Creature->abilities);
        $this->Creature->saving_throw_abilities = $creatureService->processAbilities($this->Creature->saving_throw_abilities);
        $this->Creature->skill_modifiers = $creatureService->processSkills($this->Creature->skill_modifiers);
        $this->Creature->senses = $creatureService->processTraits($this->Creature->senses);

        return [
            'guid' => $this->guid,
            'type' => $this->type,
            'entity_name' => $this->entity_name,
            'x' => $this->x,
            'y' => $this->y,
            'highlight_colour' => $this->highlight_colour,
            'visible' => $this->visible === 'yes',
            'entity' => CreatureResource::make($this->Creature)->additional(['stats' => json_decode($this->stats)]),
            'editable' => $this->added_by === $this->user->id,
        ];
    }
}
