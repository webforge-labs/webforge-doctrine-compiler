<?php

namespace Webforge\Doctrine\Compiler;

use Webforge\Doctrine\Annotations\Writer as AnnotationsWriter;

/**
 * A value object to transport a docblock with complex annotations
 */
class AnnotationsDocBlock extends \Webforge\Code\Generator\DocBlock {

  protected $annotations;
  protected $writer;

  public function __construct($body, $annotations, AnnotationsWriter $writer) {
    parent::__construct($body);
    $this->annotations = $annotations;
    $this->writer = $writer;
  }

  /**
   * @return string
   */
  public function toString() {
    $body = $this->mergeBody();
    
    $br = "\n";

    $s  = '/**'.$br;
    $s .= ' * '.str_replace($br, $br.' * ',rtrim($body)).$br;
    $s .= $this->writeAnnotations();
    $s .= ' */'.$br;
    
    return $s;
  }

  protected function writeAnnotations() {
    $s = '';
    if (is_array($this->annotations)) {
      foreach ($this->annotations as $annotation) {
        $s .= ' * '.$this->writer->writeAnnotation($annotation)."\n";
      }
    }

    return $s;
  }

  public function getAnnotations() {
    return $this->annotations;
  }
}
