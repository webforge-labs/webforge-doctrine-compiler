<?php

namespace Webforge\Code;

use Webforge\Code\Generator\ClassFileMapper;
use Webforge\Framework\Package\Package;
use Webforge\Framework\Package\PackageNotFoundException;
use Webforge\Framework\Package\SimplePackage;
use Webforge\Framework\Package\Registry;
use Webforge\Setup\AutoLoadInfo;
use Webforge\Common\System\File;

/**
 * tests the getFile() function from the GlobalClassFileMapper
 */
class GlobalClassFileMapperTest extends \Webforge\Code\Test\Base
{
    protected $mapper;

    public function setUp()
    {
        $this->registry = $this->getMock('Webforge\Framework\Package\Registry', array('findByFQN'));
        $this->mapper = new GlobalClassFileMapper();
        $this->mapper->setPackageRegistry($this->registry);
    }

    public function testGetClassIsNotImplemented()
    {
        $this->setExpectedException('Webforge\Common\Exception\NotImplementedException');
        $this->mapper->getClass(new File(__FILE__));
    }

    public function testThatNonsenseFqnsCantGetFound()
    {
        $this->expectRegistryFindsNothing();
        $this->setExpectedException('Webforge\Code\ClassFileNotFoundException');

        $this->mapper->getFile('ths\class\has\a\nonsense\name\and\is\not\existent');
    }

    public function testGlobalClassFileMapperDoesNotNeedNeessearlyARegistry()
    {
        $this->setExpectedException('Webforge\Code\ClassFileNotFoundException');

        $mapper = new GlobalClassFileMapper();
        $mapper->getFile('ths\class\as\anonsense\name');
    }

    public function testFindWithPackageWithoutAutoloadIsNotPossible()
    {
        $woAutoLoad = new SimplePackage('without-autoload', 'webforge', $this->getPackageRoot('WithoutAutoLoad'));

        $this->setExpectedException('Webforge\Code\ClassNotFoundException');

        try {
            $this->mapper->findWithPackage('DoesNotMatter\Classname', $woAutoLoad);
        } catch (ClassNotFoundException $e) {
            $this->assertContains('AutoLoading from package: ' . $woAutoLoad . ' is not defined', $e->getMessage());
            throw $e;
        }
    }

    public function testFindWithPackageWithoutFilesIsNotPossible()
    {
        $registry = new Registry();
        $emptyAutoLoad = $registry->addComposerPackageFromDirectory($this->getPackageRoot('EmptyAutoLoad'));

        $this->setExpectedException('Webforge\Code\ClassNotFoundException');

        try {
            $this->mapper->findWithPackage('EmptyAutoLoad\Something', $emptyAutoLoad);
        } catch (ClassNotFoundException $e) {
            $this->assertContains('0 files found', $e->getMessage());
            throw $e;
        }
    }

    public function testFindWithPackageWithTooManyFilesIsNotPossible()
    {
        $conflictPackage = new SimplePackage('without-autoload', 'webforge', $this->getPackageRoot('WithoutAutoLoad'), $autoload = $this->getMock('Webforge\Setup\AutoLoadInfo'));
        $autoload->expects($this->once())->method('getFilesInfos')->will(
            $this->returnValue(array(
            (object) array('file' => new File(__FILE__), 'prefix' => 'Something'),
            (object) array('file' => new File(__FILE__), 'prefix' => 'Something\Tests'),
            (object) array('file' => new File(__FILE__), 'prefix' => 'Something\Found'),
            ))
        );

        $this->setExpectedException('Webforge\Code\ClassNotFoundException');
        try {
            $this->mapper->findWithPackage('Something', $conflictPackage);
        } catch (ClassNotFoundException $e) {
            $this->assertContains('Too many Files were found', $e->getMessage());
            throw $e;
        }
    }

    public function testFindWithMultipeFilesIsResolvedByLongestFQN()
    {
        $conflictPackage = new SimplePackage('without-autoload', 'webforge', $this->getPackageRoot('WithoutAutoLoad'), $autoload = $this->getMock('Webforge\Setup\AutoLoadInfo'));

        $phpFile1 = new File(__FILE__ . '1.php');
        $phpFile2 = new File(__FILE__ . '2.php');

      // both prefixes have matched here, but the FQN is cleary for the second
        $autoload->expects($this->once())->method('getFilesInfos')->will(
            $this->returnValue(array(
            (object) array('file' => $phpFile1, 'prefix' => 'Webforge'),
            (object) array('file' => $phpFile2, 'prefix' => 'Webforge\Doctrine\Compiler'),
            ))
        );

        $foundFile = $this->mapper->findWithPackage('Webforge\Doctrine\Compiler\Inflector', $conflictPackage);
        $this->assertEquals((string) $phpFile2, (string) $foundFile, 'should resolve to only the second file');
    }

    public function testEmptyFQNsAreBad()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->mapper->getFile('');
    }

    public function testSearchingWithRegistryForACMEnormalClass()
    {
        $this->expectRegistryFindsPackageForFQN(
            $this->createPackage('acme/intranet-application', 'ACME'),
            'ACME\IntranetApplication\Main'
        );

        $actualFile = $this->mapper->getFile('ACME\IntranetApplication\Main');
        $expectedFile = $this->getFixtureFile('ACME', array('lib', 'ACME', 'IntranetApplication'), 'Main.php');

        $this->assertEquals((string) $expectedFile, (string) $actualFile);
    }

    public function testAmbiguousAutoloadInfoGetsResolvedToNormalClass()
    {
        $this->expectRegistryFindsPackageForFQN(
            $this->createPackage(
                'webforge/webforge',
                'Webforge',
                array(
                'psr-0' => (object) array(
                'Webforge' => array('lib/', 'tests/')
                )
                )
            ),
            'Webforge\Common\String'
        );

        $actualFile = $this->mapper->getFile('Webforge\Common\String');
        $expectedFile = $this->getFixtureFile('Webforge', array('lib', 'Webforge', 'Common'), 'String.php');

        $this->assertEquals((string) $expectedFile, (string) $actualFile);
    }

    protected function expectRegistryFindsPackageForFQN(Package $package, $fqn, $times = null)
    {
        $this->registry->expects($times ?: $this->once())->method('findByFQN')
                   ->with($this->equalTo($fqn))->will($this->returnValue($package));
    }

    protected function expectRegistryFindsNothing($times = null)
    {
        $this->registry->expects($times ?: $this->once())->method('findByFQN')
                   ->will(
                       $this->throwException(
                           PackageNotFoundException::fromSearch(array('fqn' => 'searched for unkown fqn (not set in test)'), array('somePrefix','someOtherPrefix'))
                       )
                   );
    }

    protected function createPackage($slug, $dirName, array $autoLoadInfoSpec = null)
    {
        list($vendor, $slug) = explode('/', $slug, 2);
        $package = new SimplePackage(
            $slug,
            $vendor,
            $this->getPackageRoot($dirName),
            new AutoLoadInfo(
                $autoLoadInfoSpec ?:
                                     array(
                                     'psr-0' => (object) array(
                                       'ACME' => array('lib/')
                                      )
                                     )
            )
        );
        $package->defineDirectory(Package::TESTS, 'tests/');
        return $package;
    }

    protected function getFixtureFile($package, $path, $fileName)
    {
        return $this->getTestDirectory('packages/' . $package . '/' . implode('/', $path) . '/')->getFile($fileName);
    }

    protected function getPackageRoot($dirName)
    {
        return $this->getTestDirectory('packages/' . $dirName . '/');
    }
}
