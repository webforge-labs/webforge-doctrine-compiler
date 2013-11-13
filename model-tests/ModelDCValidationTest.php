<?php

namespace Webforge\Doctrine\Compiler;

use Doctrine\ORM\Tools\SchemaValidator;
use Webforge\Doctrine\Util;

class ModelDCValidationTest extends \Webforge\Doctrine\Compiler\Test\ModelBase {

  public function setUp() {
    $this->chainClass = __NAMESPACE__ . '\\Compiler';
    parent::setUp();

    $this->validator = new SchemaValidator($this->em);
  }

  public function testSchemaIsValid() {
    $errors = $this->validator->validateMapping();
    $this->assertCount(0, $errors, 'There were errors with the generated schema: '.print_r($errors, true));
  }

  public function testCreatingTheSchema() {
    // this will work on travis out of the box but locally we have to delete all tables
    $connection = $this->em->getConnection();
    $sm = $connection->getSchemaManager();

    $connection->query('set foreign_key_checks=0');
    foreach ($sm->listTableNames() as $tableName) {
      $sm->dropTable($tableName);
    }
    $connection->query('set foreign_key_checks=1');

    $sql = $this->dcc->getUtil()->updateSchema('tests', Util::FORCE, $eol = "\n");

    foreach (TestReflection::tableNames() as $params) {
      list($table) = $params;

      $this->assertContains('CREATE TABLE '.$table, $sql);
    }
  }
}
