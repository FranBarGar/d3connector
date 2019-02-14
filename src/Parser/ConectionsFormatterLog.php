<?php

namespace mortalswat\d3connector\Parser;

use Monolog\Formatter\FormatterInterface;

/**
 * Class ConectionsFormatterLog
 * @package mortalswat\d3connector\Parser
 */
class ConectionsFormatterLog implements FormatterInterface
{
    /**
     * Formats a log record.
     *
     * @param array $record A record to format
     *
     * @return mixed The formatted record
     */
    public function format(array $record)
    {
        $message = \json_decode($record['message'], true);

        return
            '<<<<<<<<<< Request ('.$message['request']['time'].") >>>>>>>>>>\n".
            'URL: '.$message['request']['uri']."\n".
            "Headers:\n".$message['request']['headers']."\n".
            "Body:\n".$message['request']['content']."\n\n".
            '<<<<<<<<<< Response ('.$message['response']['time'].") >>>>>>>>>>\n".
            'Code: '.$message['response']['code']."\n".
            "Content:\n".$message['response']['content']."\n\n".
            $this->requestSeparator()
        ;
    }

    /**
     * @return string
     */
    private function requestSeparator()
    {
        return  "\n".\str_repeat('-', 10)."\n";
    }

    /**
     * Formats a set of log records.
     *
     * @param array $records A set of records to format
     *
     * @return mixed The formatted set of records
     */
    public function formatBatch(array $records)
    {
        return '';
    }
}
