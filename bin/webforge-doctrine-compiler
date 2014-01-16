#!/usr/bin/env php
<?php

use Webforge\Console\Application;
use Webforge\Doctrine\Compiler\Console\CompileCommand;

$container = require __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'bootstrap.php';
$webforge = $container->getWebforge();

$console = new Application($container);
$console->addCommands(array(
  new CompileCommand(
    'compile-entities', $webforge->getSystemContainer()->getSystem()
  )
));

$console->run();