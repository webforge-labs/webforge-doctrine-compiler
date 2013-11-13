<?php

namespace Webforge\Doctrine\Compiler\Console;

use Webforge\Doctrine\Console\AbstractDoctrineCommand;

class CompileCommand extends \Webforge\Console\Command\CommandAdapter {

  protected function configure() {
    $this
      ->setName('orm:update-schema')
      ->setDescription(
        'Updates the database schema to match the current mapping metadata.'
      )
      ->setHelp(
        $this->getName()." --dry-run\n".
        "Shows the changes that would be made.\n".
        "\n".
        $this->getName()."\n".
        'Updates the database schema to match the current mapping metadata.'
    );

    parent::configure();
  }

}
