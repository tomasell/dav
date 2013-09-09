<?php

namespace Bread\DAV;

use Bread\REST;
use Bread\DAV\Interfaces\RFC4918;

abstract class Controller extends REST\Controller implements RFC4918
{
    abstract public function propfind($resource);
    
    abstract public function proppatch($resource);
    
    abstract public function mkcol($resource);
    
    abstract public function copy($resource);
    
    abstract public function move($resource);
    
    abstract public function lock($resource);
    
    abstract public function unlock($resource);
}