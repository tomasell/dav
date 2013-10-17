<?php
namespace Bread\DAV\View;

use Bread\Helpers\DOM;
use DateTime;

class LockDiscovery extends DOM\Node
{
    public function __construct(DOM\Document $multistatus, $lock, $namespace)
    {
        $prefix = $multistatus->getNamespacePrefix($multistatus->root->nodes[0]->namespaceURI);
        parent::__construct($multistatus, "{$prefix}:lockdiscovery");
        if($lock && $lock = is_array($lock) ? $lock : $lock->__toArray()){
            $activelock = $this->append(new DOM\Node($multistatus, "{$prefix}:activelock"));
            foreach ($lock as $name => $value) {
                switch ($name) {
                    //TODO OWNER
                    case 'timeout':
                        $activelock->append(new DOM\Node($multistatus, "{$prefix}:$name", isset($value) ? $value : 'Infinite'));
                        break;
                    case 'locktype':
                    case 'lockscope':
                        $activelock->append(new DOM\Node($multistatus, "{$prefix}:$name"))->append(new DOM\Node($multistatus, "{$prefix}:$value"));
                        break;
                    case 'locktoken':
                        $activelock->append(new DOM\Node($multistatus, "{$prefix}:$name"))->append(new DOM\Node($multistatus, "{$prefix}:href", $value));
                        break;
                    case 'depth':
                        $activelock->append(new DOM\Node($multistatus, "{$prefix}:$name", isset($value) ? $value : 'infinity'));
                        break;
                }
            }
        }
    }
}
