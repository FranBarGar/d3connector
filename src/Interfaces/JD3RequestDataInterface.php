<?php

namespace mortalswat\d3connector\Interfaces;

/**
 * Interface JD3RequestDataInterface
 * @package mortalswat\d3connector\Interfaces
 */
interface JD3RequestDataInterface
{
    /**
     * @return array
     */
    public function arrayToD3(): array;

    /**
     * @return string
     */
    public function getRoutineName(): string;
}
