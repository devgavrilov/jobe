<?php defined('BASEPATH') OR exit('No direct script access allowed');

require_once('application/libraries/Executor.php');

abstract class CompiledExecutor extends Executor
{
    protected abstract function compile();

    protected abstract function run(): ExecutionResult;

    public function execute(): ExecutionResult
    {
        $this->compile();
        return $this->run();
    }
}