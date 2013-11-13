<?php

namespace Webforge\Doctrine\Compiler\Console;

use Symfony\Component\Console\Input\InputArgument;
use Webforge\Doctrine\Console\AbstractDoctrineCommand;
use Webforge\Console\CommandInput;
use Webforge\Console\CommandOutput;
use Webforge\Console\CommandInteraction;
use Webforge\Common\System\System;
use Webforge\Common\JS\JSONConverter;

use Webforge\Doctrine\Compiler\Compiler;
use Webforge\Doctrine\Compiler\EntityGenerator;
use Webforge\Doctrine\Compiler\ModelValidator;
use Webforge\Doctrine\Annotations\Writer as AnnotationsWriter;
use Webforge\Doctrine\Compiler\EntityMappingGenerator;
use Webforge\Doctrine\Compiler\Inflector;

class CompileCommand extends AbstractDoctrineCommand {

  protected $name = 'orm:compile';

  protected $compiler;
  protected $webforge;

  protected function configure() {
    $this
      ->setName($this->name)
      ->setDescription(
        'Compiles the entities in the model into real php entities.'
      )
      ->setHelp(
        $this->getName()." etc/doctrine/model.json lib/ACME/Blog/Entities\n".
        'Compiles the entities in the model stored in etc/doctrine/model.json into real php entities into the directory: lib/'."\n".
        'So if namespace in model is ACME\Blog\Entities this will write the entities to lib/ACME/Blog/Entities/SomeEntity.php'
    );

    $this->addArgument(
      'model', InputArgument::REQUIRED,
      'Path to the json model'
    );

    $this->addArgument(
      'psr0target', InputArgument::REQUIRED,
      'Path where to write the entities to (the root of the psr0-hierarchy, namespaces will be created).'
    );

    parent::configure();
  }

  public function doExecute(CommandInput $input, CommandOutput $output, CommandInteraction $interact, System $system) {
    $model = $input->getFile('model');

    $jsonModel = JSONConverter::create()->parseFile($model);

    $target = $input->getDirectory('psr0target');

    $this->getCompiler()->compileModel($jsonModel, $target, $flags = Compiler::COMPILED_ENTITIES | Compiler::RECOMPILE);

    $output->ok('The model was successful compiled.');
    return 0;
  }

  protected function getCompiler() {
    if (!isset($this->compiler)) {
      $webforge = $this->getWebforge();

      $this->compiler = new Compiler(
        $webforge->getClassWriter(), 
        new EntityGenerator(new Inflector, new EntityMappingGenerator(new AnnotationsWriter)),
        new ModelValidator,
        $webforge->getClassElevator()
      );
    }

    return $this->compiler;
  }

  protected function getWebforge() {
    return $GLOBALS['env']['container']->getWebforge();
  }

  public function injectWebforge($webforge) {
    $this->webforge = $webforge;
  }
}
