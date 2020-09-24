<?php

namespace EvoComp\CrossoverOperator;

/**
 * Class SinglePointCrossoverOperator
 */
class SinglePointCrossoverOperator implements CrossoverOperatorInterface
{
    public function crossover(array $parentA, array $parentB)
    {
        $size = count($parentA);

        if ($size < 3) {
            return [
                $parentA, $parentB
            ];
        } else if ($parentA == $parentB) {
            return [
                $parentA, $parentB
            ];
        }

        $point = $this->getPoint($size);

        $offspring1 = $this->getOffspring($parentA, $parentB, $point);
        $offspring2 = $this->getOffspring($parentB, $parentA, $point);

        return [
            $offspring1, $offspring2
        ];
    }

    /**
     * undocumented function
     *
     * @return void
     */
    public function getPoint($size)
    {
        $size -= 2;

        return mt_rand(1, $size);
    }

    protected function getOffspring(array $pA, array $pB, $point)
    {
        $size = count($pA);

        return array_merge(array_slice($pA, 0, $point), array_slice($pB, $point, ($size - $point)));
    }
}
