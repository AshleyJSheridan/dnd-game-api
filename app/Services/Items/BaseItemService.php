<?php

namespace App\Services\Items;

class BaseItemService
{
    protected function getRarityString(int $rarity, array $rarityTable): string
    {
        $roll = floor($rarity / 5);

        foreach ($rarityTable as $range => $outcome)
        {
            if (strpos($range, "-") !== false)
            {
                list($min, $max) = explode("-", $range);
                if ($roll >= intval($min) && $roll <= intval($max))
                    return $outcome;
            }
            else
            {
                if ($roll == intval($range))
                    return $outcome;
            }
        }

        // default to common
        return reset($rarityTable);
    }
}
