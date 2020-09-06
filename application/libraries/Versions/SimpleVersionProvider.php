<?php defined('BASEPATH') OR exit('No direct script access allowed');

require_once('VersionProvider.php');

class SimpleVersionProvider implements VersionProvider {
    private $version;

    public function __construct($version)
    {
        $this->version = $version;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }
}