<?php

namespace App\Services;

use Illuminate\Http\Request;

class DiceRollService
{
    private $availableDiceSides = [4, 6, 8, 10, 12, 20];

    public function getDiceSidesFromRequest(Request $request): array
    {
        $rolls = [];

        try {
            $jsonData = json_decode($request->getContent())->dice;

            foreach ($jsonData as $sides => $amount)
            {
                $sides = intval(substr($sides, 1));

                if(!in_array($sides, $this->availableDiceSides))
                    continue;

                $rolls["d$sides"] = $this->roll($sides, $amount);
            }
        } catch (\Exception $e) {
            // TODO do something here, probably means invalid JSON input
        }

        // TODO store these rolls somewhere against a user session to prevent cheating
        return $rolls;
    }

    private function roll($side, $amount): array
    {
        $rolls = [];

        for ($i = 0; $i < $amount; $i++)
        {
            $rolls[] = rand(1, $side);
        }

        return $rolls;
    }
}
