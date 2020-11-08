<?php

namespace Webforge\Doctrine\Compiler;

use Webforge\Common\JS\JSONConverter;
use Webforge\Doctrine\Annotations\Writer as AnnotationsWriter;
use Webforge\Common\System\Dir;
use Mockery as m;

class ModelExporterTest extends \Webforge\Doctrine\Compiler\Test\Base
{
    public function setUp()
    {
        $this->chainClass = __NAMESPACE__ . '\\ModelExporter';
        parent::setUp();

        $this->validator = new ModelValidator();
        $this->exporter = new ModelExporter();

        $this->originalModelJson = JSONConverter::create()->parseFile(
            $this->getTestDirectory('acme-blog/etc/doctrine')->getFile('model.json')
        );

        $this->originalModel = $this->validator->validateModel($this->originalModelJson);

        $this->mapper->shouldReceive('getFile')
            ->with('Webforge\Doctrine\Compiler\Test\BaseUserEntity')
            ->andReturn(
                $this->frameworkHelper->getProject()->dir('lib')->getFile('Webforge/Doctrine/Compiler/Test/BaseUserEntity.php')
            );

        $this->entityGenerator->generate($this->originalModel);

        $this->model = $this->exporter->exportModel($this->originalModel, $this->entityGenerator);
    }

    public function testEntitiesArrayDoesExist()
    {
        $this->assertThatObject($this->model)->property('entities')->isArray();

        return $this->model->entities;
    }

    public function testNamespaceIsWritten()
    {
        $this->assertThatObject($this->model)->property('namespace')->is('ACME\Blog\Entities');
    }

    /**
     * @depends testEntitiesArrayDoesExist
     */
    public function testEveryEntityHasAnFQNProperty(array $entities)
    {
        $fqns = array();
        foreach ($entities as $entity) {
            $this->assertThatObject($entity)->property('fqn');
            $fqns[] = $entity->fqn;
        }

        $this->assertArrayEquals(TestReflection::flatEntityFQNs(), $fqns);
    }

    /**
     * @depends testEntitiesArrayDoesExist
     */
    public function testEveryEntityHasTheSingularAndPluralName(array $entities)
    {
        $names = array();
        foreach ($entities as $entity) {
            $this->assertThatObject($entity)
                ->property('singular')->is($this->logicalNot($this->equalTo('')))->end()
                ->property('plural')->is($this->logicalNot($this->equalTo('')))->end();
            $names[] = array($entity->fqn, $entity->plural, $entity->singular);
        }

        $this->assertArrayEquals(TestReflection::entitySlugs(), $names);
    }

    public function testEntityExtendsIsExportetCorrectly()
    {
        $this->assertThatObject($this->model)->property('entities')->isArray()
            ->key(2)
            ->property('fqn')->is('ACME\Blog\Entities\Author')->end()
            ->property('extends')->is('ACME\Blog\Entities\User')->end()
            ->end()
            ->key(1)
            ->property('fqn')->is('ACME\Blog\Entities\Post')->end()
            ->property('extends')->is($this->equalTo(null))->end()
            ->end();
    }

    public function testEntityPropertiesAreExportet()
    {
        $this->assertThatObject($this->model)->property('entities')->isArray()
            ->key(0)
            ->property('fqn')->is('ACME\Blog\Entities\User')->end()
            ->property('properties')->isObject()
            ->property('id')->end()
            ->property('email')->property('type')->is('String')->end()->end()
            ->end()
            ->end()
            ->key(1)
            ->property('fqn')->is('ACME\Blog\Entities\Post')->end()
            ->property('properties')->isObject()
            ->property('id')->end()
            ->property('tags')
            ->property('type', 'ACME\Blog\Entities\Tag')->end()
            ->property('isEntityReference', true)->end()
            ->property('isCollection', true)->end()
            ->end()
            ->property('author')->isObject()
            ->property('type', 'ACME\Blog\Entities\Author')->end()
            ->property('isEntityReference', true)->end()
            ->property('isCollection', false)->end()
            ->end()
            ->property('active')
            ->property('type')->is('Boolean')->end()
            ->property('isEntityReference', false)->end()
            ->end()
            ->end()
            ->end()
            ->key(2)
            ->property('fqn')->is('ACME\Blog\Entities\Author')->end()
            ->property('properties')->isObject()
            ->property('writtenPosts')->end()
            ->property('revisionedPosts')->end()
            ->end()
            ->end();
    }
}
