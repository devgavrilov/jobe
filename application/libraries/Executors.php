<?php defined('BASEPATH') OR exit('No direct script access allowed');

define('EXECUTORS_CACHE_FILE', '/tmp/jobe_executors_cache_file');

class Executors
{
    // Return an associative array mapping executor name to executor version
    // string for all supported executors (and only supported executors).
    public static function getList()
    {
        if (file_exists(EXECUTORS_CACHE_FILE)) {
            $executorsJson = @file_get_contents(EXECUTORS_CACHE_FILE);
            $executorsMD5 = @file_get_contents(EXECUTORS_CACHE_FILE . '.md5');
            if (md5($executorsJson) !== $executorsMD5) {
                return Executors::buildExecutorsFile();
            }

            return json_decode($executorsJson);
        } else {
            return Executors::buildExecutorsFile();
        }
    }

    public static function get($executor, $task): ?Executor
    {
        // Get the the request executors and check it.
        if (!array_key_exists($executor, Executors::getList())) {
            return null;
        }

        $reqdExecutorClass = ucwords($executor) . '_Task';
        require_once(Executors::get_path_for_executor($executor));
        return new $reqdExecutorClass($task);
    }

    private static function buildExecutorsFile()
    {
        log_message('debug', '*jobe* Missing or corrupt executors cache file ... rebuilding it.');

        $executors = array();
        $library_files = scandir('application/libraries');
        foreach ($library_files as $file) {
            $end = '_task.php';
            $pos = strpos($file, $end);
            if ($pos == strlen($file) - strlen($end)) {
                $executor = substr($file, 0, $pos);
                require_once(Executors::get_path_for_executor($executor));
                $class = $executor . '_Task';
                $version = $class::getVersion()->getVersion();
                if ($version) {
                    $executors[$executor] = $version;
                }
            }
        }

        $executorsJson = json_encode($executors);
        file_put_contents(EXECUTORS_CACHE_FILE, $executorsJson);
        file_put_contents(EXECUTORS_CACHE_FILE . '.md5', md5($executorsJson));
        return $executors;
    }

    /**
     * Get the path to the file that defines the executor task for a given language.
     *
     * @param $language the executor of interest, e.g. cpp.
     * @return string the corresponding code path.
     */
    public static function get_path_for_executor($language)
    {
        return 'application/libraries/' . $language . '_task.php';
    }
}