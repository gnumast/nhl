<?php

namespace NHL;

use League\CLImate\CLImate;
use NHL\Contracts\AbstractParser;
use NHL\Exceptions\DownloaderException;
use NHL\Exceptions\ExporterException;
use NHL\Exceptions\ParserException;
use NHL\Factories\ExporterFactory;
use NHL\Factories\ParserFactory;

/**
 * Class Command
 * Represents the command line interface, main entry point of the entire app
 *
 * @package NHL
 */
class Command
{
    const DESCRIPTION = "An NHL.com data file processor";

    const DEFAULT_CONFIG = __DIR__.'/../config.ini';

    /** @var CLImate $climate */
    public $climate;

    /** @var Downloader $downloader */
    public $downloader;

    /** @var Contracts\AbstractExporter $exporter */
    public $exporter;

    /** @var Config $config */
    public $config;

    /** @var AbstractParser $parser */
    public $parser;

    /**
     * Command constructor.
     *
     * @param CLImate $climate
     */
    public function __construct(CLImate $climate)
    {
        $this->climate = $climate;
        $this->createCommandLineArguments();
        $this->climate->description(self::DESCRIPTION);
        $this->climate->arguments->parse();

        if ($this->climate->arguments->defined('help')) {
            $this->climate->usage();
            exit();
        }

        if ($this->climate->arguments->defined('list')) {
            $this->showList();
            exit();
        }

        $config_file = $this->climate->arguments->defined('config') ? $this->climate->arguments->get('config') : self::DEFAULT_CONFIG;
        if (!file_exists($config_file)) {
            die("Couldn't read configuration file. Make sure the path is correct.");
        }

        $this->prepareConfig($config_file);
    }

    /**
     * Run the console command following initialisation / configuration
     */
    public function run()
    {
        try {
            $exporter = $this->config->get('export', 'exporter') ? $this->config->get('export', 'exporter') : 'stdout';
            $this->exporter = ExporterFactory::make($exporter, $this);
            $this->out("Using Exporter: $exporter");

            if ($this->config->get('download', 'download')) {
                $this->out("Starting Downloader");
                $this->downloader = new Downloader($this);
                $this->downloader->download();
            }

            if ($this->config->get('parse', 'parse')) {
                $parsers = $this->config->get('parse', 'filetypes') ? $this->config->get('parse', 'filetypes') : 'PL';
                $parsers = explode(',', $parsers);
                foreach($parsers as $parser) {
                    $this->parser = ParserFactory::make($parser, $this);
                    $this->out("Starting Parser: " . $this->parser->name);
                    $this->parser->parse();
                }
            }
        } catch (ParserException $e) {
            exit("Parser Error: ".$e->getMessage());
        } catch (DownloaderException $e) {
            exit ("Downloader Error: ".$e->getMessage());
        } catch (ExporterException $e) {
            exit ("Exporter Error: ".$e->getMessage());
        }

    }

    /**
     * Defines the command line arguments
     *
     * @throws \Exception
     */
    private function createCommandLineArguments()
    {
        $this->climate->arguments->add([
            'help'          => [
                'prefix'      => 'h',
                'longPrefix'  => 'help',
                'description' => 'Displays the help',
                'noValue'     => true
            ],
            'config'        => [
                'prefix'      => 'c',
                'longPrefix'  => 'config',
                'description' => 'Provide a configuration file instead of using the command line arguments'
            ],
            'parse'    => [
                'prefix'      => 'p',
                'longPrefix'  => 'parse',
                'description' => 'Parse existing files. Must specify data file location',
                'noValue'     => true
            ],
            'download' => [
                'prefix'      => 'd',
                'longPrefix'  => 'download',
                'description' => 'Download the files (either before parsing or just download)',
                'noValue'     => true
            ],
            'files'         => [
                'prefix'      => 'f',
                'longPrefix'  => 'files',
                'description' => 'Directory where data files are/will be stored.'
            ],
            'season'        => [
                'prefix'      => 's',
                'longPrefix'  => 'season',
                'description' => 'Season to download the files for. Use the AAAABBBB format (ie. 20152016)'
            ],
            'verbose'       => [
                'prefix'      => 'v',
                'longPrefix'  => 'verbose',
                'description' => 'Will output different debugging information during the process.',
                'noValue'     => true
            ],
            'quick'         => [
                'prefix'      => 'q',
                'longPrefix'  => 'quick',
                'description' => 'Don\'t throttle during the downloading process (NOT recommended)',
                'noValue'     => true
            ],
            'exporter'      => [
                'prefix'      => 'e',
                'longPrefix'  => 'exporter',
                'description' => 'Specify which data exporter to use. See --list exporters for more info.',
            ],
            'list'          => [
                'longPrefix'  => 'list',
                'description' => 'Lists available implementations for a given type. Available: exporters'
            ]
        ]);
    }

    /**
     * Prints a console message only if verbose is on
     *
     * @param $msg
     */
    public function out($msg)
    {
        if ($this->config->get('general', 'verbose')) {
            $this->climate->out($msg);
        }
    }

    /**
     * Outputs all the available implementation for the given type (such as Exporters)
     * Unfortunately most of these have to be hardcoded because of autoloading (otherwise we'd use reflection)
     */
    private function showList()
    {
        $type = $this->climate->arguments->get('list');
        $data = [];
        if ($type == 'exporters') {
            $data = [
                [
                    'name'        => 'Void',
                    'description' => 'Does not output anything even if parsing is done. Pretty much useless.'
                ],
                [
                    'name'        => '<bold>StdOut</bold>',
                    'description' => '<bold>Prints all the data, formatted in a human-readable format, to the standard output.</bold>'
                ],
                [
                    'name'        => 'File',
                    'description' => 'Writes each game in its own file using the same human-readable format as StdOut'
                ],
                [
                    'name'        => 'CSV',
                    'description' => 'Writes each game to its own file in the comma-separated-values format'
                ],
                [
                    'name'        => 'MySQL',
                    'description' => 'Creates the appropriate tables and inserts the data in a MySQL database'
                ],
                [
                    'name'        => 'MySQL-Dump',
                    'description' => 'Creates a MySQL .sql file with all the required statements to create a database'
                ],
            ];
        }

        if (!empty($data)) {
            $this->climate->table($data);
        }
    }

    /**
     * Set up the configuration by loading the config file and merging with command line options
     *
     * @param $file
     */
    public function prepareConfig($file)
    {
        $this->config = new Config($file);

        $conf = $this->config->getAllFields();

        foreach ($conf as $section => $options) {
            foreach ($options as $option) {
                if ($this->climate->arguments->defined($option)) {
                    $this->config->set($section, $option, $this->climate->arguments->get($option));
                }
            }
        }
    }

}