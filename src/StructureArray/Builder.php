<?php

namespace mortalswat\d3connector\StructureArray;

use Exception;

/**
 * Class Builder
 * @package mortalswat\d3connector\StructureArray
 */
class Builder
{
    const UNDEFINED = "\xFE";

    /**
     * @param mixed $data
     * @param Structure $structure
     * @return array
     * @throws StructureArrayException
     */
    public static function buildWithFirstLevel($data, Structure $structure): array
    {
        return [$structure->getName() => self::build($data, $structure)];
    }

    /**
     * @param mixed $data
     * @param Structure $structure
     * @return array
     * @throws StructureArrayException
     */
    public static function build($data, Structure $structure): array
    {
        try {
            if ($structure->isMultiple()) {
                if (!is_array($data)) {
                    return ($data == '') ? [] : [self::recursiveParse($data, $structure)];
                }

                $tmpArray = [];
                foreach ($data as $element) {
                    if ($structure->isObject()) {
                        $recursiveParseValue = self::recursiveParse($element, $structure);
                        if ($recursiveParseValue !== self::UNDEFINED) {
                            $tmpArray[] = $recursiveParseValue;
                        }
                    } else {
                        $tmpArray[] = $element;
                    }
                }

                return $tmpArray;
            }

            return self::recursiveParse($data, $structure);
        } catch (StructureArrayException $exception) {
            throw new StructureArrayException($exception->getMessage());
        } catch (Exception $exception) {
            throw new StructureArrayException('Error al intentar procesar la estructura esperada.');
        }
    }

    /**
     * @param mixed $data
     * @param Structure $structure
     * @return mixed
     * @throws StructureArrayException
     */
    private static function recursiveParse($data, Structure $structure)
    {
        if ($structure->isUndefined()) {
            return self::UNDEFINED;
        }

        if (!is_array($data)) {
            if ($structure->isObject()) {
                // Is possible that the object only have one param
                self::recursiveParse([$data], $structure);
            }

            if ($structure->isNumeric()) {
                if ($data !== '' && !is_numeric($data)) {
                    throw new StructureArrayException('Se esperaba un valor numérico en la propiedad"' . $structure->getName() . '".');
                }

                return $data === '' ? null : (double)$data;
            }

            return $data;
        }

        if (!$structure->isObject()) {
            throw new StructureArrayException('Datos recibidos no se esperaba de tipo array para la propiedad "' . $structure->getName() . '".');
        }

        $numValues = count($data);
        $numProperties = count($structure->getProperties());

        if ($numValues !== $numProperties) {
            throw new StructureArrayException('Número de datos recibidos no se corresponde a estructura esperada para la propiedad "' . $structure->getName() . '".');
        }

        $associativeData = [];

        for ($index = 0; $index < $numProperties; ++$index) {
            $newStructure = $structure->getProperties()[$index];

            if (!$newStructure->isUndefined()) {
                $associativeData[$newStructure->getName()] = self::build(
                    $data[$index],
                    $newStructure
                );
            }
        }

        return $associativeData;
    }
}