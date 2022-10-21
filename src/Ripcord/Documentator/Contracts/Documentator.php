<?php

namespace Ripcord\Documentator\Contracts;

/**
 * This interface defines the minimum methods any documentor needs to implement.
 */
interface Documentator
{
    public function setMethodData($methods);

    public function handle($rpcServer);

    public function getIntrospectionXML();
}
