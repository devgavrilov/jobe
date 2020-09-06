<?php defined('BASEPATH') OR exit('No direct script access allowed');

class CompilationErrorException extends Exception
{
    private $output;
    private $error;
    private $exitCode;

    public function __construct(string $output, string $error, int $exitCode)
    {
        parent::__construct("Cannot compile program", 0, null);

        $this->output = $output;
        $this->error = $error;
        $this->exitCode = $exitCode;
    }

    /**
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * @return string
     */
    public function getOutput(): string
    {
        return $this->output;
    }

    /**
     * @return int
     */
    public function getExitCode(): int
    {
        return $this->exitCode;
    }
}