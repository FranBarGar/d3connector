<?php

namespace mortalswat\d3connector;

use DateInterval;
use DateTime;
use DOMDocument;
use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use mortalswat\d3connector\Interfaces\JD3RequestDataInterface;
use mortalswat\d3connector\Parser\D3FormatterLog;
use mortalswat\d3connector\Parser\D3RequestParser;
use mortalswat\d3connector\Parser\D3ResultParser;
use mortalswat\d3connector\Parser\D3TimerFormatterLog;
use SimpleXMLElement;

/**
 * Class D3Connection
 * @package mortalswat\d3connector
 */
class D3Connection
{
    /** @var */
    private $port;
    /** @var */
    private $server;
    /** @var */
    private $socket;
    /** @var int */
    private $sockopenTimeout;
    /** @var int */
    private $d3routineTimeout;
    /** @var string */
    private $xmlFile;
    /** @var bool */
    private $isConnected;
    /** @var bool */
    private $isXmlLoaded;
    /** @var Logger|null */
    private $logger;
    /** @var Logger|null */
    private $timeLogger;
    /** @var array */
    private $logInfo;
    /** @var array */
    private $timeLogInfo;
    /** @var bool */
    private $extendedLog;

    /**
     * D3Connection constructor.
     * @param $xmlFile
     * @param null $logger
     * @param null $timeLogger
     * @param bool $extendedLog
     * @param int $routineTimeout
     * @param int $sockopenTimeout
     * @throws Exception
     */
    public function __construct($xmlFile, $logger = null, $timeLogger = null, $extendedLog = false, $routineTimeout = 30, $sockopenTimeout = 5)
    {
        $this->setLogger($logger);
        $this->setTimeLogger($timeLogger);

        $this->extendedLog = $extendedLog;

        $this->sockopenTimeout = $sockopenTimeout;
        $this->d3routineTimeout = $routineTimeout;

        $this->xmlFile = $xmlFile;
        $this->isConnected = false;
        $this->isXmlLoaded = false;
    }

    /**
     * Configura el log generico
     * @param $logger
     * @throws Exception
     */
    private function setLogger($logger)
    {
        if ($logger !== null) {
            $this->logger = new Logger('files');
            $infoHandler = new StreamHandler($logger);
            $infoHandler->setFormatter(new D3FormatterLog());
            $this->logger->pushHandler($infoHandler);
            $this->logInfo = [
                /** Preparamos el log de preparación para la llamada */
                [
                    D3FormatterLog::TITTLE_LOG => D3FormatterLog::TITTLE_LOG_PREPARACION,
                ]
            ];
        }
    }

    /**
     * Configura el log basico para mostrar solo los tiempos
     * @param $logger
     * @throws Exception
     */
    private function setTimeLogger($logger)
    {
        if ($logger !== null) {
            $this->timeLogger = new Logger('files');
            $infoHandler = new StreamHandler($logger);
            $infoHandler->setFormatter(new D3TimerFormatterLog());
            $this->timeLogger->pushHandler($infoHandler);
            $this->timeLogInfo = [
                /** Preparamos el log de preparación para la llamada */
                [
                    D3FormatterLog::TITTLE_LOG => D3FormatterLog::TITTLE_LOG_PREPARACION,
                ]
            ];
        }
    }

