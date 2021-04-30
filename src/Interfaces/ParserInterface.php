<?php

namespace mortalswat\d3connector\Interfaces;

use mortalswat\d3connector\StructureArray\StructureArrayException;

/**
 * Interface ParserInterface
 * @package mortalswat\d3connector\Interfaces
 */
interface ParserInterface
{
    /**
     * @param array $data
     * @return array
     * @throws StructureArrayException
     */
    public function parse(array $data): array;
}