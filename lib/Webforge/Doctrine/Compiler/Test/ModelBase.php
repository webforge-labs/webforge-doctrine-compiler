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

  protected function assertCascadePersist(Array $association) {
    $this->assertTrue($association['isCascadePersist'], 'cascade persist for '.$association['fieldName']);
  }

  protected function assertCascadeRemove(Array $association) {
    $this->assertTrue($association['isCascadeRemove'], 'cascade remove for '.$association['fieldName']);
  }

  protected function assertJoinColumnNotNullable(Array $association) {
    $this->assertThatObject($association)
      ->key('joinColumns')->isArray()->length(1)
        ->key(0)
          ->key('nullable', false);
  }

  protected function assertIsUnidirectional(Array $association) {
    $this->assertNull($association['mappedBy'], 'association is not unidirectional');
    $this->assertNull($association['inversedBy'], 'association is not unidirectional');
  }

  protected function assertIsInversedBy($mappedBy, Array $association) {
    $this->assertEquals($mappedBy, $association['inversedBy'], 'is inversedBy does not match');
  }

  protected function assertSerializerPropertyType($expectedType, $propertyName, \JMS\Serializer\Metadata\ClassMetadata $metadata) {
    return $this->assertSerializerType($expectedType, $metadata->propertyMetadata[$propertyName]);
  }

  protected function assertThatSerializerProperty($propertyName, $metadataOrObject) {
    if (!($metadataOrObject instanceof \JMS\Serializer\Metadata\ClassMetadata)) {
      $factory = $this->serializer->getMetadataFactory();
      $metadata = $factory->getMetadataForClass(get_class($metadataOrObject));
    } else {
      $metadata = $metadataOrObject;
    }

    return $this->assertThatObject($metadata->propertyMetadata)->isArray()
      ->key($propertyName)->isNotEmpty();
  }

  protected function assertSerializerType($expectedType, \JMS\Serializer\Metadata\PropertyMetadata $property) {
    $type = $property->type;
    $this->assertEquals($expectedType, $type['name'], 'serializer type from '.$property->name.' does not match');
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
