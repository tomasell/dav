<?php
namespace Bread\DAV;

use Bread\Promises;
use Bread\REST;
use DateTime;


abstract class Model extends REST\Model
{
    use Properties\Dead;

    abstract public function href();

    public function isLocked()
    {
        return $this->lockdiscovery ? ($this->lockdiscovery->locktoken && (! $this->lockdiscovery->timeout || new DateTime() <= $this->lockdiscovery->timeout)) : false;
    }

    public function lock(array $params)
    {
        $params['locktoken'] = 'opaquelocktoken:'.Helpers\UUID::uuid4();
        if($this->lockdiscovery){
            foreach ($params as $property=>$value){
                $this->lockdiscovery->$property = $value;
            }
            return $this->lockdiscovery->store();
        }
        $this->lockdiscovery = new Properties\LockDiscovery($params);
        $this->lockdiscovery->store();
        return $this->store()->then(function($model){
            return $model->lockdiscovery;
        });
    }

    public function unlock($lockToken)
    {
        if ((! $this->lockdiscovery->timeout || $this->lockdiscovery->timeout >= new DateTime()) && rtrim(ltrim($lockToken,"<"),">") == $this->lockdiscovery->locktoken) {
            $this->lockdiscovery->locktoken = null;
            $this->lockdiscovery->depth = null;
            return $this->lockdiscovery->store();
        }
        return Promises\When::reject();
    }

    public static function fetch(array $search = array(), array $options = array())
    {
        return parent::fetch($search, $options)->then(function($collection) {
            return new Collection($collection->getArrayCopy());
        });
    }

    public function store()
    {
        $this->getlastmodified = new DateTime();
        return parent::store();
    }

}