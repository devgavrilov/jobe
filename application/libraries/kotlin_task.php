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

require_once('application/libraries/LanguageTask.php');

class Kotlin_Task extends Task {
    public function __construct($filename, $input, $params) {
        $params['memorylimit'] = 0;    // Disregard memory limit - let JVM manage memory
        $this->default_params['numprocs'] = 256;     // Java 8 wants lots of processes
        $this->default_params['interpreterargs'] = array(
             "-jar",
             "-Xrs",   //  reduces usage signals by java, because that generates debug
                       //  output when program is terminated on timelimit exceeded.
             "-Xss8m",
             "-Xmx200m"
        );

        if (isset($params['numprocs']) && $params['numprocs'] < 256) {
            $params['numprocs'] = 256;  // Minimum for Java 8 JVM
        }

        $params['cputime'] = 10000;

        parent::__construct($filename, $input, $params);
    }

    public static function getVersionCommand() {
        return array('kotlin -version', '/version ?(([0-9._-]|release)*)/');
    }

    public function compile() {
        $newSourceFileName = str_replace('.kotlin', '.kt', $this->sourceFileName);
        rename($this->sourceFileName, $newSourceFileName);
        $compileArgs = $this->getParam('compileargs');
        $cmd = '/usr/lib/kotlinc/bin/kotlinc ' . implode(' ', $compileArgs) . " {$newSourceFileName} -include-runtime -d prog.jar";
        list($output, $this->cmpinfo) = $this->run_in_sandbox($cmd);
        if (empty($this->cmpinfo)) {
            $this->executableFileName = $this->sourceFileName;
        }
    }

    // A default name for Java programs. [Called only if API-call does
    // not provide a filename. As a side effect, also set the mainClassName.
    public function defaultFileName($sourcecode) {
        return 'prog.kt';
    }

    public function getExecutablePath() {
        return '/usr/bin/java';
    }

    public function getTargetFile() {
        return 'prog.jar';
    }
};

