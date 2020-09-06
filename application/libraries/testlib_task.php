<?php defined('BASEPATH') OR exit('No direct script access allowed');

require_once('application/libraries/CachedCompiledExecutor.php');
require_once 'application/libraries/ExecutionResult.php';
require_once('application/libraries/Versions/CommandLineRegexpVersionProvider.php');
require_once 'application/libraries/Exceptions/CompilationErrorException.php';

class TestLib_Task extends CachedCompiledExecutor
{
    protected function getSourceFileName(): string
    {
        return 'checker.cpp';
    }

    protected function getCompiledFileName(): string
    {
        return "checker.exe";
    }

    public function compile()
    {
        $result = $this->sandbox->run("g++ -Wall -Werror -x c++ -o " . $this->getCompiledFileName() . " " . $this->getSourceFileName(), new RunOptions());
        if ($result->isErrorHappened()) {
            throw new CompilationErrorException($result->output, $result->error, $result->exitCode);
        }
    }

    public function run(): ExecutionResult
    {
        $data = json_decode($this->task->input);

        file_put_contents('prog.in', $data->input);
        file_put_contents('prog.out', $data->output);
        file_put_contents('prog.ans', $data->answer);

        $result = $this->sandbox->run('./' . $this->getCompiledFileName() . ' prog.in prog.out prog.ans prog.res', new RunOptions());
        return new ExecutionResult(json_encode(array('code' => $result->exitCode, 'message' => file_get_contents('prog.res'))));
    }

    public static function getVersion(): VersionProvider
    {
        return new CommandLineRegexpVersionProvider('gcc --version', '/gcc \(.*\) ([0-9.]*)/');
    }
}
