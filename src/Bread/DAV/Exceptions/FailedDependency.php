<?php
namespace Bread\DAV\Exceptions;

use Bread\Networking\HTTP\Exception;

class FailedDependency extends Exception
{

    protected $code = 424;

    protected $message = "Failed Dependency";
}
