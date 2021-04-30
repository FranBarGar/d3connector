<?php

namespace mortalswat\d3connector\Parser;

use Monolog\Formatter\FormatterInterface;

/**
 * Class D3FormatterLog
 * @package mortalswat\d3connector\Parsers
 */
class D3FormatterLog implements FormatterInterface
{
    const LOG_MODE_CONFIG = [
        0 => [self::TITTLE_LOG_LLAMADA, self::TITTLE_LOG_RESPUESTA_ARRAY, self::TITTLE_LOG_ERROR,],
        1 => [self::TITTLE_LOG_PREPARACION, self::TITTLE_LOG_LLAMADA, self::TITTLE_LOG_RESPUESTA_ARRAY, self::TITTLE_LOG_ERROR,],
        2 => [self::TITTLE_LOG_LLAMADA, self::TITTLE_LOG_RESPUESTA_BRUTA, self::TITTLE_LOG_RESPUESTA_ARRAY, self::TITTLE_LOG_ERROR,],
    ];

    const TITTLE_LOG = 'Tittle';
    /** @var string Titulo para seguimiento de la preparación de la conexión */
    const TITTLE_LOG_PREPARACION = 'Preparacion';
    /** @var string Titulo para la llamada con indices D3 */
    const TITTLE_LOG_LLAMADA = 'Llamada';
    /** @var string Titulo para la respuesta con indices D3 */
    const TITTLE_LOG_RESPUESTA = 'Respuesta';
    /** @var string Titulo para cadena de caracteres sin formatear */
    const TITTLE_LOG_RESPUESTA_BRUTA = 'Respuesta Bruta';
    /** @var string Titulo para array PHP */
    const TITTLE_LOG_RESPUESTA_ARRAY = 'Respuesta Array';
    /** @var string Titulo para excepción lanzada */
    const TITTLE_LOG_ERROR = 'Error';
    const BODY_LOG = 'Body';
    const DATETIME_LOG = 'datetime';
    const XML_LOG = 'xml';
    const SOCKET_LOG = 'socket';

    private $logMode;
    private $logLength;
    private $fieldLength;

    /**
     * D3FormatterLog constructor.
     * @param int|null $logMode
     * @param int|null $logLength
     * @param int|null $fieldLength
     * @throws \Exception
     */
    public function __construct(?int $logMode = 0, ?int $logLength = 250, ?int $fieldLength = null)
    {
        if (!isset(self::LOG_MODE_CONFIG[$logMode])) {
            throw new \LogicException('El modo de logueo no es valido.');
        }

        $this->logLength = $logLength;
        $this->logMode = $logMode;
        $this->fieldLength = $fieldLength;
    }

    /**
     * Formats a log record.
     *
     * @param array $record A record to format
     * @return string The formatted record
     */
    public function format(array $record): string
    {
        $messages = json_decode($record['message'], true);

        $logResult = '';
        if (!is_array($messages)) {
            return $logResult;
        }

        foreach ($messages as $message) {
            $logResult .= $this->formatMessage($message);
        }

        return $logResult;
    }

    /**
     * @param $message
     * @return string
     */
    private function formatMessage($message): string
    {
        $tittle = $message[self::TITTLE_LOG];

        if (!in_array($tittle, self::LOG_MODE_CONFIG[$this->logMode])) {
            return '';
        }

        $date = $message[self::DATETIME_LOG];

        // ------ HEADER ------- //
        $header = $tittle . ' D3 ' . " ($date):\n\n";

        // ------ BODY ------- //
        switch ($tittle) {
            case self::TITTLE_LOG_PREPARACION:
                $body = $message[self::XML_LOG] . "\n";
                $body .= isset($message[self::SOCKET_LOG]) ? $message[self::SOCKET_LOG] . "\n" : '';
                break;
            case self::TITTLE_LOG_LLAMADA:
            case self::TITTLE_LOG_RESPUESTA:
                $body = $this->parseElementsD3($message[self::BODY_LOG]);
                break;
            case self::TITTLE_LOG_RESPUESTA_ARRAY:
                if (is_array($message[self::BODY_LOG]) && $this->fieldLength !== null) {
                    array_walk_recursive($message[self::BODY_LOG], function (&$item, $key) {
                        if (strlen($item) > $this->fieldLength) {
                            $item = substr($item, 0, $this->fieldLength) . ' . . .';
                        }
                    });
                }
                ob_start();
                $body = var_export($message[self::BODY_LOG], true);
                break;
            default:
                $body = $message[self::BODY_LOG];
        }

        $logResult = ($header . $body);
        if ($this->logLength !== null && strlen($logResult) > $this->logLength) {
            $logResult = substr($logResult, 0, $this->logLength) . " . . .\n";
        }

        // ------ FOOTER ------- //
        return $logResult . $this->endRequest();
    }

    /**
     * @param $params
     * @return string
     */
    private function parseElementsD3($params): string
    {
        $result = '';

        if (!is_array($params)) {
            return $result;
        }

        if ($this->fieldLength !== null) {
            array_walk_recursive($params, function (&$item, $key) {
                if (strlen($item) > $this->fieldLength) {
                    $item = substr($item, 0, $this->fieldLength) . ' . . .';
                }
            });
        }

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

        return $result;
    }

    /**
     * @return string
     */
    private function endRequest(): string
    {
        return ("\n" . str_repeat('-', 10) . "\n");
    }

    /**
     * Formats a set of log records.
     *
     * @param array $records A set of records to format
     * @return string The formatted set of records
     */
    public function formatBatch(array $records): string
    {
        return '';
    }
}