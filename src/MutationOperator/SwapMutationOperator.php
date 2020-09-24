<?php

namespace EvoComp\MutationOperator;

/**
 * Class SwapMutationOperator
 */
class SwapMutationOperator implements MutationOperatorInterface
{
    protected $swapLimit;

    /**
     * @param $swapLimit = 1
     */
    public function __construct($swapLimit = 1)
    {
        $this->swapLimit = $swapLimit;
    }

    /**
     * undocumented function
     *
     * @return void
     */
    public function mutation(array $chromosome)
    {
        $swapCounter = 0;
        $size = count($chromosome) - 1;

        while ($swapCounter < $this->swapLimit) {
            $posA = mt_rand(0, $size);
            $posB = mt_rand(0, $size);

            if (!$this->applySwap($posA, $posB, $chromosome)) {
                continue;
            }

            ++$swapCounter;
        }

        return $chromosome;
    }

    /**
     * undocumented function
     *
     * @return void
     */
    protected function applySwap($posA, $posB, array &$chromosome)
    {
        if ($posA == $posB) {
            return false;
        }

        $tmp = $chromosome[$posB];
        $chromosome[$posB] = $chromosome[$posA];
        $chromosome[$posA] = $tmp;

        return true;
    }
}
