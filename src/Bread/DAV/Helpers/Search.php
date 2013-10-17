<?php
namespace Bread\DAV\Helpers;

use DateTime;
use DOMText;
use DOMElement;
use Bread\REST\Routing\Router;
use Bread\Promises\When;

class Search
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

    public static function getProperties($node, $request, $response)
    {
        $properties = array();
        foreach ($node->childNodes as $prop) {
            $name = $prop->localName;
            switch ($name) {
                case 'select':
                case 'from':
                case 'limit':
                case 'scope':
                case 'order':
                case 'where':
                case 'orderby':
                    $properties[$name] = static::getProperties($prop, $request, $response);
                    break;
                case 'allprop':
                    $properties = static::$allprop;
                    break;
                case 'include':
                    $properties = array_merge($properties, array(static::getProperties($prop, $request, $response)));
                    break;
                case 'basicsearch':
                    $properties = static::getProperties($prop, $request, $response);
                    break;
                default:
                    if ($prop->firstChild instanceof DOMText) {
                        $attributes = array();
                        foreach ($prop->attributes as $attribute) {
                            $attributes[$attribute->name] = $attribute->value;
                        }
                        $properties[$name] = static::validate($attributes, $prop->firstChild->nodeValue, $request, $response);
                    } else {
                        $properties[$name] = static::getProperties($prop, $request, $response);
                    }
            }
        }
        return When::all($properties);
    }

    protected static function validate($attributes, $nodeValue, $request, $response)
    {
        foreach ($attributes as $attribute => $value) {
            switch ($attribute) {
                case 'type':
                    switch ($value) {
                        //TODO more types
                        case 'integer':
                            $nodeValue = intval($nodeValue);
                            break;
                        case 'DateTime':
                            $nodeValue = new DateTime($nodeValue);
                            break;
                        case 'href':
                            $route = new Router($request, $response);
                            $nodeValue =  $route->route($nodeValue)->then(function ($result) use($nodeValue) {
                                list ($callback, $target, $parameters) = $result;
                                return $target;
                            });
                    }
                    break;
            }
        }
        return $nodeValue;
    }
}