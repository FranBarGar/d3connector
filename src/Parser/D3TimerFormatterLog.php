<?php

namespace mortalswat\d3connector\Parser;

use Monolog\Formatter\FormatterInterface;

/**
 * Class D3TimerFormatterLog
 * @package mortalswat\d3connector\Parser
 */
class D3TimerFormatterLog implements FormatterInterface
{
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
                $tittle = $message[D3FormatterLog::TITTLE_LOG];
                $date = $message[D3FormatterLog::DATETIME_LOG];
                $routineName = $message[D3FormatterLog::BODY_LOG];

                // ------ HEADER ------- //
                $header = "$tittle D3 ($date): $routineName\n";

                // ------ BODY ------- //
                switch ($tittle) {
                    case D3FormatterLog::TITTLE_LOG_PREPARACION:
                        $header = "$tittle D3 ($date):\n";
                        $body = $message[D3FormatterLog::XML_LOG] . "\n";
                        $body .= $message[D3FormatterLog::SOCKET_LOG] . "\n";
                        break;
                    case D3FormatterLog::TITTLE_LOG_LLAMADA:
                    case D3FormatterLog::TITTLE_LOG_RESPUESTA:
                        $body = "";
                        break;
                    default:
                        $body = $message[D3FormatterLog::BODY_LOG];
                }

                $logResult .= (
                    $header .
                    $body
                );
            }

            // ------ FOOTER ------- //
            $footer = $this->endRequest();
            $logResult .= $footer;
        }

        return $logResult;
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