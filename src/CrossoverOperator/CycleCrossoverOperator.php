<?php

namespace EvoComp\CrossoverOperator;

/**
 * Class CycleCrossoverOperator
 * @author yourname
 */
class CycleCrossoverOperator implements CrossoverOperatorInterface
{
    public function crossover(array $parentA, array $parentB)
    {
        $cycle = $this->getCycle($parentA, $parentB);

        if (!$cycle) {
            return [
                $parentA, $parentB
            ];
        }

        $offspring1 = $this->getOffspring($parentA, $parentB, $cycle);
        $offspring2 = $this->getOffspring($parentB, $parentA, $cycle);

        return [
            $offspring1, $offspring2
        ];
    }

    /**
     * undocumented function
     *
     * @return void
     */
    public function getCycle(array $pA, array $pB)
    {
        $cycleStartPos = 0;
        $cycle = [$cycleStartPos];
        $initialVal = $pA[$cycleStartPos];
        $currentPos = $cycleStartPos;

        while (true) { // no cycle found between parents, then stop
            $currentVal = $pB[$currentPos];

            if ($currentVal == $initialVal) {
                break;
            }

            $currentPos = array_search($currentVal, $pA);

            if ($currentPos === false) { // cycle not found update start position
                ++$cycleStartPos;

                if ($cycleStartPos == count($pA)) {
                    $cycle = [];
                    break;
                }

                $cycle = [$cycleStartPos];
                $initialVal = $pA[$cycleStartPos];
                $currentPos = $cycleStartPos;

                continue;
            }

            $cycle[] = $currentPos;
        }

        sort($cycle);

        return $cycle;
    }

    protected function getOffspring(array $pA, array $pB, array $keepPos)
    {
        $offspring = [];

        for ($i = 0; $i < count($pA); $i++) {
            if (in_array($i, $keepPos)) {
                $offspring[$i] = $pA[$i];
                continue;
            }

            $offspring[$i] = $pB[$i];
        }

        return $offspring;
    }
}
