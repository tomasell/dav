<?php
namespace Bread\DAV\Exceptions;

use Bread\Networking\HTTP\Exception;

class UnprocessableEntity extends Exception
{

    protected $code = 422;

    protected $message = "Unprocessable Entity";
}
