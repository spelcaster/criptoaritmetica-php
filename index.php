#!/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use EvoComp\MutationOperator\SwapMutationOperator;
use EvoComp\CrossoverOperator\CycleCrossoverOperator;
use EvoComp\CrossoverOperator\PartiallyMappedCrossoverOperator;
use EvoComp\GeneticAlgorithm;

$allowGeneRepetition = false;
$allowSameParentCrossover = false;

$selectionEngine = new RouletteWheelSelection($allowSameParentCrossover);
//$selectionMethod = new RankSelection($this->allowSameParentCrossover);
//$selectionMethod = new TournamentSelection($this->allowSameParentCrossover);
//$selectionMethod = new ElitismSelection($this->allowSameParentCrossover);

$crossoverOperator = new CycleCrossoverOperator();
//$crossoverOperator = new PartiallyMappedCrossoverOperator();
//var_dump($crossoverOperator->crossover([0, 1,], [2, 3,]));
//var_dump($crossoverOperator->crossover([1, 2, 3, 4], [4, 3, 2, 1]));
//var_dump($crossoverOperator->crossover([1, 2, 3, 4], [4, 3, 2, 5]));
//var_dump($crossoverOperator->crossover([1, 2, 3, 4, 5, 6, 7], [5, 4, 6, 7, 2, 3, 1]));

$mutationOperator = new SwapMutationOperator();
//var_dump($c, $mutationOperator->mutation([0,1,2,3,4,5,6,7]));

$ga = new GeneticAlgorithm(
    100, //$populationSize
    50, // $generationLimit
    80, // $crossoverRate
    20, // $mutationRate
    20 // $elitistPreserveRate
);

$ga->setProblem("send", "more", "money")
    ->setAllowGeneRepetition($allowGeneRepetition)
    ->setAllowSameParentCrossover($allowSameParentCrossover)
    ->setSelectionEngine($selectionEngine)
    ->setCrossoverOperator($crossoverOperator)
    ->setMutationOperator($mutationOperator);

$executionId = uniqid();
$executionPath = "analysis" . DIRECTORY_SEPARATOR . $executionId;
mkdir($executionPath);

$totalExecution = 200;
$executionTime = 0.0;
$convergenceCounter = 0;
$avgConvergence = 0.0;
$avgTime = 0.0;

$benchmark = [
    'id' => $executionId,
    'run' => [],
    'stats' => [],
];

for ($i = 0; $i < $totalExecution; $i++) {
    print("Exectuion #{$i}\n");
    $result = [
        'id' => $i,
        'has_converged' => false,
        'generation_converged' => false,
        'execution_time' => -1,
    ];

    $executionStart = microtime(true);

    $ga->run();

    $runTime = microtime(true) - $executionStart;

    $executionTime += $runTime;

    $result['execution_time'] = $runTime;

    $result['has_converged'] = $ga->hasConverged();

    $result['generation_converged'] = $ga->getConvergedGeneration();

    $benchmark['run'][$i] = $result;

    if ($ga->hasConverged()) {
        ++$convergenceCounter;

        $snapshotFile = $executionPath . DIRECTORY_SEPARATOR . "{$i}-result.json";

        $result = [
            'population' => $ga->getPopulationSnapshot(),
            'solution' => $ga->getFirstSolution(),
        ];

        file_put_contents($snapshotFile, json_encode($result));
    }
}

$avgConvergence = $convergenceCounter / $totalExecution;
$avgTime = $executionTime / $totalExecution;

$benchmark['stats'] = [
    'execution_time' => $avgTime,
    'convergence_rate' => $avgConvergence,
];

var_dump($benchmark['id']);
var_dump($benchmark['stats']);

$benchmarkFile = $executionPath . DIRECTORY_SEPARATOR . "benchmark.json";
file_put_contents($benchmarkFile, json_encode($benchmark));
