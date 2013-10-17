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

class Response extends DOM\Node
{

    public function __construct(DOM\Document $multistatus, $href)
    {
        $prefix = $multistatus->getNamespacePrefix($multistatus->root->nodes[0]->namespaceURI);
        parent::__construct($multistatus, "{$prefix}:response");
        $this->append(new DOM\Node($multistatus, "{$prefix}:href", $href));
    }
}

