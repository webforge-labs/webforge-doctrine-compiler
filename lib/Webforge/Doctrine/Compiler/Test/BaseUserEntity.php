<?php

namespace Webforge\Doctrine\Compiler\Test;

/**
 * A simple base class that user (compileduser) should extend
 */
abstract class BaseUserEntity {

    protected $id;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $usernameCanonical;

    /**
     * @var string
     */
    protected $email;

    /**
     * @var boolean
     */
    protected $enabled;

}