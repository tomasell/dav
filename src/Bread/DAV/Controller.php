<?php
namespace Bread\DAV;

use Bread\Configuration\Manager;
use Bread\DAV\Exceptions;
use Bread\DAV\Helpers;
use Bread\DAV\Interfaces\RFC4918;
use Bread\Helpers\DOM;
use Bread\Networking\HTTP\Client\Exceptions as HTTPExceptions;
use Bread\Networking\HTTP\Response;
use Bread\Promises;
use Bread\REST;
USE Bread\REST\Routing\Router;
use Bread\Storage\Hydration\Instance;
use DateTime;
use Exception;
use Bread\Promises\When;

abstract class Controller extends REST\Controller implements RFC4918
{

    const DAV_PREFIX = 'D';

    const DAV_NAMESPACE = 'DAV:';

    const OLD_DATETIME = 'Y-m-d\TH:i:s\Z';
    const WEBDAV_DATETIME = 'D, j M Y G:i:s \G\M\T';

    protected static $complianceClasses = array(
        '1',
        '2'
    );

    public function options($resource)
    {
        $this->response->headers['DAV'] = implode(',', static::$complianceClasses);
        return parent::options($resource);
    }

    public function delete($resource)
    {
        if ($resource->isLocked()) {
            throw new Exceptions\Locked();
        }
        return parent::delete($resource);
    }


    public function propfind($resource)
    {
        return $this->data->then(null, function () {
            $prefix = static::DAV_PREFIX;
            $ns = static::DAV_NAMESPACE;
            $propfind = new DOM\Document("{$prefix}:propfind", $ns);
            $propfind->registerNamespace($ns, $prefix);
            $propfind->root->append(new DOM\Node($propfind, "{$prefix}:allprop"));
            //FIXME why load? register prefix and namespace
            $propfind->load($propfind->__toString());
            return $propfind;
        })->then(function ($propfind) use($resource) {
            $properties = Helpers\Propfind::getProperties($propfind->root->nodes[0]);
            //TODO add acl in properties
            if($properties === 'propname') {
                $found = Helpers\Propfind::$allprop;
                $properties = array();
            } else {
                $found = array_intersect_key(array_merge($this->getLiveProperties($resource), array_filter($resource->__toArray())), $properties);
            }
            $notFound = array_fill_keys(array_keys(array_diff_key($properties, $found)),null);
            $href = ($resource instanceof Collection) ? $this->request->uri: $resource->href();
            $namespace = static::DAV_NAMESPACE;
            $multistatus = new DOM\Document("D:multistatus", $namespace);
            $multistatus->registerNamespace($namespace, 'D');
            $response = new View\Response($multistatus, $href);
            $foundNode = new View\Propstat($multistatus, 'HTTP/1.1 200 Ok');
            $notFoundNode = new View\Propstat($multistatus, 'HTTP/1.1 404 Not Found');
            foreach ($found as $property=>$value){
                $foundNode->addProp($property, $value, isset($properties[$property]) ? $properties[$property] : static::DAV_NAMESPACE);
            }
            if ($foundNode->getProp()->nodes[0]->hasChildNodes()) {
                $response->append($foundNode);
            }
            $multistatus->root->append($response);
            $this->response->status(207);
            $this->response->type('text/xml');
            if (! ($resource instanceof Collection)) {
                foreach ($notFound as $property => $value) {
                    $notFoundNode->addProp($property, $value, isset($properties[$property]) ? $properties[$property] : static::DAV_NAMESPACE);
                }
                if ($notFoundNode->getProp()->nodes[0]->hasChildNodes()) {
                    $response->append($notFoundNode);
                }
                return $this->response->flush($multistatus);
            }
            switch ($this->request->headers['Depth']) {
                case 'infinity':
                case 1:
                    foreach ($resource->getArrayCopy() as $model){
                        $found = array_intersect_key(array_merge($this->getLiveProperties($model), $model->__toArray()), $properties);
                        $notFound = array_fill_keys(array_keys(array_diff_key($properties, $found)),null);
                        $response = new View\Response($multistatus,  $model->href());
                        $foundNode = new View\Propstat($multistatus, 'HTTP/1.1 200 Ok');
                        $notFoundNode = new View\Propstat($multistatus, 'HTTP/1.1 404 Not Found');
                        foreach ($found as $property=>$value){
                            $foundNode->addProp($property, $value, isset($properties[$property]) ? $properties[$property] : static::DAV_NAMESPACE);
                        }
                        foreach ($notFound as $property=>$value){
                            $notFoundNode->addProp($property, $value, isset($properties[$property]) ? $properties[$property] : static::DAV_NAMESPACE);
                        }
                        if($foundNode->getProp()->nodes[0]->hasChildNodes()) {
                            $response->append($foundNode);
                        }
                        if($notFoundNode->getProp()->nodes[0]->hasChildNodes()) {
                            $response->append($notFoundNode);
                        }
                        $multistatus->root->append($response);
                    }
                case 0:
                    return $this->response->flush($multistatus);
            }
        });
    }

