<?php
namespace Bread\DAV\Properties;

/**
 * RFC4918 Section 3
 * Live Property - A property whose semantics and syntax are enforced by the server.
 *
 * http://www.webdav.org/specs/rfc4918.html#rfc.section.3
 */
trait Live
{

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
     * Specifies the nature of the resource.
     *
     * @see RFC4918 Section 15.9
     */
    public $resourcetype;

    /**
     * To provide a listing of the lock capabilities supported by the resource.
     *
     * @see RFC4918 Section 15.10
     */
    public  $supportedlock;

}