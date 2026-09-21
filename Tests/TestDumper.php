<?php
final class TestDumper {
    private $targetFolder;

    const FORMAT_JSON = 'json';

    const FORMAT_VAR_DUMP = 'var_dump';
    public  function __construct($targetFolder) {
        $this->targetFolder = rtrim($targetFolder, '/');
    }

    public function dump($data, $file, $format = self::FORMAT_JSON) {
        if (!is_dir($this->targetFolder)) {
            if (mkdir($this->targetFolder, 0777, true) === false) {
                throw new \RuntimeException('Failed to create target folder: ' . $this->targetFolder);
            }
        }
        if (!is_writable($this->targetFolder)) {
            throw new \RuntimeException('Target folder is not writable: ' . $this->targetFolder);
        }
        $fileData = '';
        switch ($format) {
            case self::FORMAT_JSON:
                $fileData = json_encode($data, JSON_PRETTY_PRINT);
                break;
            case self::FORMAT_VAR_DUMP:
                ob_start();
                var_dump($data);
                $fileData = ob_get_clean();
                break;
            default:
                throw new \InvalidArgumentException('Unsupported format: ' . $format);
        }
        file_put_contents($this->targetFolder . '/' . $file, $fileData);
    }
}