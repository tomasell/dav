<?php
namespace Bread\DAV\Helpers;

class Lock
{

    public static function getProperties($node)
    {
        $properties = array();
        foreach ($node->childNodes as $prop) {
            $name = $prop->localName;
            switch ($name) {
                case 'lockscope':
                case 'locktype':
                    $properties[$name] = $prop->firstChild->localName;
                    break;
                case 'owner':
                    $properties[$name] = $prop->nodeValue;
                    break;
            }
        }
        return $properties;
    }
}