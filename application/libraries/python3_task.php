<?php defined('BASEPATH') OR exit('No direct script access allowed');

/* ==============================================================
 *
 * Python3
 *
 * ==============================================================
 *
 * @copyright  2014 Richard Lobb, University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once 'application/Sandbox/RunOptions.php';
require_once 'application/libraries/InterpretedExecutor.php';
require_once 'application/libraries/ExecutionResult.php';
require_once 'application/libraries/Exceptions/LinterErrorException.php';
require_once 'application/libraries/Versions/CommandLineRegexpVersionProvider.php';

class Python3_Task extends InterpretedExecutor
{
    protected function getSourceFileName(): string
    {
        return 'prog.py';
    }

    protected function lint()
    {
        $result = $this->sandbox->run("python3 -m py_compile {$this->getSourceFileName()}", new RunOptions());
        if ($result->isErrorHappened()) {
            throw new LinterErrorException();
        }
    }

    protected function run(): ExecutionResult
    {
        $options = new RunOptions();
        $options->input = $this->task->input;
        $options->limits->memoryLimit = 400;

        $result = $this->sandbox->run('/usr/bin/python3 -BE ' . $this->getSourceFileName(), $options);
        return new ExecutionResult($result->output);
    }

    public static function getVersion(): VersionProvider
    {
        return new CommandLineRegexpVersionProvider('python3 --version', '/Python ([0-9._]*)/');
    }
}
