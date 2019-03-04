<?php

namespace mortalswat\d3connector\Parser;

use Monolog\Formatter\FormatterInterface;

/**
 * Class D3FormatterLog
 * @package mortalswat\d3connector\Parsers
 */
class D3FormatterLog implements FormatterInterface
{
    const TITTLE_LOG = 'Tittle';
    const TITTLE_LOG_LLAMADA = 'Llamada';
    const TITTLE_LOG_RESPUESTA = 'Respuesta';
    const TITTLE_LOG_RESPUESTA_BRUTA = 'Respuesta Bruta';
    const TITTLE_LOG_RESPUESTA_ARRAY = 'Respuesta Array';
    const BODY_LOG = 'Body';

    /**
     * Formats a log record.
     *
     * @param  array $record A record to format
     * @return mixed The formatted record
     */
    public function format(array $record)
    {
        $message = json_decode( $record['message'], true );

        $tittle = $message[self::TITTLE_LOG];
        $date = $record['datetime']->format( 'y-m-d H:i:s.u' );

        // ------ HEADER ------- //
        $header = $tittle.' D3 '." ($date):\n\n";

        // ------ BODY ------- //
        if( $tittle === self::TITTLE_LOG_LLAMADA){
            $body = $this->parseElementsD3( $message[self::BODY_LOG] );
        } elseif ( $tittle === self::TITTLE_LOG_RESPUESTA){
            $body = $this->parseElementsD3( $message[self::BODY_LOG] );
        } elseif ( $tittle === self::TITTLE_LOG_RESPUESTA_ARRAY){ob_start();
            $body = var_export($message[self::BODY_LOG], true);
        } else {
            $body = $message[self::BODY_LOG];
        }

        // ------ FOOTER ------- //
        $footer = $this->endRequest();

        return (
            $header.
            $body.
            $footer
        );
    }

    /**
     * @param $params
     * @return string
     */
    private function parseElementsD3( $params )
    {
        $result = '';

        if ( is_array( $params ) ){
            foreach ($params as $param) {
                if (is_array($param)) {
                    $count = 1;
                    foreach ($param as $keyElement => $element) {
                        $number = (string)$count;
                        $prettyNumber = '<' . str_repeat('0', 3 - strlen($number)) . $number . '> ';
                        if (is_array($element)) {
                            foreach ($element as $key => $val) {
                                if (is_array($val)) {
                                    $element[$key] = implode( '\\', $val );
                                }
                            }
                            $param[$keyElement] = implode( ' ] ', $element );
                        } else {
                            $param[$keyElement] = $element;
                        }
                        $param[$keyElement] = $prettyNumber.$param[$keyElement];
                        $count++;
                    }
                    $result .= implode( "\n", $param );
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
        return ( "\n".str_repeat('-',10)."\n" );
    }

    /**
     * Formats a set of log records.
     *
     * @param  array $records A set of records to format
     * @return mixed The formatted set of records
     */
    public function formatBatch(array $records)
    {
        return '';
    }
}