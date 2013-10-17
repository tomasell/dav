<?php
namespace Bread\DAV\Search;

use Bread\DAV;
use Bread\DAV\Helpers;
use Bread\DAV\Interfaces\RFC5323;
use Bread\DAV\View;
use Bread\Helpers\DOM;
use Bread\Networking\HTTP\Client\Exceptions as HTTPExceptions;
use Bread\Promises\When;
use Bread\REST\Routing\Route;

abstract class Controller extends DAV\Controller implements RFC5323
{

    const INFINITY = 'infinity';

    const ASCENDING = 1;

    const DESCENDING = - 1;

    protected static $dasl = array(
        'DAV:basicsearch'
    );

    public function options($resource)
    {
        $this->response->headers['DASL'] = "<" . implode('>,<', static::$dasl) . ">";
        return parent::options($resource);
    }

    public function search($resource)
    {
        return $this->data->then(function ($search) use($resource) {
            return Helpers\Search::getProperties($search->root->nodes[0], $this->request, $this->response)->then(function($data) use($resource) {
                $profind = null;
                $from = null;
                $where = array();
                $orderby = array();
                $limit = null;
                foreach ($data as $query => $value) {
                    switch ($query) {
                        case 'select':
                            $propfind = $this->createPropfind($value);
                            break;
                        case 'from':
                            $from = $this->from($value);
                            break;
                        case 'where':
                            $where = array_shift($this->where($value));
                            break;
                        case 'orderby':
                            foreach ($value as $order) {
                                $orderby = array_merge($orderby, $this->sort($order));
                            }
                            break;
                        case 'limit':
                            $limit = array_shift($value);
                            break;
                    }
                }
                if(!$propfind || !$where) {
                    throw new HTTPExceptions\PreconditionFailed();
                }
                $iterator = $resource->getIterator();
                $match = array();
                foreach ($iterator as $offset=>$model){
                    //TODO add metadata
                    When::all($this->filter($model, $where), function() use ($model, &$match) {
                        $match[] = $model;
                    });
                }
                if($match) {
                    $resource->exchangeArray($match);
                } else {
                    throw new HTTPExceptions\NotFound();
                }
                if($orderby){
                    $resource->uasort(function($el_1, $el_2) use($orderby) {
                        foreach ($orderby as $attribute=>$ordering){
                            if ($el_1->$attribute == $el_2->$attribute) {
                                continue;
                            }elseif($el_1->$attribute < $el_2->$attribute){
                                return ($ordering === static::ASCENDING) ? -1 : 1;
                            } else{
                                return ($ordering === static::ASCENDING) ? 1 : -1;
                            }
                        }
                        return 0;
                    });
                }
                if($limit) {
                    $resource->exchangeArray(array_slice($resource->getArrayCopy(),0, $limit ));
                }
                return $this->getResponse($propfind, $resource);
            });
        }, function () {
            throw new HTTPExceptions\UnsupportedMediaType();
        });
    }

    protected function createPropfind($properties)
    {
        $prefix = static::DAV_PREFIX;
        $ns = static::DAV_NAMESPACE;
        $propfind = new DOM\Document("{$prefix}:propfind", $ns);
        $propfind->registerNamespace($ns, $prefix);
        $prop = $propfind->root->append(new DOM\Node($propfind, "{$prefix}:prop"));
        foreach ($properties as $property=>$null) {
            $prop->append(new DOM\Node($propfind, "{$prefix}:$property"));
        }
        $propfind->load($propfind->__toString());
        return $propfind;
    }

    protected function from($scopes)
    {
        return array_shift($scopes);
    }

    protected function where($conditions)
    {
        $where = array();
        foreach ($conditions as $condition => $value) {
            switch ($condition) {
                case 'and':
                case 'or':
                case 'not':
                case 'nor':
                    $where[] = array(
                        '$' . $condition => $this->where($value)
                    );
                    break;
                case 'eq':
                    $literal = $this->getProp($value);
                    $where[] = array(
                        $literal['condition'] => $literal['literal']
                    );
                    break;
                default:
                    $literal = $this->getProp($value);
                    $where[] = array(
                        $literal['condition'] => array(
                            '$' . $condition => $literal['literal']
                        )
                    );
                    break;
            }
        }
        return $where;
    }

    protected function getProp($prop)
    {
        $condition = null;
        $literal = null;
        foreach ($prop as $attribute=>$value) {
            switch ($attribute) {
                case 'prop':
                    $condition = array_shift(array_keys($value));
                    break;
                case 'typed-literal':
                case 'literal':
                    $literal = $value;
                    break;
            }
        }
        return array(
            'literal' => $literal,
            'condition' => $condition
        );
    }

