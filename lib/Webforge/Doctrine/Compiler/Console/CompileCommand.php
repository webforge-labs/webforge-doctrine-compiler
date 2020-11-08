<?php

namespace Webforge\Doctrine\Compiler\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Webforge\Code\Generator\ComposerClassFileMapper;
use Webforge\Common\JS\JSONConverter;
use Webforge\Common\System\Dir;
use Webforge\Common\System\File;
use Webforge\Common\System\System;
use Webforge\Console\Command\CommandAdapter;
use Webforge\Console\CommandInput;
use Webforge\Console\CommandInteraction;
use Webforge\Console\CommandOutput;
use Webforge\Doctrine\Annotations\Writer as AnnotationsWriter;
use Webforge\Doctrine\Compiler\Compiler;
use Webforge\Doctrine\Compiler\EntityGenerator;
use Webforge\Doctrine\Compiler\EntityMappingGenerator;
use Webforge\Doctrine\Compiler\GClassBroker;
use Webforge\Doctrine\Compiler\Inflector;
use Webforge\Doctrine\Compiler\ModelValidator;

class CompileCommand extends CommandAdapter

{
    protected $name = 'orm:compile';

    protected $compiler;
    protected $webforge;

    protected function configure()
    {
        $this
            ->setName($this->name)
            ->setDescription(
                'Compiles the entities in the model into real php entities.'
            )
            ->setHelp(
                $this->getName() . " etc/doctrine/model.json lib\n" .
                'Compiles the entities in the model stored in etc/doctrine/model.json into real php entities into the directory: lib/' . "\n" .
                'So if namespace in model is ACME\Blog\Entities this will write the entities like lib/ACME/Blog/Entities/SomeEntity.php'
            );

        $this->addArgument(
            'model',
            InputArgument::REQUIRED,
            'Path to the json model'
        );

        $this->addArgument(
            'psr0target',
            InputArgument::REQUIRED,
            'Path where to write the entities to (the root of the psr0-hierarchy, namespaces will be created).'
        );

        $this->addArgument(
            'composerdirectory',
            InputArgument::REQUIRED,
            'Path where composer writes its autoload info to (vendor/composer in your project).'
        );

        $this->addOption(
            'extension',
            null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Name of extensions to load. (upcase word like: Serializer)'
        );

        parent::configure();
    }

    public function doExecute(CommandInput $input, CommandOutput $output, CommandInteraction $interact, System $system)
    {
        $model = $input->getFile('model');

        $jsonc = JSONConverter::create();

        $jsonModel = $jsonc->parseFile($model);

        $target = $input->getDirectory('psr0target');

        $composerDirectory = $input->getDirectory('composerdirectory');
        $compiler = $this->getCompiler($target, (array)$input->getValue('extension'), $composerDirectory);
        $jsonModel = $compiler->compileModel(
            $jsonModel,
            $target,
            $flags = Compiler::COMPILED_ENTITIES | Compiler::RECOMPILE | Compiler::EXPORT_MODEL
        );

        $compiledModel = clone $model;
        $compiledModel->setName($model->getName(File::WITHOUT_EXTENSION) . '-compiled.json');
        $compiledModel->writeContents($jsonc->stringify($jsonModel, JSONConverter::PRETTY_PRINT));

        $output->ok('The model was successfully compiled.');
        return 0;
    }

    protected function getCompiler(Dir $target, array $extensions, $composerDirectory)
    {
        if (!isset($this->compiler)) {
            $webforge = $this->getWebforge();
            $container = $GLOBALS['env']['container'];
            $loader = $container->getAutoLoader();

            if ($composerDirectory->exists()) {
                $map = require (string)$composerDirectory->getFile('autoload_namespaces.php');
                foreach ($map as $namespace => $path) {
                    $loader->add($namespace, $path);
                }

                $map = require (string)$composerDirectory->getFile('autoload_psr4.php');
                foreach ($map as $namespace => $path) {
                    $loader->addPsr4($namespace, $path);
                }

                $classMap = require $composerDirectory->getFile('autoload_classmap.php');
                if ($classMap) {
                    $loader->addClassMap($classMap);
                }
            }

            $webforge->setClassFileMapper(
                new ComposerClassFileMapper($loader)
            );

            $annotationsWriter = new AnnotationsWriter();
            $extensions = array_map(
                function ($extensionName) use ($annotationsWriter) {
                    $qn = 'Webforge\\Doctrine\\Compiler\\Extensions\\' . ucfirst($extensionName);

                    return new $qn($annotationsWriter);
                },
                $extensions
            );

            $this->compiler = new Compiler(
                $webforge->getClassWriter(),
                new EntityGenerator(
                    new Inflector(),
                    new EntityMappingGenerator(
                        $annotationsWriter,
                        $extensions
                    ),
                    new GClassBroker($webforge->getClassElevator())
                ),
                new ModelValidator()
            );
        }

        return $this->compiler;
    }

    protected function getWebforge()
    {
        return $GLOBALS['env']['container']->getWebforge();
    }

    public function injectWebforge($webforge)
    {
        $this->webforge = $webforge;
    }
}
