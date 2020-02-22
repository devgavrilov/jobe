<?php defined('BASEPATH') OR exit('No direct script access allowed');

require_once('application/Sandbox/RunLimits.php');

class RunOptions {
    public $limits;
    public $user;
    public $group;
    public $workingDirectory;
    public $input;

    public function __constructor() {
        $this->limits = new RunLimits();
    }
}