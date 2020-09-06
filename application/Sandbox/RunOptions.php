<?php defined('BASEPATH') OR exit('No direct script access allowed');

require_once('application/Sandbox/RunLimits.php');

class RunOptions {
    public $limits;
    public $input = null;

    public function __construct()
    {
        $this->limits = new RunLimits();
    }
}