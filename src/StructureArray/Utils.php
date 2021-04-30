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
     * @param int|null $length
     * @param bool $multiple
     * @return array|null
     */
    public static function restructureInverse(
        array $array, int $initPosition = 0, ?int $length = null, bool $multiple = false
    ): ?array
    {
        $subArray = array_slice($array, $initPosition, $length);

        if (self::getProperty($subArray, 0) === null) {
            return null;
        }

        $newData = array_map(
            function ($element) {
                return is_array($element) ? $element : [$element];
            },
            $subArray
        );

        return (!$multiple) ?
            self::getProperty(array_map(null, ...$newData), 0) : array_map(null, ...$newData);
    }

    /**
     * @param array $haystack
     * @param string $needle
     * @param mixed|null $default
     * @return mixed|null
     */
    public static function getProperty(array $haystack, string $needle, $default = null)
    {
        return (is_array($haystack) && isset($haystack[$needle])) ? $haystack[$needle] : $default;
    }

    /**
     * @return string
     */
    public static function getDate(): string
    {
        return (new \DateTime())->format('Y-m-d H:i:s.u');
    }
}