<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class CampaignMapDrawingResource extends JsonResource
{
    private User $user;

    public function __construct($resource)
    {
        parent::__construct($resource);

        try {
            $this->user = JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {}
    }

    public function toArray($request)
    {
        return [
            'guid' => $this->guid,
            'type' => $this->type,
            'entity_name' => $this->entity_name,
            'x' => $this->x,
            'y' => $this->y,
            'highlight_colour' => $this->highlight_colour,
            'orientation' => $this->orientation,
            'stats' => json_decode($this->stats),
            'linked_id' => 0,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'editable' => $this->added_by === $this->user->id,
        ];
    }
}
