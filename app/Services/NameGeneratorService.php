<?php

namespace App\Services;

use App\Models\CharName;

class NameGeneratorService
{
    private $chain = [];
    private int $order;

    public function __construct(string $nameType, int $order = 3)
    {
        $sourceNames = CharName::where('type', $nameType)->get('name')->pluck('name')->toArray();

        $this->order = 2;
        $this->buildChain($sourceNames);
    }

    public function generateName(int $minLength = 3, int $maxLength = 19): string
    {
        do {
            $key = array_rand($this->chain); // Random starting point
            $name = $key;

            while (strlen($name) < $maxLength) {
                if (!isset($this->chain[$key]) || array_sum($this->chain[$key]) < 1)
                    break;

                $choices = $this->chain[$key];
                $nextChar = $this->weightedRandomChoice($choices);

                if ($nextChar === "$")
                    break; // End of name marker

                $name .= $nextChar;
                $key = substr($name, -$this->order);
            }

            // Remove markers and capitalize
            $generatedName = ucfirst(trim($name, "^$"));
        } while (strlen($generatedName) < $minLength);

        return $generatedName;
    }

    private function buildChain(array $names)
    {
        foreach ($names as $name)
        {
            $name = strtolower("^$name$"); // Add start (^) and end ($) markers
            $length = strlen($name);

            for ($i = 0; $i <= $length - $this->order; $i++)
            {
                $key = substr($name, $i, $this->order);
                $next = $i + $this->order < $length ? $name[$i + $this->order] : null;

                if (!isset($this->chain[$key]))
                {
                    $this->chain[$key] = [];
                }

                if ($next !== null)
                {
                    if (!isset($this->chain[$key][$next]))
                    {
                        $this->chain[$key][$next] = 0;
                    }

                    $this->chain[$key][$next]++;
                }
            }
        }
    }

    private function weightedRandomChoice(array $choices)
    {
        if(array_sum($choices) < 1)
            var_dump($choices);

        $total = array_sum($choices) ?? 1;
        $rand = mt_rand(1, $total);
        $current = 0;

        foreach ($choices as $choice => $weight)
        {
            $current += $weight;
            if ($rand <= $current)
                return $choice;
        }

        return null;
    }
}