    /**
     * @param JD3RequestDataInterface $data
     * @return array
     * @throws D3Exception
     */
    public function call(JD3RequestDataInterface $data)
    {
        try {
            if ($data->getRoutineName() === '') {
                throw new D3Exception('D3: Falta el el nombre de la subrutina');
            }

            $arrToD3 = $data->arrayToD3();
            $nbpar = count($arrToD3);

            if (!is_array($arrToD3) || $nbpar < 1) {
                throw new D3Exception('D3: Faltan los parametros de la subrutina');
            }

            $requestD3Array = array_merge(
                [
                    4,
                    $data->getRoutineName(),
                    $nbpar
                ],
                $arrToD3
            );

            $requestParser = new D3RequestParser();
            $request = $requestParser->parseElementsRecursive($requestD3Array);

            if (!$this->isConnected) {
                $this->open();
            }
            $this->logInfo = [$this->logInfo[0]];
            $this->timeLogInfo = [$this->logInfo[0]];

            $datetime = (new DateTime())->format('Y-m-d H:i:s.u');
            $this->logInfo[] = [
                D3FormatterLog::TITTLE_LOG => D3FormatterLog::TITTLE_LOG_LLAMADA,
                D3FormatterLog::BODY_LOG => $requestD3Array,
                D3FormatterLog::DATETIME_LOG => $datetime
            ];
            if ($this->timeLogger !== null) {
                $this->timeLogInfo[] = [
                    D3FormatterLog::TITTLE_LOG => D3FormatterLog::TITTLE_LOG_LLAMADA,
                    D3FormatterLog::BODY_LOG => $data->getRoutineName(),
                    D3FormatterLog::DATETIME_LOG => $datetime
                ];
            }

            $responseD3 = $this->send($request);
            if ($this->extendedLog === true) {
                $this->logInfo[] = [
                    D3FormatterLog::TITTLE_LOG => D3FormatterLog::TITTLE_LOG_RESPUESTA_BRUTA,
                    D3FormatterLog::BODY_LOG => utf8_encode($responseD3),
                    D3FormatterLog::DATETIME_LOG => (new DateTime())->format('Y-m-d H:i:s.u')
                ];
            }

            $resultParser = new D3ResultParser();
            $responseD3Array = $resultParser->parseElementsRecursive($responseD3);

            // Eliminamos el primer elemento del array (0) y el último elemento ''
            array_splice($responseD3Array, 0, 1);
            array_splice($responseD3Array, -1, 1);

            $utf8responseD3Array = self::convertFromLatin1ToUtf8Recursively($responseD3Array);

            $datetime = (new DateTime())->format('Y-m-d H:i:s.u');
            if ($this->extendedLog === true) {
                $this->logInfo[] = [
                    D3FormatterLog::TITTLE_LOG => D3FormatterLog::TITTLE_LOG_RESPUESTA,
                    D3FormatterLog::BODY_LOG => $utf8responseD3Array,
                    D3FormatterLog::DATETIME_LOG => $datetime
                ];
            }
            $this->logInfo[] = [
                D3FormatterLog::TITTLE_LOG => D3FormatterLog::TITTLE_LOG_RESPUESTA_ARRAY,
                D3FormatterLog::BODY_LOG => $utf8responseD3Array,
                D3FormatterLog::DATETIME_LOG => $datetime
            ];
            if ($this->timeLogger !== null) {
                $this->timeLogInfo[] = [
                    D3FormatterLog::TITTLE_LOG => D3FormatterLog::TITTLE_LOG_RESPUESTA,
                    D3FormatterLog::BODY_LOG => $data->getRoutineName(),
                    D3FormatterLog::DATETIME_LOG => $datetime
                ];
            }
            $this->saveLog();

            return $utf8responseD3Array;
        } catch (D3Exception $exception) {
            $datetime = (new DateTime())->format('Y-m-d H:i:s.u');
            $this->logInfo[] = [
                D3FormatterLog::TITTLE_LOG => D3FormatterLog::TITTLE_LOG_ERROR,
                D3FormatterLog::BODY_LOG => '(D3Exception) ' . $exception->getMessage(),
                D3FormatterLog::DATETIME_LOG => $datetime
            ];
            $this->timeLogInfo[] = [
                D3FormatterLog::TITTLE_LOG => D3FormatterLog::TITTLE_LOG_ERROR,
                D3FormatterLog::BODY_LOG => '(D3Exception) ' . $exception->getMessage(),
                D3FormatterLog::DATETIME_LOG => $datetime
            ];
            $this->saveLog();

            throw $exception;
        }
    }

    /**
     * @throws D3Exception
     */
    public function open()
    {
        if ($this->isXmlLoaded === false) {
            $this->logInfo[0][D3FormatterLog::DATETIME_LOG] = (new DateTime())->format('Y-m-d H:i:s.u');
            $this->loadD3Connection($this->xmlFile);
        }

        $socketLog = "SOCKET:";
        $socketStart = (new DateTime())->format('Y-m-d H:i:s.u');
        try {
            $this->socket = fsockopen($this->server, $this->port, $errno, $errstr, $this->sockopenTimeout);
            $socketOpenEnd = (new DateTime())->format('Y-m-d H:i:s.u');
            $socketLog .= "\n\t($socketStart -> $socketOpenEnd) Socket para linea";
        } catch (Exception $exception) {
            $socketOpenEnd = (new DateTime())->format('Y-m-d H:i:s.u');
            $this->logInfo[0][D3FormatterLog::SOCKET_LOG] =
                "($socketStart -> $socketOpenEnd) $socketLog\n\t($socketStart -> $socketOpenEnd) Error socket para linea";
            throw new D3Exception('D3: No se ha podido establecer conexión');
        }

        $newport = fgets($this->socket, 9);

        fclose($this->socket);
        $newLineEnd = (new DateTime())->format('Y-m-d H:i:s.u');

        if (false === $newport) {
            $this->logInfo[0][D3FormatterLog::SOCKET_LOG] =
                "($socketStart -> $newLineEnd) $socketLog\n\t($socketOpenEnd -> $newLineEnd) Ninguna linea disponible";
            throw new D3Exception('D3: No existen lineas disponibles');
        }
        $socketLog .= "\n\t($socketOpenEnd -> $newLineEnd) Obtencion de linea";

        //Ha retornado un puerto libre
        $this->port = $newport;
        try {
            $this->socket = fsockopen($this->server, $newport, $errno, $errstr, $this->sockopenTimeout);
            $newLineSocketEnd = (new DateTime())->format('Y-m-d H:i:s.u');
            $this->logInfo[0][D3FormatterLog::SOCKET_LOG] =
                "($socketStart -> $newLineSocketEnd) $socketLog\n\t($newLineEnd -> $newLineSocketEnd) Linea abierta";
        } catch (Exception $exception) {
            $newLineSocketEnd = (new DateTime())->format('Y-m-d H:i:s.u');
            $this->logInfo[0][D3FormatterLog::SOCKET_LOG] =
                "($socketStart -> $newLineSocketEnd) $socketLog\n\t($newLineEnd -> $newLineSocketEnd) Error socket de linea";
            throw new D3Exception('D3: No se ha podido conectar con la linea');
        }

        $this->isConnected = true;
    }

