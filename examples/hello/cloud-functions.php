<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\PhpExecutableFinder;

# includes the autoloader for libraries installed with composer
require __DIR__ . '/vendor/autoload.php';

$application = new Application('Google Cloud Functions');

// detect label command
$application->add((new Command('start'))
    ->addArgument('target', InputArgument::REQUIRED, 'The target function.')
    ->addOption(
        'signature-type',
        '',
        InputOption::VALUE_REQUIRED,
        'The target type. can be "http" or "event"',
        'http'
    )
    ->addOption(
        'source',
        '',
        InputOption::VALUE_REQUIRED,
        'The functions file to load. Defaults to "index.php in this directory.'
    )
    ->addOption(
        'port',
        '',
        InputOption::VALUE_REQUIRED,
        'The port for the web server. Uses $PORT environment variable if set.',
        getenv('PORT') ?: 8080
    )
    ->addOption(
        'host',
        '',
        InputOption::VALUE_REQUIRED,
        'The host address to serve the application on.',
        '127.0.0.1'
    )
    ->setDescription('Run the GCF functions framework locally')
    ->setCode(function ($input, $output) {
        $routerPath = 'vendor/google/cloud-functions-framework/router.php';
        $cmd = sprintf(
             'FUNCTION_SOURCE=%s ' .
             'FUNCTION_TARGET=%s ' .
             'FUNCTION_SIGNATURE_TYPE=%s ' .
            '%s -S %s:%s %s',
            escapeshellarg($input->getOption('source')),
            escapeshellarg($input->getArgument('target')),
            escapeshellarg($input->getOption('signature-type')),
            escapeshellarg((new PhpExecutableFinder)->find(false)),
            $input->getOption('host'),
            $input->getOption('port'),
            $routerPath
        );
        $output->writeln($cmd);
        passthru($cmd);
    })
);

$application->run();
