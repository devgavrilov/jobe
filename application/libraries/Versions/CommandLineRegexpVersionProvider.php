<?php defined('BASEPATH') OR exit('No direct script access allowed');

require_once('VersionProvider.php');

class CommandLineRegexpVersionProvider implements VersionProvider {

    private $command;
    private $versionRegexp;

    public function __construct($command, $versionRegexp)
    {
        $this->command = $command;
        $this->versionRegexp = $versionRegexp;
    }

    public function getVersion(): ?string
    {
        $output = array();
        $retvalue = null;
        exec($this->command . ' 2>&1', $output, $retvalue);
        if ($retvalue != 0 || count($output) == 0) {
            return NULL;
        } else {
            $matches = array();
            $allOutput = implode("\n", $output);
            $isMatch = preg_match($this->versionRegexp, $allOutput, $matches);
            return $isMatch ? $matches[1] : "Unknown";
        }
    }
}