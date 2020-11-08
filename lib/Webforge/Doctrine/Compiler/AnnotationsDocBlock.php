<?php

namespace Webforge\Doctrine\Compiler;

use Webforge\Code\Generator\DocBlock;
use Webforge\Doctrine\Annotations\Writer as AnnotationsWriter;

/**
 * A value object to transport a docblock with complex annotations
 *
 * *1 it is possible to pass strings in the annotations array, because the jms serializer annotations are not easy to write
 *    they break some conventions the doctrine annotations have and can therefore not be written constistently with the writer
 */
class AnnotationsDocBlock extends DocBlock

{
    protected $annotations;
    protected $writer;

    public function __construct($body, $annotations, AnnotationsWriter $writer)
    {
        parent::__construct($body);
        $this->annotations = $annotations;
        $this->writer = $writer;
    }

    /**
     * @return string
     */
    public function toString()
    {
        $body = $this->mergeBody();

        $br = "\n";

        $s = '/**' . $br;
        $s .= ' * ' . str_replace($br, $br . ' * ', rtrim($body)) . $br;
        $s .= $this->writeAnnotations();
        $s .= ' */' . $br;

        return $s;
    }

    protected function writeAnnotations()
    {
        $s = '';
        if (is_array($this->annotations)) {
            foreach ($this->annotations as $annotation) {
                if (is_string($annotation)) { // dirty hack to write jms serializer annotations *1
                    $s .= ' * ' . $annotation . "\n";
                } else {
                    $s .= ' * ' . $this->writer->writeAnnotation($annotation) . "\n";
                }
            }
        }

        return $s;
    }

    public function getAnnotations()
    {
        return $this->annotations;
    }
}
