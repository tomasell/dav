<?php
namespace Bread\DAV;

use Bread\Storage;

class Collection extends Storage\Collection
{
    use Properties\Dead;

    public function __toArray()
    {
        return get_object_vars($this);
    }
}