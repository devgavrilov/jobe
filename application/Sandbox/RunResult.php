<?php defined('BASEPATH') OR exit('No direct script access allowed');

class RunResult {
    public $exitCode = 0;
    public $output = '';
    public $error = '';

    public function isErrorHappened() {
        return $this->exitCode != 0;
    }
}