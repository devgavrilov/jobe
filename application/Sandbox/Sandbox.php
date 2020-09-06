<?php defined('BASEPATH') OR exit('No direct script access allowed');

require_once('application/Sandbox/RunOptions.php');
require_once('application/Sandbox/RunResult.php');
require_once 'application/libraries/Exceptions/MissingOrUnavailableException.php';

define('PROJECT_KEY', 'j');
define('ACTIVE_USERS', 1);  // The key for the shared memory active users array
define('MAX_RETRIES', 8);   // Maximum retries (1 secs per retry), waiting for free user account

class Sandbox
{
    public $id;

    public $userName;
    private $userGroup;
    private $workingDirectory;

    private $debug;

    // Find a currently unused jobe user account.
    // Uses a shared memory segment containing one byte (used as a 'busy'
    // boolean) for each of the possible user accounts.
    // If no free accounts exist at present, the function sleeps for a
    // second then retries, up to a maximum of MAX_RETRIES retries.
    // Throws OverloadException if a free user cannot be found, otherwise
    // returns an integer in the range 0 to jobe_max_users - 1 inclusive.
    public function __construct($debug = false)
    {
        $this->id = $this->aquireUser();
        $this->userName = sprintf("jobe%02d", $this->id);
        $this->userGroup = 'jobe';
        $this->workingDirectory = $this->createWorkingDirectory();
        $this->debug = $debug;

        chdir($this->workingDirectory);

        // Give the user RW access.
        exec("setfacl -m u:{$this->userName}:rwX {$this->workingDirectory}");
    }

    private function createWorkingDirectory()
    {
        $workingDirectory = tempnam("/home/jobe/runs", "jobe_");
        if (!unlink($workingDirectory) || !mkdir($workingDirectory)) {
            log_message('error', 'LanguageTask constructor: error making temp directory');
            throw new Exception("Task: error making temp directory (race error?)");
        }
        return $workingDirectory;
    }

    private function aquireUser()
    {
        global $CI;

        $numUsers = $CI->config->item('jobe_max_users');
        $key = ftok(__FILE__, PROJECT_KEY);
        $sem = sem_get($key);
        $user = -1;
        $retries = 0;
        while ($user == -1) {  // Loop until we have a user (or an OverloadException is thrown)
            sem_acquire($sem);
            $shm = shm_attach($key);
            if (!shm_has_var($shm, ACTIVE_USERS)) {
                // First time since boot -- initialise active list
                $active = array();
                for ($i = 0; $i < $numUsers; $i++) {
                    $active[$i] = FALSE;
                }
                shm_put_var($shm, ACTIVE_USERS, $active);
            }
            $active = shm_get_var($shm, ACTIVE_USERS);
            for ($user = 0; $user < $numUsers; $user++) {
                if (!$active[$user]) {
                    $active[$user] = TRUE;
                    shm_put_var($shm, ACTIVE_USERS, $active);
                    break;
                }
            }
            shm_detach($shm);
            sem_release($sem);
            if ($user == $numUsers) {
                $user = -1;
                $retries += 1;
                if ($retries <= MAX_RETRIES) {
                    sleep(1);
                } else {
                    throw new OverloadException();
                }
            }
        }
        return $user;
    }

    public function __destruct()
    {
        if (!$this->debug) {
            $this->cleanWorkingDirectory();
            $this->cleanAllUserFiles();
        }
        $this->killRemainingProcesses();
        $this->freeUser();
    }

    private function cleanWorkingDirectory()
    {
        exec("sudo rm -R {$this->workingDirectory}");
    }

    private function cleanAllUserFiles()
    {
        global $CI;

        $path = $CI->config->item('clean_up_path');
        $dirs = explode(';', $path);
        foreach ($dirs as $dir) {
            exec("sudo /usr/bin/find $dir/ -user {$this->userName} -delete");
        }
    }

    private function killRemainingProcesses()
    {
        exec("sudo /usr/bin/pkill -9 -u {$this->userName}");
    }

    private function freeUser()
    {
        $key = ftok(__FILE__, PROJECT_KEY);
        $sem = sem_get($key);
        sem_acquire($sem);
        $shm = shm_attach($key);
        $active = shm_get_var($shm, ACTIVE_USERS);
        $active[$this->id] = FALSE;
        shm_put_var($shm, ACTIVE_USERS, $active);
        shm_detach($shm);
        sem_release($sem);
    }

    public function loadFile($fileId, $filename)
    {
        $destPath = $this->workingDirectory . '/' . $filename;
        if (!FileCache::file_exists($fileId) ||
            ($contents = FileCache::file_get_contents($fileId)) === FALSE ||
            (file_put_contents($destPath, $contents)) === FALSE) {
            throw new MissingOrUnavailableException();
        }
    }

    public function run(string $command, RunOptions $runOptions): RunResult
    {
        $diskLimit = 1024 * $runOptions->limits->diskLimit;     // MB -> KB
        $streamSize = 1024 * $runOptions->limits->streamSize;   // MB -> KB
        $memoryLimit = 1024 * $runOptions->limits->memoryLimit; // MB -> KB
        $cpuTime = $runOptions->limits->cpuTime;
        $killTime = 2 * $cpuTime;                               // Kill the job after twice the allowed cpu time
        $numProcs = $runOptions->limits->numProcs + 1;          // The + 1 allows for the sh command below.

        $sandboxCommandBits = array(
            "sudo " . dirname(__FILE__) . "/../../runguard/runguard",
            "--user={$this->userName}",
            "--group={$this->userGroup}",
            "--cputime=$cpuTime",      // Seconds of execution time allowed
            "--time=$killTime",        // Wall clock kill time
            "--filesize=$diskLimit",   // Max file sizes
            "--nproc=$numProcs",       // Max num processes/threads for this *user*
            "--no-core",
            "--streamsize=$streamSize");   // Max stdout/stderr sizes

        if ($memoryLimit != 0) {  // Special case: Matlab won't run with a memsize set. TODO: WHY NOT!
            $sandboxCommandBits[] = "--memsize=$memoryLimit";
        }

        $sandboxCmd = implode(' ', $sandboxCommandBits) .
            ' sh -c ' . escapeshellarg($command) . ' >prog.out 2>prog.err';

        // CD into the work directory and run the job
        chdir($this->workingDirectory);

        if ($runOptions->input) {
            file_put_contents('prog.in', $runOptions->input);
            $sandboxCmd .= " <prog.in\n";
        } else {
            $sandboxCmd .= " </dev/null\n";
        }

        file_put_contents('prog.cmd', $sandboxCmd);

        $output = null;
        $exitCode = 0;
        exec('bash prog.cmd', $output, $exitCode);

        $result = new RunResult();
        $result->exitCode = $exitCode;
        $result->output = file_get_contents("prog.out");
        $result->error = file_get_contents("prog.err");

        return $result;
    }
}