<?php defined('BASEPATH') OR exit('No direct script access allowed');

class RunLimits {
    public $diskLimit = 20;     // MB
    public $streamSize = 2;     // MB
    public $cpuTime = 5;        // Seconds
    public $memoryLimit = 200;  // MB
    public $numProcs = 20;      // Count
}