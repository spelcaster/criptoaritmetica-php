<?php

namespace EvoComp;

use EvoComp\CrossoverOperator\CrossoverOperatorInterface;
use EvoComp\MutationOperator\MutationOperatorInterface;
use EvoComp\SelectionEngine\SelectionEngineAbstract;
use EvoComp\SelectionEngine\ElitismSelection;
use RuntimeException;

/**
 * Class EvoComp\GeneticAlgorithm
 */
class GeneticAlgorithm
{
    protected $populationSize;

    protected $generationLimit;

    protected $crossoverRate;

    protected $mutationRate;

    protected $elitismPreserveRate;

    protected $chromosomeSize;

    protected $population;

    protected $problem;

    protected $charMap;

    protected $allowSameParentCrossover = false;

    protected $allowGeneRepetition = false;

    protected $selectionEngine;

    protected $mutationOperator;

    protected $crossoverOperator;

    protected $hasConverged;

    protected $generationConverged;

    protected $firstSolution;

    protected $populationSnapshot;

    protected $reinsertionMethod;

    protected $maxValue;

    protected $resultMap;

    /**
     * @param $populationSize = 100
     * @param $generationLimit = 50
     * @param $mutationRate = 2
     * @param $elitismPreserveRate = 20
     */
    public function __construct(
        $populationSize = 100,
        $generationLimit = 50,
        $crossoverRate = 80,
        $mutationRate = 2,
        $elitismPreserveRate = 20
    ) {
        $this->populationSize = $populationSize;
        $this->generationLimit = $generationLimit;
        $this->crossoverRate = $crossoverRate / 100;
        $this->mutationRate = $mutationRate / 100;
        $this->elitismPreserveRate = $elitismPreserveRate / 100;

        $this->chromosomeSize = 0;
        $this->allowSameParentCrossover = true;

        $this->population = [];
        $this->problem = [];

        // analysis
        $this->hasConverged = false;
        $this->convergedGeneration = false;
        $this->firstSolution = false;
        $this->populationSnapshot = [];

        $this->reinsertionMethod = 'r1';

        $this->weightMap = [];

        $this->maxValue = 0;

        $this->resultMap = [];
    }

    protected function reset()
    {
        $this->population = [];
    }

    public function __destruct()
    {
        unset($this->population);
    }

    public function setAllowGeneRepetition(bool $flag = false)
    {
        $this->allowSameParentCrossover = $flag;

        return $this;
    }

    public function setAllowSameParentCrossover(bool $flag = false)
    {
        $this->allowSameParentCrossover = $flag;

        return $this;
    }

    public function setSelectionEngine(SelectionEngineAbstract $engine)
    {
        $this->selectionEngine = $engine;

        return $this;
    }

    public function setCrossoverOperator(CrossoverOperatorInterface $operator)
    {
        $this->crossoverOperator = $operator;

        return $this;
    }

    public function setMutationOperator(MutationOperatorInterface $operator)
    {
        $this->mutationOperator = $operator;

        return $this;
    }

    public function hasConverged()
    {
        return $this->hasConverged;
    }

    public function getConvergedGeneration()
    {
        return $this->convergedGeneration;
    }

    public function getFirstSolution()
    {
        return $this->firstSolution;
    }

    public function getPopulationSnapshot()
    {
        return $this->populationSnapshot;
    }

    public function setReinsertionMethod($reinsertionMethod = 'r1')
    {
        $this->reinsertionMethod = strtolower($reinsertionMethod);

        return $this;
    }

    /**
     * Equation:
     *      $word3 = $word1 + $word2
     */
    public function setProblem($word1, $word2, $word3)
    {
        $this->problem = [
            $word1,
            $word2,
            $word3,
        ];

        // we create the char map based on the words
        $newWord = "{$word1}{$word2}{$word3}";

        $charMap = "";

        for ($i = 0; $i < strlen($newWord); $i++) {
            if (strpos($charMap, $newWord[$i]) !== false) {
                continue;
            }

            $charMap = $newWord[$i] . $charMap;
        }

        $this->charMap = $charMap;

        $this->chromosomeSize = strlen($charMap);

        $maxScore = strlen($charMap);

        if (!$this->weightMap) {
            for ($i = 0; $i < strlen($word3); $i++) {
                $pos = strpos($this->charMap, $word3[$i]);

                if (isset($this->weightMap[$pos])) {
                    continue;
                }

                $this->weightMap[$pos] = $maxScore;
                --$maxScore;
            }

            for ($i = 0; $i < $this->chromosomeSize; $i++) {
                if (isset($this->weightMap[$i])) {
                    continue;
                }

                $this->weightMap[$i] = $maxScore;
                --$maxScore;
            }

            ksort($this->weightMap);
        }

        $this->setMaxValue($word3);

        $this->setResultMap($word3);

        return $this;
    }

