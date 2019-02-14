<?php

namespace mortalswat\d3connector\Interfaces;

/**
 * Interface ParserInterface
 */
interface ParserInterface
{
    /**
     * @param array $data
     * @return array
     * @throws \mortalswat\d3connector\StructureArray\StructureArrayException
     */
    public function parse(array $data);
}