<?php

namespace mortalswat\d3connector\Parser;

/**
 * Class D3RequestParser
 * @package mortalswat\d3connector\Parser
 */
class D3RequestParser extends BaseParser
{
    /**
     * @param $elements
     * @param int $position
     * @return string
     */
    public function parseElementsRecursive($elements, $position = 0)
    {
        foreach ($elements as $key => $element) {
            if (is_array($element)) {
                $elements[$key] = $this->parseElementsRecursive($element, $position + 1);
            } else {
                $elements[$key] = utf8_decode($elements[$key]);
            }
        }
        return implode($this->separators[$position], $elements);
    }
}
