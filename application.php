#!/usr/bin/env php
<?php declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application;
use App\Command\DbDumpCommand;

$containerBuilder = new ContainerBuilder();
$loader = new YamlFileLoader($containerBuilder, new FileLocator(__DIR__ . '/config'));
$loader->load('services.yaml');

/** @var DbDumpCommand $dbDumpCommand */
$dbDumpCommand = $containerBuilder->get('dbDumpCommand');

$application = new Application();
$application->add($dbDumpCommand);

$application->run();
