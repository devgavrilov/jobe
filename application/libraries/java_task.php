<?php defined('BASEPATH') OR exit('No direct script access allowed');

/* ==============================================================
 *
 * Java
 *
 * ==============================================================
 *
 * @copyright  2014 Richard Lobb, University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('application/libraries/CachedCompiledExecutor.php');
require_once('application/libraries/Versions/CommandLineRegexpVersionProvider.php');

class Java_Task extends CachedCompiledExecutor
{
    protected function getSourceFileName(): string
    {
        return "{$this->getMainClass()}.java";
    }

    protected function getCompiledFileName(): string
    {
        return "{$this->getMainClass()}.jar";
    }

    public function compile()
    {
        $options = new RunOptions();
        $options->limits->numProcs = 256;
        $options->limits->memoryLimit = 0;

        return $this->sandbox->run("/usr/bin/javac {$this->getSourceFileName()}", $options);
    }

    // Return the name of the main class, or FALSE if no
    // such class found. Uses a regular expression to find a public class with
    // a public static void main method.
    // Not totally safe as it doesn't parse the file, e.g. would be fooled
    // by a commented-out main class with a different name.
    private function getMainClass()
    {
        $pattern = '/(^|\W)public\s+class\s+(\w+)[^{]*\{.*?public\s+static\s+void\s+main\s*\(\s*String/ms';
        if (preg_match_all($pattern, $this->task->sourceCode, $matches) !== 1) {
            return FALSE;
        } else {
            return $matches[2][0];
        }
    }

    // Get rid of the tab characters at the start of indented lines in
    // traceback output.
    public function filteredStderr()
    {
        return str_replace("\n\t", "\n        ", $this->stderr);
    }

    protected function run(): ExecutionResult
    {
        $options = new RunOptions();
        $options->limits->memoryLimit = 0;
        $options->limits->numProcs = 256;
        array(
            "-Xrs",   //  reduces usage signals by java, because that generates debug
            //  output when program is terminated on timelimit exceeded.
            "-Xss8m",
            "-Xmx200m"
        );
    }

    public static function getVersion(): VersionProvider
    {
        return new CommandLineRegexpVersionProvider('java -version', '/version "?([0-9._]*)/');
    }
}

