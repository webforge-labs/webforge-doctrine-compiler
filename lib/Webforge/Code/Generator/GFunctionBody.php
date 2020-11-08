<?php

namespace Webforge\Code\Generator;

use PHPParser_Lexer;
use PHPParser_Parser;
use Webforge\Common\ArrayUtil as A;

class GFunctionBody
{
    /**
     * PHPParser stmts
     *
     * @var array|NULL
     */
    protected $stmts;

    /**
     * @var array
     */
    protected $body;

    public function __construct(array $lines = array())
    {
        $this->body = $lines;
    }

    public static function create(array $body)
    {
        $gBody = new GFunctionBody($body);

        return $gBody;
    }

    public function php($baseIndent = 0, $eol = "\n")
    {
        if (!isset($this->stmts)) {
            $parser = new PHPParser_Parser(new PHPParser_Lexer);
            $body = A::join($this->body, "%s\n");

            $this->stmts = $parser->parse('<?php ' . $body);
        }

        $printer = new PrettyPrinter($baseIndent, $eol);

        return $printer->prettyPrint($this->stmts);
    }

    /**
     * Fügt dem Code der Funktion neue Zeilen am Ende hinzu
     *
     * @param array $codeLines
     */
    public function appendBodyLines(array $codeLines)
    {
        throw NotImplementedException('not yet');
        $this->stmts = null;
        $this->body = array_merge($this->body, $codeLines);
        return $this;
    }

    public function beforeBody(array $codeLines)
    {
        throw NotImplementedException('not yet');
        $this->stmts = null;
        $this->body = array_merge($codeLines, $this->body);
        return $this;
    }

    public function afterBody(array $codeLines)
    {
        throw NotImplementedException('not yet');
        $this->stmts = null;
        $this->body = array_merge($this->body, $codeLines);
        return $this;
    }

    public function insertBody(array $codeLines, $index)
    {
        $this->stmts = null;
        A::insertArray($this->body, $codeLines, $index);
        return $this;
    }

    public function isEmpty()
    {
        return count($this->body) === 0;
    }
}
