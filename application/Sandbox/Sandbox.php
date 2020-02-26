<?php defined('BASEPATH') OR exit('No direct script access allowed');

require_once('application/Sandbox/RunOptions.php');
require_once('application/Sandbox/RunResult.php');

class Sandbox {
    public static function run(string $command, RunOptions $runOptions) {
        $diskLimit = 1024 * $runOptions->limits->diskLimit;     // MB -> KB
        $streamSize = 1024 * $runOptions->limits->streamSize;   // MB -> KB
        $memoryLimit = 1024 * $runOptions->limits->memoryLimit; // MB -> KB
        $cpuTime = $runOptions->limits->cpuTime;
        $killTime = 2 * $cpuTime;                               // Kill the job after twice the allowed cpu time
        $numProcs = $runOptions->limits->numProcs + 1;          // The + 1 allows for the sh command below.

        $sandboxCommandBits = array(
                "sudo " . dirname(__FILE__)  . "/../../runguard/runguard",
                "--user={$runOptions->user->name}",
                "--group={$runOptions->user->group}",
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
        chdir($runOptions->user->workingDirectory);

        if ($runOptions->input) {
            file_put_contents('prog.in', $runOptions->input);
            $sandboxCmd .= " <prog.in\n";
        }
        else {
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