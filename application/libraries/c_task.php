<?php defined('BASEPATH') OR exit('No direct script access allowed');

/* ==============================================================
 *
 * C
 *
 * ==============================================================
 *
 * @copyright  2014 Richard Lobb, University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once 'application/Sandbox/RunOptions.php';
require_once 'application/libraries/ExecutionResult.php';
require_once('application/libraries/CachedCompiledExecutor.php');
require_once('application/libraries/Versions/CommandLineRegexpVersionProvider.php');

class C_Task extends CachedCompiledExecutor
{
    protected function getSourceFileName(): string
    {
        return 'prog.c';
    }

    protected function getCompiledFileName(): string
    {
        return "program.exe";
    }

    public function compile()
    {
        $result = $this->sandbox->run("gcc -Wall -Werror -std=c99 -x c -o " . $this->getCompiledFileName() . " " . $this->getSourceFileName(), new RunOptions());
        if ($result->isErrorHappened()) {
            throw new CompilationErrorException($result->output, $result->error, $result->exitCode);
        }
    }

    protected function run(): ExecutionResult
    {
        $options = new RunOptions();
        $options->input = $this->task->input;

        $result = $this->sandbox->run("./" . $this->getCompiledFileName(), $options);
        return new ExecutionResult($result->output);
    }

    public static function getVersion(): VersionProvider
    {
        return new CommandLineRegexpVersionProvider('gcc --version', '/gcc \(.*\) ([0-9.]*)/');
    }
}
