<?php

namespace Bread\DAV;

/**
 * RFC4918 Section 15
 *
 * @see http://tools.ietf.org/html/rfc4918#section-15
 */
trait Properties
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
     * Contains the Content-Language header value (from Section
     * 14.12 of [RFC2616]) as it would be returned by a GET without
     * accept headers.
     *
     * @see RFC4918 Section 15.3
     * @var string
     */
    public $getcontentlanguage;

    /**
     * Contains the Content-Length header returned by a GET
     * without accept headers.
     *
     * @see RFC4918 Section 15.4
     * @var integer
     */
    public $getcontentlength;

    /**
     * Contains the Content-Type header value (from Section 14.17
     * of [RFC2616]) as it would be returned by a GET without accept
     * headers.
     *
     * @see RFC4918 Section 15.5
     * @var string
     */
    public $getcontenttype;

    /**
     * Contains the ETag header value (from Section 14.19 of
     * [RFC2616]) as it would be returned by a GET without accept
     * headers.
     *
     * @see RFC4918 Section 15.6
     * @var string
     */
    public $getetag;

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