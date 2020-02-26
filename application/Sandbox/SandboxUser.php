<?php defined('BASEPATH') OR exit('No direct script access allowed');

define('PROJECT_KEY', 'j');

class SandboxUser {
    public $id;
    public $name;
    public $group;
    public $workingDirectory;

    // Find a currently unused jobe user account.
    // Uses a shared memory segment containing one byte (used as a 'busy'
    // boolean) for each of the possible user accounts.
    // If no free accounts exist at present, the function sleeps for a
    // second then retries, up to a maximum of MAX_RETRIES retries.
    // Throws OverloadException if a free user cannot be found, otherwise
    // returns an integer in the range 0 to jobe_max_users - 1 inclusive.
    public function __construct() {
        $this->id = $this->aquireUser();
        $this->name = sprintf("jobe%02d", $this->id);
        $this->group = 'jobe';
        $this->workingDirectory = $this->createWorkingDirectory();
    }

    private function createWorkingDirectory() {
        // Create the temporary directory that will be used.
        $workingDirectory = tempnam("/home/jobe/runs", "jobe_");
        if (!unlink($workingDirectory) || !mkdir($workingDirectory)) {
            log_message('error', 'LanguageTask constructor: error making temp directory');
            throw new Exception("Task: error making temp directory (race error?)");
        }
        return $workingDirectory;
    }

    private function aquireUser() {
        global $CI;

        $numUsers = $CI->config->item('jobe_max_users');
        $key = ftok(__FILE__,  PROJECT_KEY);
        $sem = sem_get($key);
        $user = -1;
        $retries = 0;
        while ($user == -1) {  // Loop until we have a user (or an OverloadException is thrown)
            sem_acquire($sem);
            $shm = shm_attach($key);
            if (!shm_has_var($shm, ACTIVE_USERS)) {
                // First time since boot -- initialise active list
                $active = array();
                for($i = 0; $i < $numUsers; $i++) {
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
        $this->cleanWorkingDirectory();
        $this->freeUser();
    }

    private function cleanWorkingDirectory() {
        exec("sudo rm -R {$this->workingDirectory}");
    }

    private function freeUser() {
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
}