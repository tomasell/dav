<?php
namespace Bread\DAV\Properties;

use Bread\Configuration\Manager as CM;
use Bread\REST;

class LockDiscovery extends REST\Model
{

    protected $id;

    protected $timeout;

    protected $owner;

    protected $locktype;

    protected $lockscope;

    protected $locktoken;

    protected $depth;
}

CM::defaults('Bread\DAV\Properties\LockDiscovery', array(
    'keys' => array(
        'id'
    ),
    'properties' => array(
        'id' => array(
            'type' => 'integer',
            'strategy' => 'autoincrement'
        ),
        'timeout' => array(
            'type' => 'DateTime'
        ),
        'owner' => array(
            'type' => 'string'
        ),
        'locktype' => array(
            'type' => 'string',
            'default' => 'write'
        ),
        'lockscope' => array(
            'type' => 'string',
            'values' => array(
                'exclusive',
                'shared'
            ),
            'default' => 'exclusive'
        ),
        'locktoken' => array(
            'type' => 'string'
        ),
        'depth' => array(
            'type' => 'integer'
        )
    )
));