    public function proppatch($resource)
    {
        if ($resource->isLocked()) {
            throw new Exceptions\Locked();
        }
        return $this->data->then(function ($proppatch) use($resource) {
            $prefix = $proppatch->root->nodes[0]->prefix ? $proppatch->root->nodes[0]->prefix : static::DAV_PREFIX;
            $namespace = static::DAV_NAMESPACE;
            $href = ($resource instanceof Collection) ? $this->request->uri : $resource->href();
            $multistatus = new DOM\Document("D:multistatus", $namespace);
            $multistatus->registerNamespace($namespace, 'D');
            $response = new View\Response($multistatus, $href);
            $ok = new View\Propstat($multistatus, 'HTTP/1.1 200 Ok');
            $conflict = new View\Propstat($multistatus, 'HTTP/1.1 409 Conflict');
            $failedDependency = new View\Propstat($multistatus, 'HTTP/1.1 424 Failed Dependency');
            $this->parseProppatch($resource, $proppatch->root->nodes[0], $ok, $conflict);
            if($ok->getProp()->nodes[0]->hasChildNodes()){
                $response->append($ok);
            }
            if($conflict->getProp()->nodes[0]->hasChildNodes()){
                $response->append($conflict);
            }
            if($failedDependency->getProp()->nodes[0]->hasChildNodes()){
                $response->append($failedDependency);
            }
            $multistatus->root->append($response);
            return $resource->store()->then(function ($resource) use($multistatus) {
                $this->response->status(207);
                $this->response->type('text/xml');
                return $this->response->flush($multistatus);
            });
        }, function () {
            throw new HTTPExceptions\UnsupportedMediaType();
        });
    }

    public function mkcol($resource)
    {
        //TODO
    }

    public function copy($source)
    {
        if(!isset($this->request->headers['Destination'])) {
            throw new HTTPExceptions\BadRequest();
        } elseif($source->isLocked()) {
            throw new Exceptions\Locked();
        }
        if(isset($this->request->headers['If'])) {
            preg_match_all("/<(?<simpleref>[^>]*)> ?\(?(?<condition>[^\)]*)\)?/", $this->request->headers['If'], $matches);
            //TODO evaluate conditions
        }
        $destination = parse_url($this->request->headers['Destination']);
        $destination = $destination['path'];
        $route = new Router($this->request, $this->response);
        return $route->route($destination)->then(function ($result) use($source, $destination) {
            list ($callback, $target, $parameters) = $result;
            return $target->then(function ($target) {
                switch ($this->request->headers['Overwrite']) {
                    case 'F':
                        throw new HTTPExceptions\PreconditionFailed();
                    default:
                        $this->response->status(Response::STATUS_NO_CONTENT);
                        return true;
                }
            }, function () {
                $this->response->status(Response::STATUS_CREATED);
                return true;
            })->then(function () use($source, $parameters) {
                //TODO SET ALL ATTRIBUTES
                foreach ($parameters as $attribute => $value) {
                    if (isset($source->$attribute)) {
                        $source->$attribute = $value;
                    }
                }
                return $source->store()->then(function ($resource) {
                    $this->response->headers['Location'] = $resource->href();
                    return $this->response->flush();
                });
            });
        });
    }

