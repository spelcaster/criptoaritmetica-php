<?php

namespace EvoComp;

use EvoComp\CrossoverOperator\CrossoverOperatorInterface;
use EvoComp\MutationOperator\MutationOperatorInterface;
use EvoComp\SelectionEngine\RouletteWheelSelection;
use EvoComp\SelectionEngine\RankSelection;
use EvoComp\SelectionEngine\TournamentSelection;
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

    protected $elitistPreserveRate;

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

    /**
     * @param $populationSize = 100
     * @param $generationLimit = 50
     * @param $mutationRate = 2
     * @param $elitistPreserveRate = 20
     */
    public function __construct(
        $populationSize = 100,
        $generationLimit = 50,
        $crossoverRate = 80,
        $mutationRate = 2,
        $elitistPreserveRate = 20
    ) {
        $this->populationSize = $populationSize;
        $this->generationLimit = $generationLimit;
        $this->crossoverRate = $crossoverRate / 100;
        $this->mutationRate = $mutationRate / 100;
        $this->elitistPreserveRate = $elitistPreserveRate;

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

        return $this;
    }

    protected function generatePopulation()
    {
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
        //return abs($word3 - ($word1 + $word2));
        return 100000 - abs($word3 - ($word1 + $word2));
        //return 10001 - abs($word3 - ($word1 + $word2));
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
            print("\tGeneration #{$generation}\n");

            $globalFitness = 0;

            //population fitness
            $populationFitness = [];
            $populationFitnessSum = 0;
            for ($i = 0; $i < $this->populationSize; $i++) {
                $chromosome = $this->population[$i];

                $word1 = $this->getWordValue($this->problem[0], $chromosome);
                $word2 = $this->getWordValue($this->problem[1], $chromosome);
                $word3 = $this->getWordValue($this->problem[2], $chromosome);

                $fitness = $this->fitness($word1, $word2, $word3);

                if (($word1 + $word2) == $word3) {
                    if (!$this->hasConverged) { // test if has previously converged
                        $this->hasConverged = true;
                        $this->convergedGeneration = $generation;
                        $this->firstSolution = $chromosome;
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

                $fitness = $this->fitness($word1, $word2, $word3);

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
        case 'r2':
        default:
            throw new RuntimeException("Reinsertion Methods not informed");
        }
    }
}
