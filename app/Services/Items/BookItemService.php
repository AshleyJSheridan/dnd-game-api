<?php

namespace App\Services\Items;

use App\Models\GameItem;
use App\Models\GameSpell;

class BookItemService implements iItemService
{
    public function getItem(int $rarity)
    {
        $rarity = 65;

        if ($rarity >= 1 && $rarity <= 64)
        {
            // regular book
            var_dump('regular book');
        }
        else
        {
            $spellLevelRoll = rand(1, 100);
            $scale = .3;
            $normalized = ($spellLevelRoll - 1) / (100 - 1);
            $expValue = 1 - pow(1 - $normalized, $scale);
            $mappedValue = round($expValue * 9);

            $item = $this->getRandomSpellScrollByLevel($mappedValue);
        }

    }

    private function getRandomSpellScrollByLevel(int $level)
    {
        $spell = GameSpell::where('level', $level)
            ->inRandomOrder()->first();

        $scrollName = "Scroll of $spell->name";

        $spellScroll = GameItem::where('name', $scrollName)->first();

        // existing item has been found, return that
        if (!is_null($spellScroll))
            return $spellScroll;

        // scroll didn't exist, make it, add it to the DB, and return it
        $genericItem = GameItem::where('type', 'book')->where('name', "Spell scroll (level {$spell->level})")->first();

        //var_dump(json_encode(["spell" => $spell->id]));
        return GameItem::create([
            'name' => $scrollName,
            'description' => $spell->description,
            'cost' => $genericItem->cost,
            'cost_unit' => 'gp',
            'type' => 'book',
            'rarity' => $genericItem->rarity,
            'special' => json_encode(["spell" => $spell->id]),
            'generated' => 'yes',
            'weight' => 0,
        ]);
    }
}
