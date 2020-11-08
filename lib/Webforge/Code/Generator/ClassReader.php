<?php

namespace Webforge\Code\Generator;

use PHPParser_Error;
use PHPParser_Lexer;
use PHPParser_NodeTraverser;
use PHPParser_NodeVisitor_NameResolver;
use PHPParser_Parser;
use RuntimeException;
use Webforge\Common\System\File;

class ClassReader
{
    public $stmts;

    /**
     * @var Webforge\Code\Generator\NodeVisitor
     */
    protected $nodeVisitor;

    /**
     * @return GClass
     */
    public function read(File $file)
    {
        $code = $file->getContents();

        $parser = new PHPParser_Parser(new PHPParser_Lexer());

        $traverser = new PHPParser_NodeTraverser();
        $traverser->addVisitor(new PHPParser_NodeVisitor_NameResolver()); // we will need resolved names
        $traverser->addVisitor($visitor = $this->createNodeVisitor());

        try {
            $this->stmts = $parser->parse($code);

            $traverser->traverse($this->stmts);
        } catch (PHPParser_Error $e) {
            throw new RuntimeException(sprintf("File '%s' cannot be read correctly from ClassReader.", $file), 0, $e);
        }

        return $visitor->getGClass();
    }

    public function readInto(File $file, GClass $gClass)
    {
        $this->nodeVisitor = new NodeVisitor($gClass);

        $ret = $this->read($file);

        $this->nodeVisitor = null;

        return $ret;
    }

    public function createNodeVisitor()
    {
        if (isset($this->nodeVisitor)) {
            return $this->nodeVisitor;
        }

        return new NodeVisitor();
    }
}
