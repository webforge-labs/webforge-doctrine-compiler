<?php

namespace Webforge\Code\Generator;

use RuntimeException;
use Webforge\Common\ArrayUtil as A;
use Webforge\Common\ClassInterface;
use Webforge\Common\CodeWriter;
use Webforge\Common\StringUtil as S;
use Webforge\Common\System\File;

/**
 * Writes a Class in Code (PHP)
 *
 * The ClassWriter writes the Stream in the gClass to a given File
 *
 * This writer should change somehow, so that it does not use the GClass inner
 * Functions to generate the PHP from psc-cms and that its writing is own code
 */
class ClassWriter
{
   public const OVERWRITE = true;

    /**
     * @var \Webforge\Code\Generator\Imports
     */
    protected $imports;

    /**
     * @var \Webforge\Common\CodeWriter
     */
    protected $codeWriter;

    /**
     * @var string
     */
    protected $namespaceContext;

    /**
     * @var \Webforge\Code\Generator\Imports
     */
    protected $classImports;

    public function __construct()
    {
        $this->imports = new Imports();
    }

    public function write(GClass $gClass, File $file, $overwrite = false)
    {
        if ($file->exists() && $overwrite !== self::OVERWRITE) {
            throw new ClassWritingException(
                sprintf('The file %s already exists. To overwrite set the overwrite parameter.', $file),
                ClassWritingException::OVERWRITE_NOT_SET
            );
        }

        $file->writeContents($this->writeGClassFile($gClass));
        return $this;
    }

    public function writeGClassFile(GClass $gClass, $eol = "\n")
    {
        $php = '<?php' . $eol;
        $php .= $eol;

        if (($namespace = $gClass->getNamespace()) != null) {
            $php .= 'namespace ' . $namespace . ';' . $eol;
            $php .= $eol;
        }

        $this->classImports = clone $this->imports;
        $this->classImports->mergeFromClass($gClass);

        if ($use = $this->classImports->php($namespace)) {
            $php .= $use;
            $php .= $eol;
        }

        $php .= $this->writeGClass($gClass, $namespace, $eol);
        $php .= $eol; // PSR-x

        return $php;
    }

    /**
     * returns the Class as PHP Code (without imports (use), without namespace decl)
     *
     * indentation is fixed: 2 whitespaces
     * @return string the code with docblock from class { to }
     */
    public function writeGClass(GClass $gClass, $namespace, $eol = "\n")
    {
        $that = $this;
        $this->namespaceContext = $namespace;

        $php = '';

        /* DocBlock */
        if ($gClass->hasDocBlock()) {
            $php .= $this->writeDocBlock($gClass->getDocBlock(), 0);
        }

        /* Modifiers */
        $php .= $this->writeModifiers($gClass->getModifiers());

        /* Class */
        if ($gClass->isInterface()) {
            $php .= 'interface ' . $gClass->getName();
        } else {
            $php .= 'class ' . $gClass->getName();
        }

        /* Extends */
        if (($parent = $gClass->getParent()) != null) {
            // its important to use the contextNamespace here, because $namespace can be !== $gClass->getNamespace()
            if ($parent->getNamespace() === $namespace) {
                $php .= ' extends ' . $parent->getName(); // don't prefix with namespace
            } else {
                // should it add to use, or use \FQN in extends?
                $php .= ' extends ' . '\\' . $parent->getFQN();
            }
        }

        /* Interfaces */
        if (count($gClass->getInterfaces()) > 0) {
            $php .= ' implements ';
            $php .= A::implode(
                $gClass->getInterfaces(),
                ', ',
                function (GClass $iClass) use ($namespace) {
                    if ($iClass->getNamespace() === $namespace) {
                        return $iClass->getName();
                    } else {
                        return '\\' . $iClass->getFQN();
                    }
                }
            );
        }

        $php .= $eol . '{';


        $body = '';
        $glue = $eol . $eol . '%s';

        /* Constants */
        $body .= A::joinc(
            $gClass->getConstants(),
            $glue . ';',
            function ($constant) use ($that, $eol) {
                return $that->writeConstant($constant, 4, $eol);
            }
        );

        /* Properties */
        $body .= A::joinc(
            $gClass->getProperties(),
            $glue . ';',
            function ($property) use ($that, $eol) {
                return $that->writeProperty($property, 4, $eol);
            }
        );

        /* Methods */
        $body .= A::joinc(
            $gClass->getMethods(),
            $glue,
            function ($method) use ($that, $eol) {
                return $that->writeMethod($method, 4, $eol);
            }
        );

        $body = mb_substr($body, 1); // cut of first empty line after {

        $php .= $body;
        $php .= $eol . '}';

        return $php;
    }

