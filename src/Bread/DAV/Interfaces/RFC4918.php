<?php

namespace Bread\DAV\Interfaces;

use Bread\REST\Interfaces\RFC5789;

interface RFC4918 extends RFC5789
{
    public function propfind($resource);
    
    public function proppatch($resource);
    
    public function mkcol($resource);
    
    public function copy($resource);
    
    public function move($resource);
    
    public function lock($resource);
    
    public function unlock($resource);
}