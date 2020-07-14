<?php

namespace mortalswat\d3connector\Parser;

use Monolog\Formatter\FormatterInterface;

/**
 * Class D3FormatterLog
 * @package mortalswat\d3connector\Parsers
 */
class D3FormatterLog implements FormatterInterface
{
    const TRUNCATE_LENGTH = 100;

    const TITTLE_LOG = 'Tittle';
    const TITTLE_LOG_PREPARACION = 'Preparacion';
    const TITTLE_LOG_LLAMADA = 'Llamada';
    const TITTLE_LOG_RESPUESTA = 'Respuesta';
    const TITTLE_LOG_RESPUESTA_BRUTA = 'Respuesta Bruta';
    const TITTLE_LOG_RESPUESTA_ARRAY = 'Respuesta Array';
    const TITTLE_LOG_ERROR = 'Error';
    const BODY_LOG = 'Body';
    const DATETIME_LOG = 'datetime';
    const XML_LOG = 'xml';
    const SOCKET_LOG = 'socket';

    /**
     * Formats a log record.
     *
     * @param array $record A record to format
     * @return mixed The formatted record
     */
    public function format(array $record)
    {
        $messages = json_decode($record['message'], true);

        $logResult = '';
        if (is_array($messages)) {
            foreach ($messages as $message) {
                $tittle = $message[self::TITTLE_LOG];
                $date = $message[self::DATETIME_LOG];

                // ------ HEADER ------- //
                $header = $tittle . ' D3 ' . " ($date):\n\n";

                // ------ BODY ------- //
                switch ($tittle) {
                    case self::TITTLE_LOG_PREPARACION:
                        $body = $message[self::XML_LOG] . "\n";
                        $body .= $message[self::SOCKET_LOG] . "\n";
                        break;
                    case self::TITTLE_LOG_LLAMADA:
                    case self::TITTLE_LOG_RESPUESTA:
                        $body = $this->parseElementsD3($message[self::BODY_LOG]);
                        break;
                    case self::TITTLE_LOG_RESPUESTA_ARRAY:
                        if (is_array($message[self::BODY_LOG]))
                            array_walk_recursive($message[self::BODY_LOG], function (&$item, $key) {
                                if (strlen($item) > self::TRUNCATE_LENGTH) {
                                    $item = mb_substr($item, 0, self::TRUNCATE_LENGTH) . ' . . .';
                                }
                            });
                        ob_start();
                        $body = var_export($message[self::BODY_LOG], true);
                        break;
                    default:
                        $body = $message[self::BODY_LOG];
                }

                // ------ FOOTER ------- //
                $footer = $this->endRequest();
                $logResult .= (
                    $header .
                    $body .
                    $footer
                );
            }
        }

        return $logResult;
    }

    /**
     * @param $params
     * @return string
     */
    private function parseElementsD3($params)
    {
        $result = '';

        if (is_array($params)) {
            array_walk_recursive($params, function (&$item, $key) {
                if (strlen($item) > self::TRUNCATE_LENGTH) {
                    $item = mb_substr($item, 0, self::TRUNCATE_LENGTH) . ' . . .';
                }
            });

            foreach ($params as $param) {
                if (is_array($param)) {
                    $count = 1;
                    foreach ($param as $keyElement => $element) {
                        $number = (string)$count;
                        $prettyNumber = '<' . str_repeat('0', 3 - strlen($number)) . $number . '> ';
                        if (is_array($element)) {
                            foreach ($element as $key => $val) {
                                if (is_array($val)) {
                                    $element[$key] = implode('\\', $val);
                                }
                            }
                            $param[$keyElement] = implode(' ] ', $element);
                        } else {
                            $param[$keyElement] = $element;
                        }
                        $param[$keyElement] = $prettyNumber . $param[$keyElement];
                        $count++;
                    }
                    $result .= implode("\n", $param);
                } else {
                    $result .= ' - ' . $param;
                }
                $result .= "\n";
            }
        }

        return $result;
    }

    /**
     * @return string
     */
    private function endRequest()
    {
        return ("\n" . str_repeat('-', 10) . "\n");
    }

    /**
     * Formats a set of log records.
     *
     * @param array $records A set of records to format
     * @return mixed The formatted set of records
     */
    public function formatBatch(array $records)
    {
        return '';
    }
}