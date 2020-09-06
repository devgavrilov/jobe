<?php defined('BASEPATH') OR exit('No direct script access allowed');

require_once 'application/libraries/ExecutionResult.php';

abstract class InterpretedExecutor extends Executor
{
    protected abstract function lint();
    protected abstract function run(): ExecutionResult;

    public function execute(): ExecutionResult
    {
        $this->lint();
        return $this->run();
    }
}