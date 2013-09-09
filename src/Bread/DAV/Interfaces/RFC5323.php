<?php

namespace Bread\DAV\Interfaces;

interface RFC5323 extends RFC4918
{
    public function search($resource);
}