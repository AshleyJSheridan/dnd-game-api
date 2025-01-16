<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CharToolOptionsProficiencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'max' => $this->max_tools,
            'tools' => CharToolProficiencyResource::collection($this->ToolProficiencies),
        ];
    }
}
