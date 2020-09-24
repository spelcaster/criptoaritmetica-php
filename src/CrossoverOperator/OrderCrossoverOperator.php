<?php

namespace EvoComp\CrossoverOperator;

/**
 * Class OrderCrossoverOperator
 * @author yourname
 */
class OrderCrossoverOperator implements CrossoverOperatorInterface
{
    public function crossover(array $parentA, array $parentB)
    {
        $size = count($parentA) - 1;

        $segment = $this->getSegment($size);

        if (($segment[1] - $segment[0]) == $size) { // entire chromosome selected
            return [
                $parentA, $parentB
            ];
        }

        $offspring1 = $this->getOffspring($parentA, $parentB, $segment);

        $offspring2 = $this->getOffspring($parentB, $parentA, $segment);

        return [
            $offspring1, $offspring2
        ];
    }

    protected function getSegment($size)
    {
        $segment = [];

        $i = 0;
        while ($i < 2) {
            $pos = mt_rand(0, $size);

            if (in_array($pos, $segment)) {
                continue;
            }

            $segment[] = $pos;
            ++$i;
        }

        sort($segment);

        return $segment;
    }

    protected function getOffspring($p1, $p2, $segment)
    {
        $offspring = [];

        for ($i = $segment[0]; $i <= $segment[1]; $i++) {
            $offspring[$i] = $p1[$i];
        }

        $divider = count($p1);

        $current = ($segment[1] + 1) % $divider;
        $j = $current;
        while ($j != $segment[0]) {
            if (!isset($p2[$current])) {
                var_dump($current, $p2);
                die();
            }
            $char = $p2[$current];

            if (in_array($char, $offspring)) {
                $current = ++$current % $divider;
                continue;
            }

            $offspring[$j] = $char;

            $j = ++$j % $divider;
            $current = ++$current % $divider;
        }

        ksort($offspring);

        return $offspring;
    }
}
