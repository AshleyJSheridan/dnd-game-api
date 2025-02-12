<?php

namespace App\Services\Items;

use App\Models\CharProficiency;
use App\Models\GameItem;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WeaponItemService implements iItemService
{
    public function __construct(private Request $request)
    {}

    public function getItem(int $rarity)
    {
        $weapon = $this->getBaseWeapon();
        $originalName = $weapon->name;

        // 20% chance of a magic weapon
        if (rand(1, 100) <= 20)
        {
            // clone the original instance of the weapon to use as a base
            $weapon = $weapon->replicate();

            // roll 1-10 to discover first enchantment on weapon
            $roll = rand(1, 10);
            if ($roll <= 7)
            {
                $modifier = 1;
                $this->applyPlusModifier($weapon, $roll, $modifier);

                // some lucky weapons get a second effect, 1 in 20 chance
                if (rand(1, 20) === 20)
                {
                    $damageType = '';
                    $this->applyDamageEffect($weapon, $damageType);

                    // Greataxe of radiant doesn't have the right ring to it!
                    $weapon->name = ($damageType === 'radiant') ? "Radiant {$weapon->name}" : "{$weapon->name} of $damageType";
                    $weapon->rarity = 'very rare';
                }

                $weapon->name .= " +$modifier";
            }
            elseif ($roll === 8)
            {
                $monster = '';
                $this->applySlayingEffect($weapon, $monster);
                $weapon->name .= " of $monster slaying";
                $weapon->rarity = 'rare';
            }
            else
            {
                $damageType = '';
                $this->applyDamageEffect($weapon, $damageType);
                $weapon->name = ($damageType === 'radiant') ? "Radiant {$weapon->name}" : "{$weapon->name} of $damageType";
                $weapon->rarity = 'rare';
            }

            // check if weapon has already been generated and exists in DB
            $existingWeapon = GameItem::where('type', 'weapon')->where('name', $weapon->name)->first();
            if (!is_null($existingWeapon))
                return $existingWeapon;

            // rough change to base price of weapon based on rarity
            switch ($weapon->rarity)
            {
                case 'rare':
                    $weapon->cost += 800;
                    break;
                case 'very rare':
                    $weapon->cost += 1800;
                    break;
            }

            // TODO add a description for magical items
            $weapon->created_at = Carbon::now();
            $weapon->save();
        }

        return $weapon;
    }

    private function applyDamageEffect(GameItem &$weapon, string &$damageType): void
    {
        $damageTypes = ['fire', 'poison', 'ice', 'acid', 'thunder', 'lightning', 'radiant'];
        $damageType = $damageTypes[array_rand($damageTypes)];

        // add this in as a special affect rather than overriding the existing damage type of the weapon to preserve
        // the original type to allow total damage to be calculated correctly
        $weapon->special = json_encode(["extra_damage" => "1d6", "damage_type" => $damageType]);
    }

    private function applySlayingEffect(GameItem &$weapon, string &$monster): void
    {
        $monsters = ['dragon', 'giant', 'undead', 'demon', 'devil', 'aberration', 'construct', 'lycanthrope', 'fey',
            'elemental', 'fiend', 'vampire', 'ooze', 'celestial', 'swarm', 'golem', 'troll', 'orc', 'goblin', 'plant'];
        $monster = $monsters[array_rand($monsters)];

        $weapon->special = json_encode(["slaying" => "3d6", "creature" => $monster]);
    }

    private function applyPlusModifier(GameItem &$weapon, int $roll, string &$modifier): void
    {
        $modifier = ($roll === 7) ? 3 : (($roll === 6 || $roll === 5) ? 2 : 1);
        $weapon->rarity = ($roll === 7) ? 'very rare' : 'rare';

        // some weapons have a base damage level, so this applies modifier correctly, rather than a damage of 1+1
        if (is_numeric($weapon->damage))
        {
            $weapon->damage = intval($weapon->damage) + $modifier;
        }
        else
        {
            $weapon->damage .= "+$modifier";
        }
    }

    private function getBaseWeapon(): GameItem
    {
        $weaponProficiency = $this->request->get('proficiency', '');
        $weapon = null;

        if ($weaponProficiency !== '')
        {
            $proficiencyIds = CharProficiency::where('type', strtolower($weaponProficiency))->pluck('id')->toArray();
            $weapon = GameItem::where('type', 'weapon')
                ->where('rarity', 'common')
                ->whereIn('proficiency_id', $proficiencyIds)
                ->inRandomOrder()->first();
        }

        if ($weaponProficiency === '' || is_null($weapon))
        {
            $weapon = GameItem::where('type', 'weapon')
                ->where('rarity', 'common')
                ->inRandomOrder()->first();
        }

        return $weapon;
    }
}
