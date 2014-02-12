<?php

namespace Webforge\Doctrine\Compiler\Test;

class ModelBase extends Base {

  protected function setUpPackage() {
    $this->blogPackage = self::$package;
    $this->classElevator = $this->frameworkHelper->getWebforge()->getClassElevator();
  }

  protected function initEntitiesPaths() {
    $this->entitiesPaths = array($this->getVirtualDirectory()->sub('lib/ACME/Blog/Entities'));
    return $this->entitiesPaths;
  }

  protected function getVirtualDirectory($name = NULL) {
    return $this->getPackageDir('build/package/');
  }

  protected function assertAssociationMapping($name, \Doctrine\ORM\Mapping\ClassMetadata $metadata) {
    $associations = $metadata->getAssociationMappings();
    $this->assertNotEmpty($associations, 'There should be associations defined for entity '.$metadata->name);

    $this->assertArrayHasKey($name, $associations, 'association metadata for '.$name.' is not defined in '.$metadata->name);
    return $associations[$name];
  }

  protected function assertTableName($expectedTableName, $entityShortName) {
    $metadata = $this->assertDoctrineMetadata($fqn = 'ACME\Blog\Entities\\'.$entityShortName);

    $this->assertEquals($expectedTableName, $metadata->table['name'], 'failed asserting that tablename for '.$fqn.' does match');
  }

  protected function assertIsMappedBy($mappedBy, Array $association) {
    $this->assertEquals($mappedBy, $association['mappedBy'], 'is mappedBy does not match');
  }

  protected function assertIsInversedBy($mappedBy, Array $association) {
    $this->assertEquals($mappedBy, $association['inversedBy'], 'is inversedBy does not match');
  }

  protected function assertHasTargetENtity($fqn, Array $association) {
    if ($fqn instanceof ClassInterface) $fqn = $fqn->getFQN();

    $this->assertEquals($fqn, $association['targetEntity'], 'the target entity does not match');
  }

  protected function assertMetadataField($entityShortname, $fieldName) {
    $metadata = $this->assertDoctrineMetadata('ACME\Blog\Entities\\'.$entityShortname);

    $field = $metadata->getFieldMapping($fieldName);

    return $field;
  }
}
