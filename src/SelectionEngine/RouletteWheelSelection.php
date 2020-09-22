<?php

namespace EvoComp\SelectionEngine;

/**
 * Class RouletteWheelSelection
 *
 * @author yourname
 */
class RouletteWheelSelection extends SelectionEngineAbstract
{
    protected function runSelection($selectionLimit)
    {
        $selected = [];
        $selectedPos = [];

        $i = 0;
        while ($i < $selectionLimit) {
            $chromosome = $this->draw();

            $key = array_keys($chromosome)[0];
            if (!$this->allowRepetition && isset($selectedPos[$key])) {
                continue;
            }

            $selectedPos[$key] = true;
            $selected[] = $chromosome[$key];

            ++$i;
        }

        return $selected;
    }

    protected function draw()
    {
        $prop = $this->getRelativeFitnessProp();

        $pointer = (float) mt_rand() / (float) mt_getrandmax(); // rand(0, 1)
        $epsilon = 0.0000000005;

        $prob = 0.0;
        for ($i = 0; $i < $this->populationSize; $i++) {
            $prob += $this->population[$i][$prop];

            if (($prob + $epsilon) < $pointer) {
                continue;
            }

            return [
                $i => $this->population[$i]['chromosome'],
            ];
        }

        $i = $this->populationSize - 1;

        return [
            $i => $this->population[$i]['chromosome'],
        ];
    }
}
