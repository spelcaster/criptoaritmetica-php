#!/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use EvoComp\CrossoverOperator\CycleCrossoverOperator;
use EvoComp\CrossoverOperator\PartiallyMappedCrossoverOperator;
use EvoComp\CrossoverOperator\SinglePointCrossoverOperator;
use EvoComp\CrossoverOperator\OrderCrossoverOperator;
use EvoComp\GeneticAlgorithm;
use EvoComp\MutationOperator\SwapMutationOperator;
use EvoComp\SelectionEngine\ElitismSelection;
use EvoComp\SelectionEngine\RankSelection;
use EvoComp\SelectionEngine\RouletteWheelSelection;
use EvoComp\SelectionEngine\TournamentSelection;

$allowGeneRepetition = false;
$allowSameParentCrossover = false;

//$tm = 2; // TM1
//$tm = 10; // TM2
//$tm = 20; // TM3
$tm = 1;

//$selectionEngine = new RouletteWheelSelection($allowSameParentCrossover); // S1
$selectionEngine = new RankSelection($allowSameParentCrossover); // S2
//$selectionEngine = new TournamentSelection($allowSameParentCrossover); // S3
//$selectionEngine = new ElitismSelection($allowSameParentCrossover);

//$crossoverOperator = new CycleCrossoverOperator(); // C1
$crossoverOperator = new PartiallyMappedCrossoverOperator(); // C2
//$crossoverOperator = new SinglePointCrossoverOperator();
//$crossoverOperator = new OrderCrossoverOperator();

$mutationOperator = new SwapMutationOperator();

$ga = new GeneticAlgorithm(
    240, //$populationSize
    25, // $generationLimit
    85, // $crossoverRate
    $tm, // $mutationRate
    15 // $elitistPreserveRate
);

$ga->setReinsertionMethod('r1');  // R1
//$ga->setReinsertionMethod('r2'); // R2

$ga->setProblem("send", "more", "money");
//$ga->setProblem("eat", "that", "apple");
//$ga->setProblem("cross", "roads", "danger");
//$ga->setProblem("coca", "cola", "oasis");
//$ga->setProblem("donald", "gerald", "robert");

$ga->setAllowGeneRepetition($allowGeneRepetition)
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

$avgConvergedGeneration = 0.0;

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
        'converged_generation' => -1,
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
        $avgConvergedGeneration += $ga->getConvergedGeneration();

        $result = [
            'generation' => $ga->getConvergedGeneration(),
            'population' => $ga->getPopulationSnapshot(),
            'first_solution' => $ga->getFirstSolution(),
        ];

        file_put_contents($snapshotFile, json_encode($result));
    }
}

if ($convergenceCounter) {
    $avgConvergence = $convergenceCounter / $totalExecution;
    $avgConvergedGeneration = $avgConvergedGeneration / $convergenceCounter;
}

$avgTime = $executionTime / $totalExecution;

$benchmark['stats'] = [
    'execution_time' => $avgTime,
    'convergence_rate' => $avgConvergence,
    'average_generation' => $avgConvergedGeneration,
];

print_r([$benchmark['id'], $benchmark['stats']]);
print("\n");

$benchmarkFile = $executionPath . DIRECTORY_SEPARATOR . "benchmark.json";
file_put_contents($benchmarkFile, json_encode($benchmark));