    /**
     * returns the PHP Code for a GMethod
     *
     * after } is no LF
     * @return string
     */
    public function writeMethod(GMethod $method, $baseIndent = 0, $eol = "\n")
    {
        $php = '';

        if ($method->hasDocBlock()) {
            $php = $this->writeDocBlock($method->getDocBlock(), $baseIndent, $eol);
        }

        $php .= str_repeat(' ', $baseIndent);

        $php .= $this->writeModifiers($method->getModifiers());
        $php .= $this->writeGFunction($method, $baseIndent, $eol);

        return $php;
    }

    /**
     * returns PHPCode for a GFunction/GMethod
     *
     */
    public function writeGFunction(GMethod $function, $baseIndent = 0, $eol = "\n")
    {
        $php = '';

        $php .= $this->writeFunctionSignature($function, $baseIndent, $eol);

        if ($function->isAbstract() || $function->isInInterface()) {
            $php .= ';';
        } else {
            $php .= $this->writeFunctionBody($function->getBody(), $baseIndent, $eol);
        }

        return $php;
    }

    /**
     * Writes a function body
     *
     * the function body is from { to }
     * @return string
     */
    public function writeFunctionBody(GFunctionBody $body = null, $baseIndent = 0, $eol = "\n")
    {
        $php = $eol;
        $php .= S::indent('{', $baseIndent, $eol) . $eol;
        if ($body !== null && !$body->isEmpty()) {
            $php .= $body->php($baseIndent + 4, $eol) . $eol;
        }
        $php .= S::indent('}', $baseIndent, $eol);

        return $php;
    }

    protected function writeFunctionSignature(GMethod $function, $baseIndent = 0, $eol = "\n")
    {
        $php = 'function ' . $function->getName() . $this->writeParameters(
            $function->getParameters(),
            $this->namespaceContext,
            $baseIndent,
            $eol
        );

        if ($hint = $function->getReturnTypeHint()) {
            $php .= ': '.$hint;
        }
        return $php;
    }

    public function writeParameters(array $parameters, $namespace)
    {
        $that = $this;

        $php = '(';
        $php .= A::implode(
            $parameters,
            ', ',
            function ($parameter) use ($that, $namespace) {
                return $that->writeParameter($parameter, $namespace);
            }
        );
        $php .= ')';

        return $php;
    }

    public function writeParameter(GParameter $parameter, $namespace)
    {
        $php = '';

        $php .= $this->writeParameterTypeHint($parameter, $namespace);

        // name
        $php .= ($parameter->isReference() ? '&' : '') . '$' . $parameter->getName();

        // optional (default)
        if ($parameter->hasDefault()) {
            $php .= ' = ';

            if ($parameter->hasLiteralDefaultValue()) {
                $php .= (string)$parameter->getDefault();
            } else {
                $default = $parameter->getDefault();
                if (is_array($default) && count($default) == 0) {
                    $php .= 'array()';
                } else {
                    $php .= $this->writeArgumentValue($default); // das sieht scheise aus
                }
            }
        }

        return $php;
    }

