<?php defined('BASEPATH') OR exit('No direct script access allowed');

interface VersionProvider {
    public function getVersion(): ?string;
}