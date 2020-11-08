<?php

namespace Webforge\Types;

use Psc\Form\IntegerValidatorRule;
use Webforge\Types\Adapters\TypeRuleMapper;

/**
 * Der type für eine Ganzzahl
 *
 * hier ist auch die Frage, ob wir eine SmallInt oder PositiveInt-Klasse machen oder lieber ein Property setzen
 *
 * ich entscheide mich hier für Klassen
 *  - SmallInteger (PositiveSmallInteger)
 *  - PositiveInteger
 *  - NegativeInteger
 *
 * weil Positive und Negative echt schwierig mit boolschen variablen in einer Klasse abbildbar sind. Höchsten Konstanten könnte gehen, aber das kann man dann auch faken in dem man einfach sagt, dass der Typ immutable ist (also Klassen zu anderen Klassen werden können)
 *
 * Bei Default kann ein Integer negativ, 0 und positiv sein (also Mathematisch eine Ganzzahl)
 * mit setZero(FALSE) kann dann die 0 ausgeschlossen werden
 *
 * z. B. Eine AutoIncrement-ID in der Datenbank ist ein PositiveInteger mit $zero === FALSE
 */
class IntegerType extends Type implements ValidationType, DoctrineExportableType, MappedComponentType, SerializationType
{
  /**
   * @var bool
   */
    protected $zero = true;

  /**
   * @return bool
   */
    public function hasZero()
    {
        return $this->zero;
    }

  /**
   * @param bool $zero
   * @chainable
   */
    public function setZero($zero)
    {
        $this->zero = $zero == true;
        return $this;
    }

    public function getValidatorRule(TypeRuleMapper $mapper)
    {
        return $mapper->createRule(new IntegerValidatorRule($this->hasZero())); // die gibts noch nicht @TODO: fixme
    }

  /**
   * Gibt den String zurück, der in @Doctrine\ORM\Mapping\Column(type="%s")  benutzt werden kann
   */
    public function getDoctrineExportType()
    {
        return 'integer';
    }

    public function getSerializationType()
    {
        return 'integer';
    }

    public function getDocType()
    {
        return 'int';
    }

  /**
   *
   * @return \Psc\CMS\Component
   */
    public function getMappedComponent(\Webforge\Types\Adapters\ComponentMapper $componentMapper)
    {
        return $componentMapper->createComponent('IntegerField');
    }

    public function __toString()
    {
        return '[Type:' . $this->getTypeClass()->getFQN() . ' Zero:' . ($this->hasZero() ? 'true' : 'false') . ']';
    }
}
