<?php

namespace App\Services\Items;

use App\Models\GameItem;
use App\Models\GameSpell;

class BookItemService implements iItemService
{
    public function getItem(int $rarity)
    {
        $rarity = 1;

        if ($rarity >= 1 && $rarity <= 64)
        {
            // regular book
            $bookType = $this->getRandomBookType();
            $bookAdjective = $this->getRandomBookAdjective();
            $indefiniteArticle = in_array(strtolower(mb_substr($bookType, 0, 1)), ['a', 'e', 'i', 'o', 'u']) ? 'An' : 'A';

            $bookTitle = $this->getRandomBookTitle($bookType, $bookAdjective);
            $bookDescription = "$indefiniteArticle $bookType " . $this->getRandomDescription();

            $book = GameItem::where('name', $bookTitle)->first();
            if (!is_null($book))
                return $book;

            return GameItem::create([
                'name' => $bookTitle,
                'description' => $bookDescription,
                'cost' => 5,
                'cost_unit' => 'gp',
                'type' => 'book',
                'rarity' => 'common',
                'generated' => 'yes',
                'weight' => 0,
            ]);
        }
        else
        {
            // spell scroll
            $curvedSpellScrollLevel = $this->getSpellScrollLevel();

            $item = $this->getRandomSpellScrollByLevel($curvedSpellScrollLevel);
        }

    }

    private function getRandomDescription(): string
    {
        $descriptions = [
            "filled with cryptic knowledge and forbidden spells.",
            "said to drive its readers to madness.",
            "containing the lost secrets of a forgotten civilization.",
            "penned by an unknown sorcerer from the age of dragons.",
            "of magical beasts and their weaknesses, written by a famed hunter.",
            "detailing prophecies that have yet to come to pass.",
            "infused with the power of the cosmos itself.",
            "hidden deep within the ruins of an ancient kingdom.",
            "detailing the rites of an order long lost to time.",
            "filled with the names of those who have bargained with the gods.",
            "cookbook filled with strange and unusual recipes for miniature giant space hamsters.",
            "filled with a curious collection of humorously shaped vegetables.",
            "full of very boring details of a subject nobody thought to write about until now."
        ];

        return $descriptions[array_rand($descriptions)];
    }

    private function getRandomBookType(): string
    {
        $types = [
            "Tome", "Grimoire", "Codex", "Scroll", "Manuscript", "Volume", "Lexicon", "Compendium", "Chronicle", "Scripture",
            "Bestiary", "Necronomicon", "Annals", "Saga", "Prophecy", "Edict", "Gospel", "Mythos", "Parchment", "Ledger",
            "Ancient text", "Sacred text", "Legendary manuscript", "Cursed tome", "Arcane volume", "Forbidden scripture",
            "Mysterious ledger",
        ];

        return $types[array_rand($types)];
    }

    private function getRandomBookAdjective(): string
    {
        $adjectives = [
            "Ancient", "Forbidden", "Mystic", "Arcane", "Cursed", "Legendary", "Enchanted", "Lost", "Dark", "Eldritch",
            "Hallowed", "Infernal", "Celestial", "Divine", "Chaotic", "Runed", "Secret", "Shadowed", "Hidden", "Sacred"
        ];

        return $adjectives[array_rand($adjectives)];
    }

    private function getRandomBookTitle(string $bookType, string $adjective): string
    {
        $themes = [
            "of the Forgotten Realms", "of the Shadowfell", "of the Feywild", "of the Abyss", "of the Nine Hells",
            "of the Lich King", "of the Arcane Order", "of the Lost Gods", "of the Underdark", "of the Blood Moon",
            "of the Dragon Lords", "of the Crimson Mage", "of the Eternal Night", "of the Cursed One", "of the Eldritch Horrors",
            "of the Astral Planes", "of the Haunted Keep", "of the Silver Sages", "of the Sunken Kingdom", "of the Rune Masters"
        ];

        $theme = $themes[array_rand($themes)];

        return "$adjective $bookType $theme";
    }

    private function getSpellScrollLevel(): int
    {
        $spellLevelRoll = rand(1, 100);
        $scale = .3;
        $normalized = ($spellLevelRoll - 1) / (100 - 1);
        $expValue = 1 - pow(1 - $normalized, $scale);

        return round($expValue * 9);
    }

    private function getRandomSpellScrollByLevel(int $level): GameItem
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
