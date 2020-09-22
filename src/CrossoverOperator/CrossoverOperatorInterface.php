<?php

namespace EvoComp\CrossoverOperator;

/**
 * Interface CrossoverOperatorInterface
 * @author yourname
 */
interface CrossoverOperatorInterface
{
    public function crossover(array $parentA, array $parentB);
}
