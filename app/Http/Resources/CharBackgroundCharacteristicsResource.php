<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Database\Eloquent\Collection;

class CharBackgroundCharacteristicsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->getGroupedCharacteristic();
    }

    public function getGroupedCharacteristic()
    {
        $labels = [];

        foreach ($this->resource as $item)
        {
            if (!isset($labels[$item->label]))
            {
                $labels[$item->label] = [];
            }

            $labels[$item->label][] = ['id' => $item->id, 'characteristic' => $item->characteristic];
        }

        return $labels;
    }
}