    /**
     * @return string with whitespace at the end if hint is set
     */
    protected function writeParameterTypeHint(GParameter $parameter, $namespace)
    {
        if ($parameter->hasHint()) {
            if (($import = $parameter->getHintImport()) instanceof ClassInterface) {
                if (isset($this->classImports) && $this->classImports->have($import)) {
                    $useFQN = false;
                } elseif ($this->imports->have($import)) {
                    $useFQN = false;
                } elseif ($import->getNamespace() === $namespace) {
                    $useFQN = false;
                } else {
                    $useFQN = true;
                }

                return $parameter->getHint($useFQN) . ' ';
            } else {
                return $parameter->getHint() . ' ';
            }
        }

        return '';
    }

    protected function writeArgumentValue($value)
    {
        if (is_array($value) && A::getType($value) === 'numeric') {
            return $this->getCodeWriter()->exportList($value);
        } elseif (is_array($value)) {
            return $this->getCodeWriter()->exportKeyList($value);
        } else {
            try {
                return $this->getCodeWriter()->exportBaseTypeValue($value);
            } catch (RuntimeException $e) {
                throw new RuntimeException(
                    'In Argumenten oder Properties können nur Skalare DefaultValues stehen. Die value muss im Constructor stehen.',
                    0,
                    $e
                );
            }
        }
    }

    /**
     * @return string
     */
    public function writeProperty(GProperty $property, $baseIndent, $eol = "\n")
    {
        $php = '';

        if ($property->hasDocBlock()) {
            $php = $this->writeDocBlock($property->getDocBlock(), $baseIndent, $eol);
        }

        $php .= str_repeat(' ', $baseIndent);
        $php .= $this->writeModifiers($property->getModifiers());

        $php .= '$' . $property->getName();

        if ($property->hasDefaultValue()) {
            if ($property->hasLiteralDefaultValue()) {
                $php .= ' = ' . ((string)$property->getDefaultValue());
            } else {
                if ($property->getDefaultValue() !== null) {
                    $php .= ' = ' . $this->writePropertyValue($property->getDefaultValue());
                }
            }
        }

        return $php;
    }

    protected function writePropertyValue($value)
    {
        return $this->writeArgumentValue($value);
    }


    /**
     * @return string
     */
    public function writeDocBlock(DocBlock $docBlock, $baseIndent = 0, $eol = "\n")
    {
        return S::indent($docBlock->toString(), $baseIndent);
    }

    /**
     * @return string with whitespace after the last modifier
     */
    public function writeModifiers($bitmap)
    {
        $ms = array(
            GModifiersObject::MODIFIER_ABSTRACT => 'abstract',
            GModifiersObject::MODIFIER_PUBLIC => 'public',
            GModifiersObject::MODIFIER_PRIVATE => 'private',
            GModifiersObject::MODIFIER_PROTECTED => 'protected',
            GModifiersObject::MODIFIER_STATIC => 'static',
            GModifiersObject::MODIFIER_FINAL => 'final'
        );

        $php = null;
        foreach ($ms as $const => $modifier) {
            if (($const & $bitmap) == $const) {
                $php .= $modifier . ' ';
            }
        }
        return $php;
    }

    /**
     * Adds an Import, that should be added to every written file
     *
     */
    public function addImport(ClassInterface $gClass, $alias = null)
    {
        $this->imports->add($gClass, $alias);
        return $this;
    }

    // @codeCoverageIgnoreStart

    /**
     * Removes an Import, that should be added to every written file
     *
     * @param string $alias case insensitive
     */
    public function removeImport($alias)
    {
        $this->imports->remove($alias);
        return $this;
    }

    /**
     * @param Webforge\Code\Generator\Imports $imports
     * @chainable
     */
    public function setImports(Imports $imports)
    {
        $this->imports = $imports;
        return $this;
    }
    // @codeCoverageIgnoreEnd

    /**
     * @return Webforge\Code\Generator\Imports
     */
    public function getImports()
    {
        return $this->imports;
    }

    public function getCodeWriter()
    {
        if (!isset($this->codeWriter)) {
            $this->codeWriter = new CodeWriter();
        }

        return $this->codeWriter;
    }
}
