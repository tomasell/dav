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
use Bread\DAV\Controller;
use Bread\DAV\Helpers;

class Propstat extends DOM\Node
{

    protected $prop;
    protected $multistatus;

    public function __construct(DOM\Document $multistatus, $status)
    {
        $prefix = $multistatus->getNamespacePrefix($multistatus->root->nodes[0]->namespaceURI);
        parent::__construct($multistatus, "{$prefix}:propstat");
        $this->multistatus = $multistatus;
        $this->prop = $this->append(new DOM\Node($this->multistatus, "{$prefix}:prop"));
        $this->append(new DOM\Node($this->multistatus, "{$prefix}:status", $status));
    }

    public function addProp($name, $value, $namespace)
    {
        $this->multistatus->registerNamespace($namespace, uniqid('p'));
        $prefix = $this->multistatus->getNamespacePrefix($namespace);
        switch ($name) {
            case 'creationdate':
                if($value) {
                    $this->prop->append(new DOM\Node($this->multistatus, "{$prefix}:{$name}", date_format($value, Controller::OLD_DATETIME)));
                } else {
                    $this->prop->append(new DOM\Node($this->multistatus, "{$prefix}:{$name}"));
                }
                break;
            case 'getlastmodified':
                if($value) {
                    $this->prop->append(new DOM\Node($this->multistatus, "{$prefix}:{$name}", date_format($value, Controller::WEBDAV_DATETIME)));
                } else {
                    $this->prop->append(new DOM\Node($this->multistatus, "{$prefix}:{$name}"));
                }
                break;
            case 'resourcetype':
                $resource = $this->prop->append(new DOM\Node($this->multistatus, "{$prefix}:{$name}"));
                if ($value) {
                    $resource->append(new DOM\Node($this->multistatus, "{$prefix}:{$value}"));
                }
                break;
            case 'lockdiscovery':
                $this->prop->append(new LockDiscovery($this->multistatus, $value, $namespace));
                break;
            case 'supportedlock':
                if ($value) {
                    $this->prop->append(new SupportedLock($this->multistatus, $namespace));
                } else {
                    $this->prop->append(new DOM\Node($this->multistatus, "{$prefix}:{$name}"));
                }
                break;
            case 'acl':
              // TODO
                 break;
            case 'ordering-type':
             // TODO
                break;
            case 'successor-set':
            case 'predecessor-set':
                // TODO
                break;
            default:
                if ($value instanceof Node) {
                    $this->prop->append($value);
                } elseif ($value) {
                    $this->prop->append(new DOM\Node($this->multistatus, "{$prefix}:{$name}", $value));
                } else {
                    $this->prop->append(new DOM\Node($this->multistatus, "{$prefix}:{$name}"));
                }
        }
    }

    public function getProp()
    {
        return $this->prop;
    }
}
