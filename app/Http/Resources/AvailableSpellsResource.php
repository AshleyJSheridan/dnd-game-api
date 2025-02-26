<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AvailableSpellsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'spells_known' => $this['spells_known'],
            'level_0' => $this['level_0'],
            'level_1' => $this['level_1'],
            'level_2' => $this['level_2'],
            'level_3' => $this['level_3'],
            'level_4' => $this['level_4'],
            'level_5' => $this['level_5'],
            'level_6' => $this['level_6'],
            'level_7' => $this['level_7'],
            'level_8' => $this['level_8'],
            'level_9' => $this['level_9'],
            'spells' => GameSpellResource::collection($this['spells']),
        ];
    }
}
