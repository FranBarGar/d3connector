<?php

namespace mortalswat\d3connector\Parser;

/**
 * Class D3ResultParser
 * @package mortalswat\d3connector\Parser
 */
class D3ResultParser extends BaseParser
{
    /**
     * @param $string
     * @param int $position
     * @return array
     */
    public function parseElementsRecursive($string, $position = 0)
    {
        $numberSeparators = \count($this->separators);

        if ($position < $numberSeparators) {
            if (false !== \strpos($string, $this->separators[$position])) {
                $array = \explode($this->separators[$position], $string);

                foreach ($array as $key => $value) {
                    $array[$key] = $this->parseElementsRecursive($value, $position + 1);
                }

                return $array;
            }

//            Equivalente este bloque al de abajo, siendo el último más eficiente ya que
//            consigue optimizar la pila de llamadas
//            for ($indexndex = $position + 1; $indexndex < $numberSeparators; ++$indexndex) {
//                if (false !== \strpos($string, $this->$separators[$indexndex])) {
//                    return [$this->parseElementsRecursive($string, $position + 1)];
//                }
//            }
            $auxArray = [];
            for ($indexndex = $position + 1; $indexndex < $numberSeparators; ++$indexndex) {
                if (false !== \strpos($string, $this->separators[$indexndex])) {
                    $auxArray[] = $this->parseElementsRecursive($string, $indexndex);
                    return $auxArray;
                }
                $auxArray[] = [];
            }
        }

        return ($string == '' ? null : $string);
    }
}
