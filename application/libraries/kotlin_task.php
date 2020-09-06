<?php defined('BASEPATH') OR exit('No direct script access allowed');

/* ==============================================================
 *
 * Kotlin
 *
 * ==============================================================
 *
 * @copyright  2014 Richard Lobb, University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once 'application/libraries/ExecutionResult.php';
require_once('application/libraries/CachedCompiledExecutor.php');
require_once 'application/libraries/Exceptions/CompilationErrorException.php';

class Kotlin_Task extends CachedCompiledExecutor
{
    protected function getSourceFileName(): string
    {
        return 'prog.kt';
    }

    protected function getCompiledFileName(): string
    {
        return 'prog.jar';
    }

    public function compile()
    {
        $options = new RunOptions();
        $options->limits->cpuTime = 60;
        $options->limits->memoryLimit = 0;

        $result = $this->sandbox->run("/usr/lib/kotlinc/bin/kotlinc {$this->getSourceFileName()} -include-runtime -d " . $this->getCompiledFileName(), $options);
        if ($result->isErrorHappened()) {
            throw new CompilationErrorException($result->output, $result->error, $result->exitCode);
        }
    }

    protected function run(): ExecutionResult
    {
        $options = new RunOptions();
        $options->input = $this->task->input;
        $options->limits->numProcs = 256;
        $options->limits->memoryLimit = 0;

        $result = $this->sandbox->run('/usr/bin/java -jar -Xrs -Xss8m -Xmx200m ' . $this->getCompiledFileName(), $options);
        return new ExecutionResult($result->output);
    }

    public static function getVersion(): VersionProvider
    {
        return new CommandLineRegexpVersionProvider('kotlin -version', '/version ?(([0-9._-]|release)*)/');
    }
}
