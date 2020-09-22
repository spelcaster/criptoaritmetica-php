<?php

namespace EvoComp\SelectionEngine;

/**
 * Class RankSelection
 * @author yourname
 */
class RankSelection extends SelectionEngineAbstract
{
    protected function runSelection($selectionLimit)
    {
        $prop = $this->getRelativeFitnessProp();
        $epsilon = 0.0000000005;

        usort($this->population, function ($a, $b) use ($prop, $epsilon) {
            if (($b[$prop] + $epsilon) > $a[$prop]) {
                return 0;
            }

            return 1;
        });

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
        $pos = mt_rand(0, $this->populationSize);

        return [
            $pos => $this->population[$pos]['chromosome'],
        ];
    }
}