    /**
     * @param $xmlFile
     * @throws D3Exception
     */
    public function loadD3Connection($xmlFile)
    {
        $xmlStart = (new DateTime())->format('Y-m-d H:i:s.u');
        $this->logInfo[0][D3FormatterLog::DATETIME_LOG] = $xmlStart;

        try {
            $settings = self::loadSettings($xmlFile);
            $this->port = $settings['server'][0]['mainport'];
            $this->server = $settings['server'][0]['host'];
            $this->logInfo[0][D3FormatterLog::XML_LOG] = "($xmlStart -> " . (new DateTime())->format('Y-m-d H:i:s.u') . ') XML';
        } catch (Exception $exception) {
            $this->logInfo[0][D3FormatterLog::XML_LOG] = "($xmlStart -> " . (new DateTime())->format('Y-m-d H:i:s.u') . ') Error XML';
            throw new D3Exception('D3: Error al cargar el xml de conexión' . $exception->getMessage());
        }

        $this->isXmlLoaded = true;
    }

    /**
     * @param $xml
     *
     * @return array
     */
    private static function loadSettings($xml)
    {
        $xmlRequest = new DOMDocument();
        $xmlRequest->load($xml, LIBXML_NOBLANKS);

        $xmlConfig = new SimpleXMLElement($xmlRequest->saveXML());

        return self::toArray($xmlConfig);
    }

    /**
     * @param SimpleXMLElement $xmlObject
     * @return array
     */
    public static function toArray(SimpleXMLElement $xmlObject)
    {
        $subtree = [];

        foreach ($xmlObject as $rootNode) {
            if ($rootNode->count() === 0) {
                $subtree[$rootNode->getName()] = (string)$rootNode;
            } else {
                $subtree[$rootNode->getName()][] = self::toArray($rootNode);
            }
        }

        return $subtree;
    }

    /**
     * @param $request
     * @return string
     * @throws D3Exception
     */
    public function send($request)
    {
        $before = new DateTime();
        stream_set_timeout($this->socket, $this->d3routineTimeout);

        if (!$this->isConnected) {
            throw new D3Exception('D3: No hay conexión');
        }

        if (empty($request)) {
            throw new D3Exception('D3: Faltan los datos a enviar');
        }

        fwrite($this->socket, sprintf('%08d', strlen($request)) . $request);

        $lonrep = fread($this->socket, 8);
        $reply = '';
        $length = $lonrep;

        while ($length > 0) {
            $size = min(8192, $length);
            $data = fread($this->socket, $size);

            if (strlen($data) === false) {
                break; // EOF
            }

            $reply .= $data;
            $length -= strlen($data);
        }
        $after = new DateTime();
        if ($after > $before->add(DateInterval::createFromDateString(
                '+' . $this->d3routineTimeout . ' seconds'
            )))
            throw new D3Exception('D3: La petición ha tardado más de ' . $this->d3routineTimeout . ' segundos en ser procesada');

        return $reply;
    }

    /**
     * Encode array from latin1 to utf8 recursively
     * @param $data
     * @return array|string
     */
    public static function convertFromLatin1ToUtf8Recursively($data)
    {
        if (is_string($data)) {
            return utf8_encode($data);
        }

        if (is_array($data)) {
            $ret = [];
            foreach ($data as $key => $value) $ret[$key] = self::convertFromLatin1ToUtf8Recursively($value);

            return $ret;
        }

        if (is_object($data)) {
            foreach ($data as $key => $value) $data->$key = self::convertFromLatin1ToUtf8Recursively($value);

            return $data;
        }

        return $data;
    }

    /**
     * Save logInfo into a file.
     */
    private function saveLog()
    {
        if ($this->logger !== null) {
            $this->logger->info(json_encode($this->logInfo));
        }
        if ($this->timeLogger !== null) {
            $this->timeLogger->info(json_encode($this->timeLogInfo));
        }
    }
}
