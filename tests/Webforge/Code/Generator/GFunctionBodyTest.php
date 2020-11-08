<?php

namespace Webforge\Code\Generator;

use Webforge\Common\ArrayUtil as A;

class GFunctionBodyTest extends \Webforge\Code\Test\Base
{
    protected $body;

    public function setUp()
    {
    }

  /**
   * @dataProvider phpBodyExamples
   */
    public function testPHPCodeEqualsArrayLinesAcception(array $lines)
    {
        $body = GFunctionBody::create($lines);

        $this->assertCodeEquals(
            A::join($lines, "%s\n"),
            $body->php(0, "\n")
        );
    }

    public static function phpBodyExamples()
    {
        $tests = array();

        $php = function () use (&$tests) {
            $tests[] = array(func_get_args());
        };

        $php(
            'return $this->x;'
        );

        $php(
            'if (!isset($this->x)) {',
            '  $this->x = new PointValue(0);',
            '}',
            'return $this->x;'
        );

        $php(
            'switch ($var) {',
            null,
            "case 'x':",
            '  $this->setX($value);',
            'break;',
            null,
            "case 'y':",
            '  $this->setX($value);',
            'break;',
            '}'
        );

        return $tests;
    }

    public function testInsertBodyShiftsCodeLines()
    {
        $body = GFunctionBody::create(array('echo "hello";', 'return $this;'));
        $body->insertBody(array('echo "world!";'), 1);

        $this->assertCodeEquals(
            "echo 'hello';\n" .
            "echo 'world!';\n" .
            "return \$this;\n",
            $body->php(0, "\n")
        );
    }
}
