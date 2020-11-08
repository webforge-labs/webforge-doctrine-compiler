<?php

namespace Webforge\Code\Test;

use Webforge\Common\StringUtil as S;

class CodeEqualsConstraintTest extends \Webforge\Code\Test\Base
{
  /**
   * @dataProvider provideMatches
   */
    public function testMatches($expectedCode, $actualCode)
    {
        $this->matchConstraint($expectedCode, $actualCode, true);
    }

  /**
   * @dataProvider provideNonMatches
   */
    public function testNonMatches($expectedCode, $actualCode)
    {
        $this->matchConstraint($expectedCode, $actualCode, false);
    }

    protected function matchConstraint($expectedCode, $actualCode, $shouldMatch)
    {
        $constraint = new CodeEqualsConstraint($expectedCode);

        $this->assertEquals(
            $shouldMatch,
            $constraint->matches($actualCode),
            "\n" .
                        S::eolVisible($constraint->normalizeCode($actualCode)) .
                        "\n" .
                        S::eolVisible($constraint->normalizeCode($expectedCode))
        );
    }

    public static function provideMatches()
    {
        $tests = array();

        $tests[] = array(
        'echo "hello world";',
        'echo  "hello world";'
        );

        $tests[] = array(
        'echo "hello world";',
        'echo  "hello world";'
        );

        $tests[] = array(
        'function factory(){',
        'function factory() {'
        );

        $tests[] = array(
        '$num = 7;',
        '$num = 7; '
        );

        $tests[] = array(
        '$num=7;',
        '$num = 7; '
        );

        $tests[] = array(
        '/* completeley */',
        '/* irrelevant */'
        );

        $tests[] = array(
        '',
        ''
        );

      // its an expression!
        $tests[] = array(
        'echo"";',
        'echo "";'
        );

        $tests[] = array(
        'f("");',
        'f ("");'
        );

        $tests[] = array(
        "(expression)\n{ call();",
        "(expression) {\n  call();"
        );

        $tests[] = array(
        <<<'PHP'
abstract class TestClass {
  
  protected $prop1 = 'banane';
  
  public static $prop2;
  
  public function comboBox($label, $name, $selected = NULL, $itemType = NULL, Array $commonItemData = array()) {
    // does not matter
    
    $oderDoch = true;
  }
  
  public static function factory(SomeClassForAHint $dunno) {
PHP
,
  // some whitespaces and indenting is changed and removed, the comment is stripped
        <<<'PHP'
abstract class TestClass {
  protected $prop1 = 'banane';
  public static $prop2;
  
  public function comboBox($label, $name, $selected = NULL, $itemType = NULL, Array $commonItemData = array())
  {
    $oderDoch = true;
  }
  
public static function factory(SomeClassForAHint $dunno){
PHP
        );

        return $tests;
    }

    public static function provideNonMatches()
    {
        $tests = array();

        $tests[] = array(
                     'echo "hello world";',
                     'echo "hello  world";'
                    );

        $tests[] = array(
      // this fails with symfony white space stripper
        'rememberThat("whitespaces in strings\n\nare important!")',
        'rememberThat("whitespaces in strings\nare important!")',
        );

        return $tests;
    }

    public function testToStringOutputContainsSomething()
    {
        $constraint = new CodeEqualsConstraint('echo "the exepected code";');
        $this->assertNotEmpty($constraint->toString());

        $constraint = new CodeEqualsConstraint('echo "the exepected code";');
        $constraint->matches('echo "the other code"');
        $this->assertNotEmpty($constraint->toString());
    }
}
