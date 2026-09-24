<?php
namespace AgentMedia\T3LocalCopy\Export;

use AgentMedia\T3LocalCopy\DatabaseExtractor\TableExtractor;
use AgentMedia\T3LocalCopy\EventHandling\EventHandler;



/**
 * Summary of ExportCommand
 *
 * This class handles the export command for the T3LocalCopy system.
 * It reads CLI arguments, loads the configuration file, and executes the export process.
 * 
 * 
 */

final class ExportCommand
{
    private $defaultBaseDir;
    public function __construct($defaultBaseDir)
    {
        $this->defaultBaseDir = $defaultBaseDir;
    }
    public function execute()
    {   
        if (Exporter::initCliInterruptHandling()) {
            echo "You can interrupt the export process gracefully using Ctrl+C.\n";
        }
    
        $cliArguments = $this->readCliArguments();
        $verbosity = (int)($cliArguments['verbosity'] ?? InsertAddReporter::VERBOSITY_NONE);

        $rootPageUid = (int)($cliArguments['rootPageUid'] ?? 0);
        $configFile = $cliArguments['configFile'];
        $filesFile = $cliArguments['filesFile'] ?? $this->defaultBaseDir . '/files.txt';
        $insertsFile = $cliArguments['insertsFile'] ?? $this->defaultBaseDir . '/inserts.sql';
        if (!file_exists($configFile)) {
            throw new \InvalidArgumentException("Config file not found: $configFile");
        }
        $config = json_decode(file_get_contents($configFile), true);
        if ($rootPageUid) {
        $config['common']['rootPageUid'] = $rootPageUid;
        }
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to parse config file: ' . json_last_error_msg());
        }
        $exporter = new Exporter($config, null, $verbosity);
        $exporter->execute();

        if (file_put_contents($insertsFile, $exporter->getUpsertsSql()) === false) {
            throw new \RuntimeException("Failed to write to inserts file: $insertsFile");
        }
        if (file_put_contents($filesFile, implode(PHP_EOL, $exporter->getCollectedFiles())) === false) {
            throw new \RuntimeException("Failed to write to files file: $filesFile");
        }
    }

    protected function readCliArguments()
    {
        $options = getopt('', ['rootPageUid:', 'configFile:', 'insertsFile:', 'filesFile:', 'verbosity:']);
        // validate options
        if (!isset($options['configFile'])) {
            throw new \InvalidArgumentException('Missing required CLI arguments. You must provide a configFile');
        }
        if (!isset($options['verbosity'])) {
            $options['verbosity'] = InsertAddReporter::VERBOSITY_NONE;
        }
        return $options;
    }

    public function showHelp($commandName = '')
    {
        if (!$commandName) {
            $commandName = 'php ExportCommand.php';
        }
        echo "Usage: " . $commandName . " --configFile=path/to/config.json [--insertsFile=path/to/inserts.sql] [--filesFile=path/to/files.txt] [--rootPageUid=123]\n";
        echo "  --configFile=path/to/config.json   (String, Required) Path to the JSON configuration file.\n";
        echo "  --insertsFile=path/to/inserts.sql   (String, Optional) Path to the SQL inserts file. By default, it is set to __DIR__/inserts.sql\n";
        echo "  --filesFile=path/to/files.txt   (String, Optional) Path to the text file containing the collection of files. By default, it is set to __DIR__/files.txt\n";
        echo "  --rootPageUid=123                  (Integer, Optional) Root page UID for the export. If not provided, it is taken from the config file, config path: common.rootPageUid\n";
        echo "  --verbosity=LEVEL                  (Integer, Optional) Verbosity level for insert event reporting. Possible values are:\n";
        echo "                                      0: None (default setting)\n";
        echo "                                      1: Low\n";
        echo "                                      2: Medium\n";
        echo "                                      3: High\n";
        echo "                                      4: All\n";
    }
}

