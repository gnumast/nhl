<?php

namespace NHL;

use NHL\Contracts\Exporter;
use NHL\Exporters\CSV;
use NHL\Exporters\File;
use NHL\Exporters\MySQL;
use NHL\Exporters\StdOut;
use NHL\Exporters\Void;

/**
 * Class ExporterFactory
 *
 * @package NHL
 */
class ExporterFactory
{
    /**
     * @param string  $exporter
     * @param Command $command
     *
     * @return Exporter
     */
    public static function make($exporter, Command $command)
    {
        switch ($exporter) {
            case 'csv':
                $obj = new CSV();
                break;
            case 'file':
                $obj = new File();
                break;
            case 'mysql':
                $obj = new MySQL();
                break;
            case 'void':
                $obj = new Void();
                break;
            case 'stdout':
            default:
                $obj = new StdOut();
        }

        if (method_exists($obj, 'setCommand')) {
            $obj->setCommand($command);
        }

        return $obj;
    }
}