<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class CampaignMapCharacterResource extends JsonResource
{
    private User $user;

    public function __construct($resource)
    {
        parent::__construct($resource);

        try {
            $this->user = JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {}
    }

    public function toArray(Request $request)
    {
        return [
            'guid' => $this->guid,
            'type' => $this->type,
            'entity_name' =>$this->Player->name,
            'x' => $this->x,
            'y' => $this->y,
            'highlight_colour' => $this->highlight_colour,
            'entity' => CharacterResource::make($this->Player),
            'editable' => $this->Player->id === $this->user->id,
        ];
    }
}
