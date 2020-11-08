<?php

namespace Webforge\Doctrine\Compiler\Types;

use Webforge\Code\Generator\GClass;
use Webforge\Types\DoctrineExportableType;
use Webforge\Types\GClassAdapter;
use Webforge\Types\InterfacedType;
use Webforge\Types\ParameterHintedType;
use Webforge\Types\Psc;
use Webforge\Types\Type;

class DecimalType extends Type implements DoctrineExportableType, InterfacedType, ParameterHintedType
{
    /**
     * @var GClass
     */
    protected $interfaceClass;

    public function __construct()
    {
        parent::__construct();
        $this->interfaceClass = GClassAdapter::newGClass($this->getInterface());
    }

    /**
     * Returns a string that can be used as @Doctrine\ORM\Mapping\Column(type="%s")
     */
    public function getDoctrineExportType()
    {
        return 'decimal_object';
    }

    public function getDocType()
    {
        return '\\Decimal\\Decimal';
    }

    /**
     * Gibt das Interface zurück welches den Type implementiert
     *
     * @return string der FQN des Interfaces der Klasse ohne \ davor.
     */
    public function getInterface()
    {
        return 'Decimal\\Decimal';
    }

    /**
     * @inheritdoc
     */
    public function getParameterHint($useFQN = true)
    {
        if ($useFQN) {
            return '\\' . $this->interfaceClass->getFQN();
        } else {
            return $this->interfaceClass->getName();
        }
    }

    public function getParameterHintImport()
    {
        return $this->interfaceClass;
    }
}
