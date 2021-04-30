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
use mortalswat\d3connector\StructureArray\Utils;
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
    private $sockopenMainTimeout;

    /** @var int */
    private $sockopenChildTimeout;

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

    /**
     * D3Connection constructor.
     * @param string $xmlFile
     * @param string|null $logger
     * @param string|null $timeLogger
     * @param int|null $logMode
     * @param int|null $logTruncate
     * @param int|null $logFieldTruncate
     * @throws \Exception
     */
    public function __construct(
        string $xmlFile,
        ?string $logger = null,
        ?string $timeLogger = null,
        ?int $logMode = 0,
        ?int $logTruncate = 250,
        ?int $logFieldTruncate = null
    )
    {
        $this->setLogger($logger, $logMode, $logTruncate, $logFieldTruncate);
        $this->setTimeLogger($timeLogger);

        $this->xmlFile = $xmlFile;
        $this->isConnected = false;
        $this->isXmlLoaded = false;
    }

    /**
     * Configura el log generico
     * @param string|null $logger
     * @param int|null $logMode
     * @param int|null $logTruncate
     * @param int|null $logFieldTruncate
     * @throws \Exception
     */
    private function setLogger(?string $logger, ?int $logMode, ?int $logTruncate, ?int $logFieldTruncate): void
    {
        if ($logger !== null) {
            $this->logger = new Logger('files');
            $infoHandler = new StreamHandler($logger);
            $infoHandler->setFormatter(new D3FormatterLog($logMode, $logTruncate, $logFieldTruncate));
            $this->logger->pushHandler($infoHandler);
        }

        $this->logInfo = [
            /** Preparamos el log de preparación para la llamada */
            [
                D3FormatterLog::TITTLE_LOG => D3FormatterLog::TITTLE_LOG_PREPARACION,
            ]
        ];
    }

    /**
     * Configura el log basico para mostrar solo los tiempos
     * @param string|null $logger
     */
    private function setTimeLogger(?string $logger): void
    {
        if ($logger !== null) {
            $this->timeLogger = new Logger('files');
            $infoHandler = new StreamHandler($logger);
            $infoHandler->setFormatter(new D3TimerFormatterLog());
            $this->timeLogger->pushHandler($infoHandler);
        }

        $this->timeLogInfo = [
            /** Preparamos el log de preparación para la llamada */
            [
                D3FormatterLog::TITTLE_LOG => D3FormatterLog::TITTLE_LOG_PREPARACION,
            ]
        ];
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
            $this->setLogDataAfterOpenConnection($requestD3Array, $data);

            $responseD3 = $this->send($request);

            $this->setLogDataAfterSend($responseD3);

            $resultParser = new D3ResultParser();
            $responseD3Array = $resultParser->parseElementsRecursive($responseD3);

            if ($responseD3Array !== null) {
                // Eliminamos el primer elemento del array (0) y el último elemento ''
                array_splice($responseD3Array, 0, 1);
                array_splice($responseD3Array, -1, 1);
            }

            $utf8responseD3Array = self::convertFromLatin1ToUtf8Recursively($responseD3Array);

            $this->setLogDataAfterParse($utf8responseD3Array, $data);
            $this->saveLog();

            return $utf8responseD3Array;
        } catch (D3Exception $exception) {
            $this->setLogDataAfterException($exception);
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
            $this->logInfo[0][D3FormatterLog::DATETIME_LOG] = Utils::getDate();
            $this->loadD3Connection($this->xmlFile);
        }

        $socketLog = "SOCKET:";
        $socketStart = Utils::getDate();
        try {
            $this->socket = fsockopen($this->server, $this->port, $errno, $errstr, $this->sockopenMainTimeout);
            $socketOpenEnd = Utils::getDate();
            $socketLog .= "\n\t($socketStart -> $socketOpenEnd) Socket para linea";
        } catch (Exception $exception) {
            $socketOpenEnd = Utils::getDate();
            $this->logInfo[0][D3FormatterLog::SOCKET_LOG] =
                "($socketStart -> $socketOpenEnd) $socketLog\n\t($socketStart -> $socketOpenEnd) Error socket para linea";
            throw new D3Exception('D3: No se ha podido establecer conexión');
        }

        $newport = fgets($this->socket, 9);

        fclose($this->socket);
        $newLineEnd = Utils::getDate();

        if (false === $newport) {
            $this->logInfo[0][D3FormatterLog::SOCKET_LOG] =
                "($socketStart -> $newLineEnd) $socketLog\n\t($socketOpenEnd -> $newLineEnd) Ninguna linea disponible";
            throw new D3Exception('D3: No existen lineas disponibles');
        }
        $socketLog .= "\n\t($socketOpenEnd -> $newLineEnd) Obtencion de linea";

        //Ha retornado un puerto libre
        $this->port = $newport;
        try {
            $this->socket = fsockopen($this->server, $newport, $errno, $errstr, $this->sockopenChildTimeout);
            $newLineSocketEnd = Utils::getDate();
            $this->logInfo[0][D3FormatterLog::SOCKET_LOG] =
                "($socketStart -> $newLineSocketEnd) $socketLog\n\t($newLineEnd -> $newLineSocketEnd) Linea abierta";
        } catch (Exception $exception) {
            $newLineSocketEnd = Utils::getDate();
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
        $xmlStart = Utils::getDate();
        $this->logInfo[0][D3FormatterLog::DATETIME_LOG] = $xmlStart;

        try {
            $settings = self::loadSettings($xmlFile);

            if (Utils::getProperty($settings, 'active', '0') === '0') {
                throw new D3Exception('El pool de conexiones está deshabilitado');
            }

            $serverSettings = Utils::getProperty($settings, 'server');
            if (is_array($serverSettings)) {
                $serverSettings = $serverSettings[0];
            }

            if (!(
                $serverSettings !== null &&
                isset($serverSettings['mainport']) &&
                isset($serverSettings['host']) &&
                isset($serverSettings['timeout'])
            )) {
                throw new D3Exception('El pool de conexiones no está correctamente configurado');
            }

            $serverTimeout = $serverSettings['timeout'];
            $this->sockopenMainTimeout = Utils::getProperty($serverTimeout, 'main', 5);
            $this->sockopenChildTimeout = Utils::getProperty($serverTimeout, 'child', 5);
            $this->d3routineTimeout = Utils::getProperty($serverTimeout, 'io', 60);

            $this->port = $serverSettings['mainport'];
            $this->server = $serverSettings['host'];

            $this->logInfo[0][D3FormatterLog::XML_LOG] = '($xmlStart -> ' . Utils::getDate() . ') XML';
        } catch (Exception $exception) {
            $this->logInfo[0][D3FormatterLog::XML_LOG] = '($xmlStart -> ' . Utils::getDate() . ') Error XML';
            throw new D3Exception('D3: Error al cargar el xml de conexión (' . $exception->getMessage() . ')');
        }

        $this->isXmlLoaded = true;
    }

    /**
     * @param $xml
     *
     * @return array
     * @throws \Exception
     */
    private static function loadSettings($xml): array
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
    public static function toArray(SimpleXMLElement $xmlObject): array
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
     * @param string $request
     * @return string
     * @throws D3Exception
     */
    public function send(string $request): string
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
        if (
            $after > $before->add(DateInterval::createFromDateString("+{$this->d3routineTimeout} seconds"))
        ) {
            throw new D3Exception("D3: La petición ha tardado más de {$this->d3routineTimeout} segundos en ser procesada");
        }

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
            $data = utf8_encode($data);
        }

        if (is_array($data)) {
            $ret = [];
            foreach ($data as $key => $value) {
                $ret[$key] = self::convertFromLatin1ToUtf8Recursively($value);
            }

            return $ret;
        }

        if (is_object($data)) {
            foreach ($data as $key => $value) {
                $data->$key = self::convertFromLatin1ToUtf8Recursively($value);
            }
        }

        return $data;
    }

    /**
     * @param array $requestD3Array
     * @param JD3RequestDataInterface $data
     */
    private function setLogDataAfterOpenConnection(array $requestD3Array, JD3RequestDataInterface $data): void
    {
        $this->logInfo = [$this->logInfo[0]];
        $this->timeLogInfo = [$this->logInfo[0]];

        $datetime = Utils::getDate();
        $this->logInfo[] = [
            D3FormatterLog::TITTLE_LOG => D3FormatterLog::TITTLE_LOG_LLAMADA,
            D3FormatterLog::BODY_LOG => $requestD3Array,
            D3FormatterLog::DATETIME_LOG => $datetime
        ];
        $this->timeLogInfo[] = [
            D3FormatterLog::TITTLE_LOG => D3FormatterLog::TITTLE_LOG_LLAMADA,
            D3FormatterLog::BODY_LOG => $data->getRoutineName(),
            D3FormatterLog::DATETIME_LOG => $datetime
        ];
    }

    /**
     * @param string $responseD3
     */
    private function setLogDataAfterSend(string $responseD3): void
    {
        $this->logInfo[] = [
            D3FormatterLog::TITTLE_LOG => D3FormatterLog::TITTLE_LOG_RESPUESTA_BRUTA,
            D3FormatterLog::BODY_LOG => utf8_encode($responseD3),
            D3FormatterLog::DATETIME_LOG => Utils::getDate()
        ];
    }

    /**
     * @param array $utf8responseD3Array
     * @param JD3RequestDataInterface $data
     */
    private function setLogDataAfterParse(array $utf8responseD3Array, JD3RequestDataInterface $data): void
    {
        $datetime = Utils::getDate();
        $this->logInfo[] = [
            D3FormatterLog::TITTLE_LOG => D3FormatterLog::TITTLE_LOG_RESPUESTA,
            D3FormatterLog::BODY_LOG => $utf8responseD3Array,
            D3FormatterLog::DATETIME_LOG => $datetime
        ];
        $this->logInfo[] = [
            D3FormatterLog::TITTLE_LOG => D3FormatterLog::TITTLE_LOG_RESPUESTA_ARRAY,
            D3FormatterLog::BODY_LOG => $utf8responseD3Array,
            D3FormatterLog::DATETIME_LOG => $datetime
        ];
        $this->timeLogInfo[] = [
            D3FormatterLog::TITTLE_LOG => D3FormatterLog::TITTLE_LOG_RESPUESTA,
            D3FormatterLog::BODY_LOG => $data->getRoutineName(),
            D3FormatterLog::DATETIME_LOG => $datetime
        ];
    }


    /**
     * @param D3Exception $exception
     */
    private function setLogDataAfterException(D3Exception $exception): void
    {
        $datetime = Utils::getDate();
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
    }

    /**
     * Save logInfo into a file.
     */
    private function saveLog(): void
    {
        if ($this->logger !== null) {
            $this->logger->info(json_encode($this->logInfo));
        }
        if ($this->timeLogger !== null) {
            $this->timeLogger->info(json_encode($this->timeLogInfo));
        }
    }
}
