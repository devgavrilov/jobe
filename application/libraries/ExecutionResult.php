<?php defined('BASEPATH') OR exit('No direct script access allowed');

class ExecutionResult {
    public $output;

    public function __construct($output)
    {
        $this->output = $output;
    }
}