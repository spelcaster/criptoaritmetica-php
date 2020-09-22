<?php

namespace EvoComp\CrossoverOperator;

/**
 * Class PartiallyMappedCrossoverOperator
 * @author yourname
 */
class PartiallyMappedCrossoverOperator implements CrossoverOperatorInterface
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

        $other = [];
        $me = [];
        for ($i = $segment[0]; $i <= $segment[1]; $i++) {
            if (in_array($p2[$i], $offspring)) {
                continue;
            }

            $other[] = $p2[$i];
            $me[] = $p1[$i];
        }

        for ($i = 0; $i < count($other); $i++) {
            $myValue = $me[$i];

            $key = array_search($myValue, $p2);
            while (isset($offspring[$key])) {
                $myValue = $p1[$key];
                $key = array_search($myValue, $p2);
            }

            $offspring[$key] = $other[$i];
        }

        for ($i = 0; $i < count($p2); $i++) {
            if (isset($offspring[$i])) {
                continue;
            }

            $offspring[$i] = $p2[$i];
        }

        ksort($offspring);

        return $offspring;
    }
}
