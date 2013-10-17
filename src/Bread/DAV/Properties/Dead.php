<?php
namespace Bread\DAV\Properties;

use Bread\Configuration\Manager as CM;

/**
 * RFC4918 Section 3
 * Dead Property - A property whose semantics and syntax are not enforced by the server.
 * The server only records the value of a dead property; the client is responsible for maintaining the consistency of
 * the syntax and semantics of adead property.
 *
 * @see http://tools.ietf.org/html/rfc4918#section-3
 */
trait Dead
{

    /**
     * Records the time and date the resource was created.
     *
     * @see RFC4918 Section 15.1
     * @var DateTime
     */
    public $creationdate;

    /**
     * Provides a name for the resource that is suitable for
     * presentation to a user.
     *
     * @see RFC4918 Section 15.2
     * @var string
     */
    public $displayname;

    /**
     * Contains the Last-Modified header value (from Section
     * 14.29 of [RFC2616]) as it would be returned by a GET method
     * without accept headers.
     *
     * @see RFC4918 Section 15.7
     * @var DateTime
     */
    public $getlastmodified;

    /**
     * Describes the active locks on a resource.
     *
     * @see RFC4918 Section 15.8
     * @var string
     */
    public $lockdiscovery;
}

CM::defaults('Bread\DAV\Properties\Dead', array(
    'properties' => array(
        'creationdate' => array(
            'type' => 'DateTime'
        ),
        'displayname' => array(
            'type' => 'string'
        ),
        'getlastmodified' => array(
            'type' => 'DateTime'
        ),
        'lockdiscovery' => array(
            'type' => 'Bread\DAV\Properties\LockDiscovery'
        )
    )
));