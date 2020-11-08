<?php

namespace Webforge\Code;

use InvalidArgumentException;
use Webforge\Code\Generator\ClassFileMapper;
use Webforge\Code\Generator\GClass;
use Webforge\Common\ArrayUtil as A;
use Webforge\Common\Exception\NotImplementedException;
use Webforge\Common\StringUtil as S;
use Webforge\Common\System\File;
use Webforge\Framework\Package\Package;
use Webforge\Framework\Package\PackageNotFoundException;
use Webforge\Framework\Package\Registry as PackageRegistry;

/**
 * The Global Class File Mapper finds the corrosponding file in a project on the local machine
 *
 * the main usage of this classFileMapper is to find autoLoading paths on a developer machine and map those paths to class files
 *
 * given: a full qualified classname
 * returns: a file where to store the class for the fqn
 *
 * it is usefull to have such a classmapper on a developer-machine because it enables
 * you to create classes in every project you work on, which does not have to be a webforge-package
 *
 * a nice approach to this complexity would be to use an array of agents, which try to find the file
 * and then evaluate the array of agents.
 *
 */
class GlobalClassFileMapper implements ClassFileMapper
{
   public const WITH_RESOLVING = 0x000001;

    /**
     * A Registry for Packages installed on the host (e.g.)
     *
     * @var Webforge\Framework\Package\Registry
     */
    protected $packageRegistry;

    /**
     * @return GClass
     */
    public function getClass(File $file)
    {
        throw NotImplementedException::fromString('getting the class from a file');
    }

    /**
     * @return Webforge\Common\System\File
     */
    public function getFile($fqn)
    {
        $fqn = $this->normalizeClassFQN($fqn);

        if (($file = $this->findWithRegistry($fqn)) != null) {
            return $file;
        }

        throw ClassFileNotFoundException::fromFQN($fqn);
    }

    /**
     * @return Webforge\Common\System\File|NULL
     */
    public function findWithRegistry($fqn)
    {
        if (isset($this->packageRegistry)) {
            try {
                $package = $this->packageRegistry->findByFQN($fqn);

                return $this->findWithPackage($fqn, $package);
            } catch (PackageNotFoundException $e) {
                $e = ClassFileNotFoundException::fromPackageNotFoundException($fqn, $e);
                throw $e;
            }
        }

        return null;
    }

    public function findWithPackage($fqn, Package $package)
    {
        $autoLoad = $package->getAutoLoadInfo();

        if (!isset($autoLoad)) {
            $e = ClassNotFoundException::fromFQN($fqn);
            $e->appendMessage(
                sprintf('. AutoLoading from package: %s is not defined. Cannot resolve to file.', $package)
            );
            throw $e;
        }

        $filesInfos = $autoLoad->getFilesInfos($fqn, $package->getRootDirectory());

        if (count($filesInfos) === 0) {
            $e = ClassNotFoundException::fromFQN($fqn);
            $e->appendMessage(sprintf(". AutoLoading from package: %s failed. 0 files found.", $package));
            throw $e;
        }

        $file = $this->resolveConflictingFiles($filesInfos, $fqn, $package);

        return $this->validateFile($file, self::WITH_RESOLVING);
    }

    protected function resolveConflictingFiles(array $filesInfos, $fqn, Package $package)
    {
        if (count($filesInfos) > 1) {
            $testsDir = $package->getDirectory(Package::TESTS);
            $testFilesInfos = array_filter(
                $filesInfos,
                function ($fileInfo) use ($testsDir) {
                    return $fileInfo->file->getDirectory()->isSubdirectoryOf($testsDir);
                }
            );

            if (S::endsWith($fqn, 'Test') && count($testFilesInfos) === 1) {
                return current($testFilesInfos)->file;
            }

            $filesInfos = array_udiff(
                $filesInfos,
                $testFilesInfos,
                function ($a, $b) {
                    $fileA = (string)$a->file;
                    $fileB = (string)$b->file;

                    return strcmp($fileA, $fileB);
                }
            );

            if (count($filesInfos) > 1) {
                // we know that $fqn starts with $fileInfo->prefix for every $fileInfo in $filesInfos
                // we just find the longest prefix and resolve for that

                $byPrefix = array();
                foreach ($filesInfos as $fileInfo) {
                    $fileInfo->length = mb_strlen($fileInfo->prefix);
                    $byPrefix[$fileInfo->prefix] = $fileInfo;
                }

                usort(
                    $byPrefix,
                    function ($a, $b) {
                        if ($a->length === $b->length) {
                            return 0;
                        }

                        return $a->length > $b->length ? -1 : 1; // inverse
                    }
                );

                $longest = $byPrefix[0]->length;
                $filesInfos = array_filter(
                    $byPrefix,
                    function ($fileInfo) use ($longest) {
                        return $fileInfo->length == $longest;
                    }
                );

                // not further reduction possible
                if (count($filesInfos) > 1) {
                    $e = ClassNotFoundException::fromFQN($fqn);
                    $e->appendMessage(
                        sprintf(
                            ". AutoLoading from package: %s failed. Too many Files were found:\n%s",
                            $package,
                            implode("\n", A::pluck($filesInfos, 'file'))
                        )
                    );
                    throw $e;
                }
            }
        }

        return current($filesInfos)->file;
    }

    protected function validateFile(File $file, $flags = 0x0000)
    {
        if ($flags & self::WITH_RESOLVING) {
            $file->resolvePath();
        }

        return $file;
    }

    protected function normalizeClassFQN($fqn)
    {
        $fqn = ltrim($fqn, '\\');

        if (mb_strlen($fqn) === 0) {
            throw new InvalidArgumentException('fqn cannot be empty');
        }

        return $fqn;
    }

    /**
     * @param Webforge\Framework\Package\Registry $packageRegistry
     * @chainable
     */
    public function setPackageRegistry(PackageRegistry $packageRegistry)
    {
        $this->packageRegistry = $packageRegistry;
        return $this;
    }

    /**
     * @return Webforge\Framework\Package\Registry
     */
    public function getPackageRegistry()
    {
        return $this->packageRegistry;
    }
}