    protected function setMaxValue($word)
    {
        $value = 0;

        $wordLen = strlen($word);
        for ($i = 0; $i < $wordLen; $i++) {
            $value += (pow(10, ($wordLen - $i - 1)) * 9);
        }

        $this->maxValue = $value + 1;
    }

    protected function setResultMap($word)
    {
        $rWord = strrev($word);

        for ($i = 0; $i < strlen($word); $i++) {
            $char = $rWord[$i];
            $pos = strpos($this->charMap, $char);

            $this->resultMap[$i] = $pos;
        }
    }

    protected function generatePopulation()
    {
        $this->population = [];

        for ($i = 0; $i < $this->populationSize; $i++) {
            $this->population[] = $this->generateChromosome();
        }
    }

    protected function generateChromosome()
    {
        $chromosome = [];

        $i = 0;
        while ($i < $this->chromosomeSize) {
            $gene = $this->getGene();

            if (!$this->allowGeneRepetition && in_array($gene, $chromosome)) {
                continue;
            }

            $chromosome[$i] = $gene;
            ++$i;
        }

        return $chromosome;
    }

    /**
     * Each gene in a chromosome is a number between 0 and 9 in this GA
     */
    protected function getGene()
    {
        return mt_rand(0, 9);
    }

    public function getWordValue($word, array $chromosome)
    {
        $value = 0;

        $wordLen = strlen($word);
        for ($i = 0; $i < $wordLen; $i++) {
            $key = $word[$i];

            $charPos = strpos($this->charMap, $key);

            if ($charPos === false) {
                throw new RuntimeException("Character not found in the dictionary");
            }

            $charVal = $chromosome[$charPos];

            $value += (pow(10, ($wordLen - $i - 1)) * $charVal);
        }

        return $value;
    }

    protected function fitness($word1, $word2, $word3)
    {
        return $this->maxValue - abs($word3 - ($word1 + $word2));
    }

    protected function fitness2($word1, $word2, $word3, $chromosome)
    {
        $r = $word1 + $word2;

        $score = 1;

        for ($i = 0; $i < count($this->resultMap); $i++) {
            $dVal = $r / 10;
            $value = $r - (intval($dVal) * 10);

            $r = intval($dVal);

            $rPos = $this->resultMap[$i];
            if ($chromosome[$rPos] != $value) {
                continue;
            }

            $score += 1;
        }

        return $score;
    }

    protected function fitness3($word1, $word2, $word3, $chromosome)
    {
        $r = $word1 + $word2;

        $score = 1;
        for ($i = 0; $i < count($this->resultMap); $i++) {
            $dVal = $r / 10;
            $value = $r - (intval($dVal) * 10);

            $r = intval($dVal);

            $rPos = $this->resultMap[$i];
            if ($chromosome[$rPos] != $value) {
                continue;
            }

            $score += $this->weightMap[$rPos];
        }

        return $score;
    }

