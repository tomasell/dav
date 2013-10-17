<?php
namespace Bread\DAV\Exceptions;

use Bread\Networking\HTTP\Exception;

class Locked extends Exception
{

    protected $code = 423;

    protected $message = "Locked";
}
