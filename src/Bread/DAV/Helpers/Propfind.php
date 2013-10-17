<?php
namespace Bread\DAV\Helpers;

class Propfind
{

    public static $allprop = array(
        'creationdate' => null,
        'displayname' => null,
        'getcontentlanguage' => null,
        'getcontentlength' => null,
        'getcontenttype' => null,
        'getetag' => null,
        'getlastmodified' => null,
        'lockdiscovery' => null,
        'resourcetype' => null,
        'supportedlock' => null
    );

    public static function getProperties($node)
    {
        $properties = array();
        foreach ($node->childNodes as $prop) {
            $name = $prop->localName;
            switch ($name) {
                case 'propname':
                    return $name;
                case 'allprop':
                    $properties = static::$allprop;
                    break;
                case 'prop':
                case 'include':
                    $properties = array_merge($properties, static::getProperties($prop));
                    break;
                default:
                    $properties[$name] = $prop->namespaceURI;
            }
        }
        return $properties;
    }
}