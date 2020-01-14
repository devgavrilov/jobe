<?php defined('BASEPATH') OR exit('No direct script access allowed');

/* ==============================================================
 *
 * TestLib (Based on C++)
 *
 * ==============================================================
 *
 * @copyright  2014 Richard Lobb, University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('application/libraries/LanguageTask.php');

class TestLib_Task extends Task {

    public function __construct($filename, $input, $params) {
        parent::__construct($filename, $input, $params);
        $this->default_params['compileargs'] = array(
            '-Wall',
            '-Werror');
    }

    public static function getVersionCommand() {
        return array('gcc --version', '/gcc \(.*\) ([0-9.]*)/');
    }

    public function compile() {
        $src = basename($this->sourceFileName);
        $this->executableFileName = $execFileName = "$src.exe";
        
        $cacheKey = md5(file_get_contents($this->sourceFileName));
        if (!FileCache::file_exists($cacheKey)) {
            $compileargs = $this->getParam('compileargs');
            $linkargs = $this->getParam('linkargs');
            $cmd = "g++ " . implode(' ', $compileargs) . " -x c++ -o $execFileName $src " . implode(' ', $linkargs);
            list($output, $this->cmpinfo) = parent::run_in_sandbox($cmd);

            FileCache::file_put_contents($cacheKey, file_get_contents($this->executableFileName));
        } else {
            file_put_contents($this->executableFileName, FileCache::file_get_contents($cacheKey));

            exec('sudo chmod +x ' . $this->executableFileName);
            exec('sudo chown ' . $this->user . ':jobe ' . $this->executableFileName);
        }
    }

    // A default name for C++ programs
    public function defaultFileName($sourcecode) {
        return 'check.cpp';
    }

    // The executable is the output from the compilation
    public function getExecutablePath() {
        return "./" . $this->executableFileName;
    }

    public function getTargetFile() {
        return '';
    }

    public function run_in_sandbox($wrappedCmd, $iscompile = true, $stdin = null)
    {
        $workdir = $this->workdir;
        chdir($workdir);

        // Input
        file_put_contents('prog.ans', $stdin);

        touch('prog.in');
        touch('prog.out');

        $output = null;
        $returnVal = 0;
        exec('sh -c ' . escapeshellarg($wrappedCmd . ' prog.in prog.out prog.ans prog.res'), $output, $returnVal);

        return array($returnVal . '|' . file_get_contents('prog.res'), '');
    }
};
