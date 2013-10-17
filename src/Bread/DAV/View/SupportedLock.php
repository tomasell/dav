<?php
/**
 * Bread PHP Framework (http://github.com/saiv/Bread)
 * Copyright 2010-2012, SAIV Development Team <development@saiv.it>
 *
 * Licensed under a Creative Commons Attribution 3.0 Unported License.
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright  Copyright 2010-2012, SAIV Development Team <development@saiv.it>
 * @link       http://github.com/saiv/Bread Bread PHP Framework
 * @package    Bread
 * @since      Bread PHP Framework
 * @license    http://creativecommons.org/licenses/by/3.0/
 */
namespace Bread\DAV\View;

use Bread\Helpers\DOM;

class SupportedLock extends DOM\Node
{

    public function __construct(DOM\Document $multistatus, $namespace)
    {
        $prefix = $multistatus->getNamespacePrefix($namespace);
        parent::__construct($multistatus, "{$prefix}:supportedlock");
        $lockEntry1 = new DOM\Node($multistatus, "{$prefix}:lockentry");
        $lockEntry2 = new DOM\Node($multistatus, "{$prefix}:lockentry");
        $this->append($lockEntry1);
        $this->append($lockEntry2);
        $lockEntry1->append(new DOM\Node($multistatus, "{$prefix}:lockscope"))->append(new DOM\Node($multistatus, "{$prefix}:exclusive"));
        $lockEntry1->append(new DOM\Node($multistatus, "{$prefix}:locktype"))->append(new DOM\Node($multistatus, "{$prefix}:write"));
        $lockEntry2->append(new DOM\Node($multistatus, "{$prefix}:lockscope"))->append(new DOM\Node($multistatus, "{$prefix}:shared"));
        $lockEntry2->append(new DOM\Node($multistatus, "{$prefix}:locktype"))->append(new DOM\Node($multistatus, "{$prefix}:write"));
    }
}

