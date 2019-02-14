<?php

namespace mortalswat\d3connector\StructureArray;

/**
 * Class Utils
 * @package mortalswat\d3connector\StructureArray
 */
class Utils
{
    /**
     * @param array $array
     * @param int $initPosition
     * @param int $length
     * @param bool $multiple
     * @return null|array
     */
    public static function restructureInverse (array $array, $initPosition = 0, $length = null, $multiple = false)
    {
        $subArray = array_slice($array, $initPosition, $length);

        if($subArray[0] === null){
            return null;
        }

        $newData = array_map(
            function ($element){return is_array($element) ? $element : [$element];},
            $subArray
        );

        if (!$multiple) return array_map(null, ...$newData)[0];

        return array_map(null, ...$newData);
    }
}