    public function run()
    {
        $epsilon = 0.0000000001;

        $this->hasConverged = false;
        $this->convergedGeneration = false;
        $this->firstSolution = false;
        $this->populationSnapshot = [];

        $offspringSize = (int) ($this->crossoverRate * $this->populationSize);
        $mutationLimit = (int) ($this->mutationRate * $this->populationSize);

        $this->generatePopulation();

        $generation = 0;
        while ($generation < $this->generationLimit) {
            $globalFitness = 0;

            //population fitness
            $populationFitness = [];
            $populationFitnessSum = 0;
            for ($i = 0; $i < $this->populationSize; $i++) {
                $chromosome = $this->population[$i];

                $word1 = $this->getWordValue($this->problem[0], $chromosome);
                $word2 = $this->getWordValue($this->problem[1], $chromosome);
                $word3 = $this->getWordValue($this->problem[2], $chromosome);

                //$fitness = $this->fitness($word1, $word2, $word3);
                //$fitness = $this->fitness2($word1, $word2, $word3, $chromosome);
                $fitness = $this->fitness3($word1, $word2, $word3, $chromosome);

                if (($word1 + $word2) == $word3) {
                    if (!$this->hasConverged) { // test if has previously converged
                        $this->hasConverged = true;
                        $this->convergedGeneration = $generation;
                        $this->firstSolution = [
                            'dictionary' => $this->charMap,
                            'chromosome' => $chromosome,
                            'w1' => $word1,
                            'w2' => $word2,
                            'w3' => $word3,
                            'result' => ($word1 + $word2),
                        ];
                        $this->populationSnapshot = $this->population;
                    }
                }

                $populationFitness[$i] = [
                    'chromosome' => $chromosome,
                    'fitness' => $fitness,
                    'local_relative_fitness' => 0.0,
                    'global_relative_fitness' => 0.0,
                    'type' => 'parent',
                ];

                $populationFitnessSum += $fitness;
            }

            for ($i = 0; $i < $this->populationSize; $i++) {
                $populationFitness[$i]['local_relative_fitness'] = $populationFitness[$i]['fitness'] / $populationFitnessSum;
            }

            $selectedParents = $this->selection($populationFitness, $offspringSize);

            $offspring = $this->crossover($selectedParents, $offspringSize);

            $this->mutation($offspring, $offspringSize, $mutationLimit);


            //offspring fitness
            $offspringFitness = [];
            $offspringFitnessSum = 0;
            for ($i = 0; $i < $offspringSize; $i++) {
                $chromosome = $offspring[$i];

                $word1 = $this->getWordValue($this->problem[0], $chromosome);
                $word2 = $this->getWordValue($this->problem[1], $chromosome);
                $word3 = $this->getWordValue($this->problem[2], $chromosome);

                //$fitness = $this->fitness($word1, $word2, $word3);
                //$fitness = $this->fitness2($word1, $word2, $word3, $chromosome);
                $fitness = $this->fitness3($word1, $word2, $word3, $chromosome);

                $offspringFitness[$i] = [
                    'chromosome' => $chromosome,
                    'fitness' => $fitness,
                    'local_relative_fitness' => 0.0,
                    'global_relative_fitness' => 0.0,
                    'type' => 'offspring',
                ];

                $offspringFitnessSum += $fitness;
            }

            $globalFitnessSum = $populationFitnessSum + $offspringFitnessSum;

            for ($i = 0; $i < $offspringSize; $i++) {
                $offspringFitness[$i]['local_relative_fitness'] = $offspringFitness[$i]['fitness'] / $offspringFitnessSum;
                $offspringFitness[$i]['global_relative_fitness'] = $offspringFitness[$i]['fitness'] / $globalFitnessSum;
            }

            for ($i = 0; $i < $this->populationSize; $i++) {
                $populationFitness[$i]['global_relative_fitness'] = $populationFitness[$i]['fitness'] / $globalFitnessSum;
            }

            $this->updatePopulation($populationFitness, $offspringFitness);

            unset($offspring);
            unset($offspringFitness);
            unset($populationFitness);

            ++$generation;
        }
    }

    protected function selection($population, $selectionLimit)
    {
        $this->selectionEngine->setPopulation($population);

        return $this->selectionEngine->select($selectionLimit);
    }

    protected function crossover(array $selectedPopulation, $size)
    {
        $offspring = [];
        for ($i = 0; $i < $size; $i+=2) {
            $j = $i + 1;

            $newOffspring = $this->crossoverOperator->crossover(
                $selectedPopulation[$i], $selectedPopulation[$j]
            );

            if (!is_array($newOffspring)) {
                throw new RuntimeException("Crossover Operator failed to generate pair of children");
            }

            $offspring = array_merge($offspring, $newOffspring);
        }

        return $offspring;
    }

    protected function mutation(array &$offspring, $size, $totalMutation)
    {
        $mutationCache = [];

        $i = 0;
        while ($i < $totalMutation) {
            $pos = mt_rand(0, ($size - 1));

            if (isset($mutationCache[$pos])) { // same chromosome cannot suffer multiple mutation
                continue;
            }

            $offspring[$pos] = $this->mutationOperator->mutation(
                $offspring[$pos]
            );

            ++$i;
        }
    }

    protected function updatePopulation(array $population, array $offspring)
    {
        switch ($this->reinsertionMethod) {
        case 'r1':
            $selectionMethod = new ElitismSelection();

            $selectionMethod->setPopulation(array_merge($population, $offspring))
                ->setIsGlobal(true);

            $nextGen = $selectionMethod->select($this->populationSize);

            shuffle($nextGen); // shuffle next generation

            $this->population = $nextGen;

            return;

        case 'r2':
            $selectionMethod = new ElitismSelection();

            $selectionMethod->setPopulation(array_merge($population, $offspring))
                ->setIsGlobal(true)
                ->setElitismRate($this->elitismPreserveRate);

            $selectedParents = $selectionMethod->select(0); // elitism rate will be used

            $nextGen = array_column($offspring, 'chromosome');

            $nextGen = array_merge($nextGen, $selectedParents);

            shuffle($nextGen);

            $this->population = $nextGen;

            return;

        default:
            throw new RuntimeException("Reinsertion Methods not informed");
        }
    }
}
