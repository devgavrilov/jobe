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
require_once('application/libraries/CompiledExecutor.php');
require_once('application/libraries/Versions/SimpleVersionProvider.php');

class Android_Task extends CompiledExecutor
{
    public function compile()
    {

    }

    protected function run(): ExecutionResult
    {
        $options = new RunOptions();
        $options->limits->memoryLimit = 0;
        $options->limits->numProcs = 256;
        $options->limits->diskLimit = 1024;
        $options->limits->cpuTime = 5 * 60;

        exec("unzip ./task.zip");
        exec("chmod -R a=rwx .");
        if (file_exists('./sourceCode.path')) {
            file_put_contents(file_get_contents('./sourceCode.path'), $this->task->sourceCode);
        }

        $result = $this->sandbox->run("ANDROID_HOME=/sdk /opt/gradle/gradle-6.3/bin/gradle cAT", $options);
        if ($result->isErrorHappened()) {
            throw new CompilationErrorException($result->output, $result->error, $result->exitCode);
        }

        return new ExecutionResult('OK');
    }

    public static function getVersion(): VersionProvider
    {
        return new SimpleVersionProvider('1.0.0');
    }
}
