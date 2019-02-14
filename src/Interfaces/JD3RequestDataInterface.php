<?php

namespace mortalswat\d3connector\Interfaces;

/**
 * Interface JD3RequestDataInterface.
 */
interface JD3RequestDataInterface
{
    /**
     * @return array
     */
    public function arrayToD3();

    /**
     * @return string
     */
    public function getRoutineName();
}
