<?php defined('BASEPATH') OR exit('No direct script access allowed');

/* ==============================================================
 *
 * This file defines the abstract Task class, a subclass of which
 * must be defined for each implemented language.
 *
 * ==============================================================
 *
 * @copyright  2014 Richard Lobb, University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('application/libraries/Result.php');
require_once('application/Sandbox/Sandbox.php');

abstract class Executor
{
    protected $task;
    protected $sandbox;

    public function __construct(ExecutionTask $task)
    {
        $this->task = $task;
        $this->sandbox = new Sandbox($this->task->debug);
        $this->loadFiles();
        file_put_contents($this->getSourceFileName(), $task->sourceCode);
    }

    private function loadFiles()
    {
        foreach ($this->task->files as $file) {
            list($fileId, $filename) = $file;
            $this->sandbox->loadFile($fileId, $filename);
        }
    }

    protected function getSourceFileName(): string {
        return "source";
    }

    public abstract function execute(): ExecutionResult;

    public static abstract function getVersion(): VersionProvider;
}
