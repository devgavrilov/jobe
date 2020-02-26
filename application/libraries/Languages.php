<?php defined('BASEPATH') OR exit('No direct script access allowed');

define('LANGUAGE_CACHE_FILE', '/tmp/jobe_language_cache_file');

class Languages {
    // Return an associative array mapping language name to language version
    // string for all supported languages (and only supported languages).
    public static function getList() {
        if (file_exists(LANGUAGE_CACHE_FILE)) {
            $langsJson = @file_get_contents(LANGUAGE_CACHE_FILE);
            $langsMD5 = @file_get_contents(LANGUAGE_CACHE_FILE . '.md5');
            if (md5($langsJson) !== $langsMD5) {
                return Languages::buildLanguagesFile();
            }

            return json_decode($langsJson);
        } else {
            return Languages::buildLanguagesFile();
        }
    }

    public static function get($language, $sourceFile, $input, $params) {
        // Get the the request languages and check it.
        if (!array_key_exists($language, Languages::getList())) {
            return null;
        }

        $reqdTaskClass = ucwords($language) . '_Task';
        require_once(Languages::get_path_for_language_task($language));
        return new $reqdTaskClass($sourceFile, $input, $params);
    }

    private static function buildLanguagesFile() {
        Languages::log('debug', 'Missing or corrupt languages cache file ... rebuilding it.');

        $langs = array();
        $library_files = scandir('application/libraries');
        foreach ($library_files as $file) {
            $end = '_task.php';
            $pos = strpos($file, $end);
            if ($pos == strlen($file) - strlen($end)) {
                $lang = substr($file, 0, $pos);
                require_once(Languages::get_path_for_language_task($lang));
                $class = $lang . '_Task';
                $version = $class::getVersion();
                if ($version) {
                    $langs[$lang] = $version;
                }
            }
        }

        $langsJson = json_encode($langs);
        file_put_contents(LANGUAGE_CACHE_FILE, $langsJson);
        file_put_contents(LANGUAGE_CACHE_FILE . '.md5', md5($langsJson));
        return $langs;
    }

    /**
     * Get the path to the file that defines the language task for a given language.
     *
     * @param $lang the language of interest, e.g. cpp.
     * @return string the corresponding code path.
     */
    public static function get_path_for_language_task($lang) {
        return 'application/libraries/' . $lang . '_task.php';
    }

    private static function log($type, $message) {
        // Call log_message with the same parameters, but prefix the message
        // by *jobe* for easy identification.
        log_message($type, '*jobe* ' . $message);
    }
}