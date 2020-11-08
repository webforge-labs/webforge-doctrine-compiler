<?php

namespace Webforge\Code\Generator;

use Webforge\Common\System\File;

/**
 * A ClassMapper maps a file to a class FQN
 *
 * notice: we never consider that a file can have more than one class in it. We always have one class in one file in its context.
 * normally the verbs for a mapper would be: mapFile, mapClass, but that is confusing because it doesnt say if it maps to or from File/Class
 * so get* is mor intuitive
 */
interface ClassFileMapper
{
    /**
     * Returns the File for a given Class FQN
     *
     * @param string FQN of the Class which file is to be determined
     * @return Webforge\Common\System\File
     */
    public function getFile($classFQN);

    /**
     * Returns the class FQN for a given File
     *
     * @param Webforge\Common\System\File $file the file including the class
     * @return string FQN of the Class which file is given
     */
    public function getClass(File $file);
}
