<?php

namespace Webforge\Code\Generator;

class DocBlockTest extends \Webforge\Code\Test\Base
{
    protected $varDocBlock, $varDocBlockBody;
    protected $functionDocBlock, $functionDocBlockBody;

    public function setUp()
    {
        $this->chainClass = 'Webforge\\Code\\Generator\\DocBlock';
        parent::setUp();

        $this->varDocBlock = new DocBlock($this->varDocBlockBody =
        "/**
 * @var integer
 */\n");

        $this->functionDocBlock = new DocBlock($this->functionDocBlockBody =
        "/**
 * @param string \$haystack
 */\n");
    }

    public function testCanBeInstantiatedWithOutAnyBody()
    {
        $this->assertChainable(new DocBlock(null));
    }

    public function testCanBeConvertedToStringAgain()
    {
        $this->assertEquals($this->functionDocBlockBody, $this->functionDocBlock->toString());
        $this->assertEquals($this->varDocBlockBody, $this->varDocBlock->toString());
    }

    public function testReturnsIfSimpleAnnotationIsAvaible()
    {
        $this->assertTrue($this->varDocBlock->hasSimpleAnnotation('var'));
        $this->assertFalse($this->varDocBlock->hasSimpleAnnotation('param'));

        $this->assertTrue($this->functionDocBlock->hasSimpleAnnotation('param'));
        $this->assertFalse($this->functionDocBlock->hasSimpleAnnotation('var'));
    }

    public function testReturnsTheValueFromASimpleAnnotation()
    {
        $this->assertEquals('string $haystack', $this->functionDocBlock->parseSimpleAnnotation('param'));
        $this->assertEquals('integer', $this->varDocBlock->parseSimpleAnnotation('var'));
    }

    public function testSomethingCanBeAppendedToBody()
    {
        $this->functionDocBlock->append('@return bool');

        $modifiedDocBlockBody =
        "/**
 * @param string \$haystack
 * @return bool
 */\n";

        $this->assertEquals($modifiedDocBlockBody, $this->functionDocBlock->toString());
    }

  /**
   * @dataProvider provideStripCommentAsteriks
   */
    public function testStripCommentAsteriks($comment, $stripped)
    {
        $this->assertEquals($stripped, $this->varDocBlock->stripCommentAsteriks($comment));
    }

    public static function provideStripCommentAsteriks()
    {
        $tests = array();

        $tests[] = array('
/**
 * @TODO inline Kommentare werden verschluckt, inline Kommentare verwirren den Einrücker
 * @TODO imports aus der OriginalKlasse müssen geparsed werden und beibehalten werden!
 *       => wir wollen einen ClassReader haben der aus der Datei die Imports zwischenspeichert udn dann an den ClassWriter geben kann
 *          der dann alles schön schreibt
 *          der ClassReader kann dann auch kommentare die "verloren gehen" verwalten und kann sogar so styles wie "use" oder sowas auslesen
 */
 ',

        '@TODO inline Kommentare werden verschluckt, inline Kommentare verwirren den Einrücker
@TODO imports aus der OriginalKlasse müssen geparsed werden und beibehalten werden!
      => wir wollen einen ClassReader haben der aus der Datei die Imports zwischenspeichert udn dann an den ClassWriter geben kann
         der dann alles schön schreibt
         der ClassReader kann dann auch kommentare die "verloren gehen" verwalten und kann sogar so styles wie "use" oder sowas auslesen'
        );

        $tests[] = array(
        '/**
  * Die input GClass
  * 
  * @var Psc\Code\Generate\GClass
  */
',
        'Die input GClass

@var Psc\Code\Generate\GClass'
        );

        $tests[] = array(
        '/** BrokenDocBlock Headline

 * Summary continues
*/',
        'BrokenDocBlock Headline

Summary continues'
        );


        $tests[] = array(
        '/**
 * Headline
 *
 * Summary is here
 */
',

        'Headline

Summary is here');


        $tests[] = array(
        '/** Headline */',
        'Headline'
        );


        $tests[] = array(
        '/** Headline
*/',
        'Headline'
        );

        return $tests;
    }
}
