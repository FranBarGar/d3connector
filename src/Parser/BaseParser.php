<?php

namespace mortalswat\d3connector\Parser;

/**
 * Class BaseParser
 * @package mortalswat\d3connector\Parser
 */
abstract class BaseParser
{
    /**
     * @var array
     */
    protected $separators = [
        "\x1",
        "\xFE",
        "\xFD",
        "\xFC"
    ];
}
