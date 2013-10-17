<?php
namespace Bread\DAV\Exceptions;

use Bread\Networking\HTTP\Exception;

class InsufficientStorage extends Exception
{

    protected $code = 507;

    protected $message = "Insufficient Storage";
}
