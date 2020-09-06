<?php defined('BASEPATH') OR exit('No direct script access allowed');

require_once 'application/libraries/Exceptions/JobException.php';

class MissingOrUnavailableException extends JobException
{
    public function __construct()
    {
        parent::__construct('One or more of the specified files is missing/unavailable', 'file(s) not found', 404);
    }
}