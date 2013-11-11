<?php

namespace Webforge\Doctrine\Compiler;

interface GClassBroker {

  /**
   * Returns a previous generated Entity or fully elevated Class
   */
  public function getElevated($fqn, $debugEntity);

}
