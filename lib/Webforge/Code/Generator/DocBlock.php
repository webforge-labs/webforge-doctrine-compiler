<?php

namespace Webforge\Code\Generator;

use Webforge\Common\Preg;

/**
 * Abstraction for a DocBlock
 *
 * SimpleAnnotations are one-line-annotations like
 * (at)var integer
 * or
 * (at)param string $needle
 *
 */
class DocBlock
{
    /**
     * @var string
     */
    protected $summary;

    /**
     * @var string without the asteriks
     */
    protected $body;


    public function __construct($body = null)
    {
        $this->summary = null;
        $this->setBody($body);
    }

    /**
     * @chainable
     */
    public function setBody($body = null)
    {
        if ($body != null) {
            $this->body = $this->parseBody($body);
        } else {
            $this->body = null;
        }
        return $this;
    }

    protected function parseBody($body)
    {
        /* wir schauen nach ob der Body z.B. noch kommentare beeinhaltet */
        if (Preg::qmatch($body, '|^/\*+|', 0) !== null) {
            $body = $this->stripCommentAsteriks($body);
        }

        return $body;
    }

    /**
     * Returns a docblock without the comments and * before
     *
     * @return string
     */
    public function stripCommentAsteriks($body)
    {
        /* okay, also das Prinzip ist eigentlich: lösche alle Tokens am Anfang der Zeile (deshalb /m)
           bzw * / am Ende der Zeile

           da aber bei leeren Zeilen im Docblock bzw. Zeilen ohne * das multi-line-pattern kaputt geht
           überprüfen wir im callback ob die zeile "leer" ist und fügen dann einen Umbruch ein.
           Wir behalten quasi die Umbrüche zwischendrin
        */
        $lines = array();

        $debug = "\n";
        foreach (explode("\n", ltrim($body)) as $line) {
            $tline = trim($line);
            $debug .= "'$tline'";

            $debug .= "\n  ";
            if ($tline === '*') {
                // nur ein sternchen bedeutet eine gewollte Leerzeile dazwischen
                $lines[] = null; // means zeilenumbruch
                $debug .= "newline";
            } elseif ($tline === '/**' || $tline === '/*' || $tline === '*/') {
                // top und bottom wollen wir nicht in unserem docBlock haben
                $debug .= "discard";
            } elseif (($content = Preg::qmatch($line, '/^\s*\/?\*+\s?(.*?)\s*$/')) !== null) {
                // eine Zeile mit * am Anfang => wir nehmen den $content (Einrückungssafe)
                // eine Zeile mit /*+ am Anfang => wir nehmen den content (Einrückungssafe)
                $content = rtrim($content, ' /*');  // read carefully
                $lines[] = $content;
                $debug .= "content: '" . $content . "'";
            } else {
                // dies kann jetzt nur noch eine Zeile ohne * sein (also ein kaputter Docblock). Den machen wir heile
                $lines[] = $line; // preservewhitespace davor?
                $debug .= "fix: '$line'";
            }

            $debug .= "\n";
        }
        $debug .= "ergebnis:\n";
        $debug .= "\n\n";

        // mit rtrim die leerzeilen nach dem letzten Text entfernen
        return rtrim(implode("\n", $lines));
    }


    /**
     * Returns the first matching Annotation with the given name and returns its value
     *
     *
     * @return string the word after the annotation (stopped being parsed at end of line)
     */
    public function parseSimpleAnnotation($name)
    {
        $m = array();
        if (Preg::match($this->body, '/@' . $name . '\s+([^\n]+)/i', $m)) {
            return rtrim($m[1]);
        }

        return null;
    }

    /**
     * @return bool
     */
    public function hasSimpleAnnotation($name)
    {
        return mb_strpos($this->body, '@' . ltrim($name, '@')) !== false;
    }

    /**
     * Adds a new line to the DocBlockBody
     *
     * the last line will be completed with EOL marker
     */
    public function append($string)
    {
        if (isset($this->body)) {
            $this->body .= "\n";
        }
        $this->body .= $string;

        return $this;
    }

    /**
     * @return string
     */
    protected function mergeBody()
    {
        $body = null;
        if (isset($this->summary)) {
            $body .= $this->summary . "\n"; // das macht die leerzeile nach der summary
            if (isset($this->body)) {
                $body .= "\n";
            }
        }
        $body .= $this->body;

        return $body;
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
        $s .= ' */' . $br;

        return $s;
    }
}