    public function move($source)
    {
        if(!isset($this->request->headers['Destination'])) {
            throw new HTTPExceptions\BadRequest();
        } elseif($source->isLocked()) {
            throw new Exceptions\Locked();
        }
        $destination = parse_url($this->request->headers['Destination']);
        $destination = $destination['path'];
        $route = new Router($this->request, $this->response);
        return $route->route($destination)->then(function ($result) use($source, $destination) {
            list ($callback, $target, $parameters) = $result;
            return $target->then(function ($target) {
                switch ($this->request->headers['Overwrite']) {
                    case 'F':
                        throw new HTTPExceptions\PreconditionFailed();
                    default:
                        $this->response->status(Response::STATUS_NO_CONTENT);
                        return $target->delete();
                }
            }, function () {
                $this->response->status(Response::STATUS_CREATED);
                return true;
            })->then(function () use($source, $parameters) {
                foreach ($parameters as $attribute => $value) {
                    if (isset($source->$attribute)) {
                        $source->$attribute = $value;
                    }
                }
                return $source->store()->then(function ($resource) {
                    $this->response->headers['Location'] = $resource->href();
                    return $this->response->flush();
                });
            });
        });
    }

    public function lock($resource)
    {
        return $this->data->then(function ($lock) use($resource) {
            if ($resource->isLocked()) {
                throw new Exceptions\Locked();
            } else {
                $properties = array();
                preg_match('/Second-([0-9]*)$|Infinite$/', $this->request->headers['Timeout'], $second);
                if (isset($second[0]) && $second[0] !== 'Infinite') {
                    $properties['timeout'] = new DateTime("+{$second[1]} second");
                } else {
                    $properties['timeout'] = null;
                }
                if (isset($this->request->headers['Depth']) && $this->request->headers['Depth'] !== 'infinity') {
                    $properties['depth'] = intval($this->request->headers['Depth']);
                } else {
                    $properties['depth'] = null;
                }
                $properties = array_merge($properties,Helpers\Lock::getProperties($lock->root->nodes[0]));
                return $resource->lock($properties)->then(function ($lockdiscovery) {
                    $multistatus = new DOM\Document(static::DAV_PREFIX . ":prop", static::DAV_NAMESPACE);
                    $multistatus->registerNamespace(static::DAV_NAMESPACE, static::DAV_PREFIX);
                    $multistatus->root->append(new View\LockDiscovery($multistatus, $lockdiscovery->__toArray(), static::DAV_NAMESPACE));
                    $this->response->headers['Lock-Token'] = "<" . $lockdiscovery->locktoken . ">";
                    $this->response->status(200);
                    $this->response->type("text/xml");
                    return $this->response->flush($multistatus);
                });
            }
        }, function () {
            throw new HTTPExceptions\UnsupportedMediaType();
        });
    }

    public function unlock($resource)
    {
        return $resource->unlock($this->request->headers['Lock-Token'])->then(function () {
            $this->response->status(204);
            return $this->response->flush();
        }, function () {
            throw new HTTPExceptions\BadRequest();
        });
    }

    protected function parseProppatch($resource, $root, $ok, $conflict)
    {
        foreach ($root->childNodes as $node) {
            $name = $node->localName;
            $value = $node->nodeValue ? $node->nodeValue : null;
            switch ($name) {
                case 'set':
                case 'remove':
                case 'prop':
                    $this->parseProppatch($resource, $node, $ok, $conflict);
                    break;
                default:
                    switch (Manager::get(get_class($resource), "properties.$name.type")) {
                        case 'DateTime':
                            try {
                                $value = new DateTime($value);
                                $resource->$name = $value;
                                $ok->addProp($name, $value, $node->namespaceURI);
                            } catch (Exception $exception) {
                                $conflict->addProp($name, null, $node->namespaceURI);
                            }
                            break;
                        default:
                            try {
                                $resource->$name = $value;
                                $ok->addProp($name, $value, $node->namespaceURI);
                            } catch (InvalidAttribute $exception) {
                                $conflict->addProp($name, null, $node->namespaceURI);
                            }
                    }
            }
        }
    }

    protected function getLiveProperties($resource, $accept = 'json')
    {
        //TODO all live properties and all accept into view
        $json = json_encode($resource, JSON_PRETTY_PRINT);
        return array(
            'getcontentlanguage' => 'it-IT',
            'getcontentlength' => strlen($json),
            'getcontenttype' => $accept,
            'getetag' => md5($json),
            'resourcetype' => ($resource instanceof Collection) ? 'collection' : null,
            'supportedlock' => 'default'
        );
    }

    protected function allowedMethods()
    {
        return array_merge(parent::allowedMethods(), array(
            'PROPFIND',
            'PROPPATCH',
            'MKCOL',
            'COPY',
            'MOVE',
            'LOCK',
            'UNLOCK'
        ));
    }
}