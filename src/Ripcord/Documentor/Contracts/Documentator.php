<?php

namespace Ripcord\Documentor\Contracts;

/**
 * This interface defines the minimum methods any documentor needs to implement.
 */
interface Documentor
{
    public function setMethodData($methods);

    public function handle($rpcServer);

    public function getIntrospectionXML();
}
