<?php

namespace mortalswat\d3connector\Parser;

use function count;
use function explode;
use function strpos;

/**
 * Class D3ResultParser
 * @package mortalswat\d3connector\Parser
 */
class D3ResultParser extends BaseParser
{
    /**
     * @param $string
     * @param int $position
     * @return array|string|null
     */
    public function parseElementsRecursive($string, $position = 0)
    {
        $numberSeparators = count($this->separators);

        if ($position < $numberSeparators) {
            if (false !== strpos($string, $this->separators[$position])) {
                $array = explode($this->separators[$position], $string);

                foreach ($array as $key => $value) {
                    $array[$key] = $this->parseElementsRecursive($value, $position + 1);
                }

                return $array;
            }

            $auxArray = [];
            for ($i = $position + 1; $i < $numberSeparators; ++$i) {
                if (false !== strpos($string, $this->separators[$i])) {
                    $auxArray[] = $this->parseElementsRecursive($string, $i);
                    return $auxArray;
                }
                $auxArray[] = [];
            }
        }

        return ($string === '' ? null : $string);
    }
}