    protected function filter($resource, $search)
    {
        //TODO not
        $where = array();
        foreach ($search as $condition => $value) {
            switch ($condition) {
                case '$and':
                    $and = array();
                    foreach ($value as $cond){
                        $and = array_merge($this->filter($resource, $cond), $and);
                    }
                    $where[] = When::all($and);
                    break;
                case '$or':
                    $or = array();
                    foreach ($value as $cond){
                        $or= array_merge($this->filter($resource, $cond), $or);
                    }
                    $where[] = When::any($or);
                    break;
                case '$nor':
                    $nor = array();
                    foreach ($value as $cond){
                        $nor= array_merge($this->filter($resource, $cond), $nor);
                    }
                    $where[] = When::any($nor)->then(function () {
                        return When::reject();
                    }, function () {
                        return When::resolve();
                    });
                    break;
                default:
                    if(is_array($value)){
                        $key = key($value);
                        $where[] = isset($resource->$condition) ? $this->matchRule($resource->$condition, array_shift($value), $key) : When::reject();
                    } else {
                        $where[] = isset($resource->$condition) ? $this->matchRule($resource->$condition, $value) : When::reject();
                    }
            }
        }
        return $where;
    }

    protected function matchRule($attribute, $value, $operator = '=')
    {
        switch ($operator) {
            case '=':
                return $attribute === $value ? When::resolve() : When::reject();
            case '$ne':
                return $attribute !== $value ? When::resolve() : When::reject();
            case '$lt':
                return $attribute < $value ? When::resolve() : When::reject();
            case '$lte':
                return $attribute <= $value ? When::resolve() : When::reject();
            case '$gt':
                return $attribute > $value ? When::resolve() : When::reject();
            case '$gte':
                return $attribute >= $value ? When::resolve() : When::reject();
            case '$not':
                return $attribute !== $value ? When::resolve() : When::reject();
            case '$all':
                return array_intersect($value, $attribute) === $value ? When::resolve() : When::reject();
            case '$in':
                $attribute = is_array($attribute) ? $attribute : array($attribute);
                return array_intersect($value, $attribute) !== array() ? When::resolve() : When::reject();
            case '$nin':
                $attribute = is_array($attribute) ? $attribute : array($attribute);
                return array_intersect($value, $attribute) === array() ? When::resolve() : When::reject();
        }
    }

    protected function sort($orderby)
    {
        $condition = null;
        $literal = null;
        foreach ($orderby as $attribute=>$value){
            switch ($attribute) {
                case 'prop':
                    $condition = array_shift(array_keys($value));
                    break;
                case 'ascending':
                    $literal = static::ASCENDING;
                    break;
                case 'descending':
                    $literal = static::DESCENDING;
                    break;
            }
        }
        return array(
            $condition => $literal
        );
    }

    protected function getResponse($propfind, $resource)
    {
        $properties = Helpers\Propfind::getProperties($propfind->root->nodes[0]);
        $namespace = static::DAV_NAMESPACE;
        $multistatus = new DOM\Document("D:multistatus", $namespace);
        $multistatus->registerNamespace($namespace, 'D');
        $this->response->status(207);
        $this->response->type('text/xml');
        if(!$resource->getArrayCopy()){
            throw new HTTPExceptions\NotFound();
        }
        foreach ($resource->getArrayCopy() as $model) {
            $found = array_intersect_key(array_merge($model->__toArray(), $this->getLiveProperties($model)), $properties);
            $notFound = array_fill_keys(array_keys(array_diff_key($properties, $found)),null);
            $response = new View\Response($multistatus,  $model->href());
            $foundNode = new View\Propstat($multistatus, 'HTTP/1.1 200 Ok');
            $notFoundNode = new View\Propstat($multistatus, 'HTTP/1.1 404 Not Found');
            foreach ($found as $property=>$value){
                $foundNode->addProp($property, $value, isset($properties[$value]) ? $properties[$value] : static::DAV_NAMESPACE);
            }
            foreach ($notFound as $property=>$value){
                $notFoundNode->addProp($property, $value, isset($properties[$value]) ? $properties[$value] : static::DAV_NAMESPACE);
            }
            if($foundNode->getProp()->nodes[0]->hasChildNodes()){
                $response->append($foundNode);
            }
            if($notFoundNode->getProp()->nodes[0]->hasChildNodes()){
                $response->append($notFoundNode);
            }
            $multistatus->root->append($response);
        }
        return $this->response->flush($multistatus);
    }

    protected function allowedMethods()
    {
        return array_merge(parent::allowedMethods(), array(
            'SEARCH'
        ));
    }
}
