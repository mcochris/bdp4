<?php

declare(strict_types=1);

// 
//  Global variables and required functions used by bdp4 app.
//

define("PRODUCTION", false);
define("MUSIC_DIR", "music/Alot of FLAC/");
define("DB_FILE_NAME", "bdp4.sqlite");
define("DEBUG_LOG", "debug.log");

ini_set("error_reporting", E_ALL);
ini_set("log_errors", true);
ini_set("error_log", "bdp4error.log");

if (PRODUCTION) {
    ini_set("display_errors", false);
    ini_set("display_startup_errors", false);
} else {
    ini_set("display_errors", true);
    ini_set("display_startup_errors", true);
}

//  enable help and debug options
$options = getopt("h", ["debug::", "help"]);

//  Does the user want help?
if (in_array(key($options), ["h", "help"]))
    exit(help());

//  If debug arg is set, then determine debug levels.
//  If no debug value, all debugging is on.
//  If there is a debug value, it's a comma-separated list of function to debug.
if (key($options) === "debug") {
    define("DEBUG", true);
    if (empty($options["debug"]))
        define("DEBUG_FUNCTIONS", "all");
    else
        define("DEBUG_FUNCTIONS", explode(",", $options["debug"]));
} else {
    define("DEBUG", false);
    define("DEBUG_FUNCTIONS", []);
}

/**
 * Recursive `glob()`.
 *
 * @author info@ensostudio.ru
 * @link https://gist.github.com/UziTech/3b65b2543cee57cd6d2ecfcccf846f20?permalink_comment_id=3393822#gistcomment-3393822
 * @param string $baseDir Base directory to search
 * @param string $pattern Glob pattern
 * @param int $flags Behavior bitmask
 * @return array|string|bool
 */
function glob_recursive(string $baseDir = "", string $pattern = "", int $flags = GLOB_NOSORT | GLOB_BRACE): array
{
    if (empty($baseDir)) {
        error_log("Music directory not supplied.");
        exit(0);
    }

    if (!is_dir($baseDir)) {
        error_log("Specified music directory \"$baseDir\" is not a directory.");
        exit(0);
    }

    if (empty($pattern)) {
        error_log("glob pattern not supplied.");
        exit(0);
    }

    $paths = glob(rtrim($baseDir, '\/') . DIRECTORY_SEPARATOR . $pattern, $flags);
    if ($paths === false) {
        error_log(__FUNCTION__ . "- glob on directory \"$baseDir\" failed.");
        exit(0);
    }

    foreach ($paths as $path)
        if (is_dir($path)) {
            $subPaths = (__FUNCTION__)($path, $pattern, $flags);
            if ($subPaths !== false) {
                $subPaths = (array) $subPaths;
                array_push($paths, ...$subPaths);
            }
        }

    debug(__FUNCTION__, "found these subdirectories in directory \"$baseDir\":\n" . var_export($paths, true));
    return $paths;
}

/**
 *  Display help message
 */
function help(): void
{
    echo "help!\n";
}


/**
 *  Write debug message to debug log
 */
function debug(string $function, string $message): void
{
    if ((DEBUG_FUNCTIONS === "all") or in_array($function, DEBUG_FUNCTIONS))
        file_put_contents(DEBUG_LOG, "[" . date("M j o g:i:s a e") . "]: function \"$function\" returns:\n" . $message . "\n", FILE_APPEND);
}
