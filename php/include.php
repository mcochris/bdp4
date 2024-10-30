<?php

declare(strict_types=1);

#╔═════════════════════════════════════════════════════════════════════════════
#║	This file contains the configuration settings for the program.
#╚═════════════════════════════════════════════════════════════════════════════
define("PRODUCTION", false);
define("MUSIC_DIR", "\\\\freenas.localdomain\\Music\\Alot of FLAC");
define("DB_FILE_NAME", "bdp4.sqlite");
define("DEBUG_LOG", "debug.log");
define("IMAGE_FILE_EXTENSIONS", ["jpg", "jpeg", "webp", "bmp", "gif", "png", "ico", "jpt", "pgx", "tiff"]);
define("AUDIO_FILE_EXTENSIONS", ["mp3", "flac", "wav", "ogg", "pcm", "aiff", "aac", "wma", "alac", "wma"]);

ini_set("error_reporting", E_ALL);
ini_set("log_errors", true);
ini_set("error_log", "error.log");

if (PRODUCTION) {
	ini_set("display_errors", false);
	ini_set("display_startup_errors", false);
} else {
	ini_set("display_errors", true);
	ini_set("display_startup_errors", true);
}

define("DB_OPTIONS", [
	PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	PDO::ATTR_EMULATE_PREPARES   => false
]);

define("ALBUM_ART_WIDTH", 200);
define("ALBUM_ART_HEIGHT", 200);
define("HASH_ALGORITHM", "xxh3");

#╔═════════════════════════════════════════════════════════════════════════════
#║	Get the command line options.
#║	usage: php program.php [-h|--help] [--debug[=all|function1,function2,...]]
#╚═════════════════════════════════════════════════════════════════════════════
$options = getopt("h", ["debug::", "help"]);

#╔═════════════════════════════════════════════════════════════════════════════
#║	Display program help if asked for on the command line.
#╚═════════════════════════════════════════════════════════════════════════════
if (in_array(key($options), ["h", "help"], true))
	exit(help());

#╔═════════════════════════════════════════════════════════════════════════════
#║	If the debug arg is set on the command line, determine debug levels.
#║	If no debug value, all debugging is off.
#║	If there is a debug value, it's a comma-separated list of functions to debug.
#║	If the user specifies "all", all functions are debugged.
#╚═════════════════════════════════════════════════════════════════════════════
if (empty(key($options))) {
	define("DEBUG", false);
	define("DEBUG_FUNCTIONS", []);
} else {
	if (strtolower(key($options)) === "debug") {
		define("DEBUG", true);
		define("DEBUG_FUNCTIONS", array_map("strtolower", explode(",", $options["debug"])));
	}
}

#╔═════════════════════════════════════════════════════════════════════════════
#║	Display program help if asked for on the command line.
#╚═════════════════════════════════════════════════════════════════════════════
function help(): void
{
	echo "help!\n";
}

#╔═════════════════════════════════════════════════════════════════════════════
#║	If debugging is enabled for the specified function, write the message to
#║	the debug log.
#╚═════════════════════════════════════════════════════════════════════════════
function debug(string $function = "", string $message = ""): void
{
	if (DEBUG)
		if (in_array(strtolower($function), DEBUG_FUNCTIONS, true) or in_array("all", DEBUG_FUNCTIONS, true))
			file_put_contents(DEBUG_LOG, "[" . date("M j o g:i:s a e") . "]: function \"$function\" returns:\n" . $message . "\n", FILE_APPEND);
}

#╔═════════════════════════════════════════════════════════════════════════════
#║	Get a list of all image files in the specified directory.
#╚═════════════════════════════════════════════════════════════════════════════
function getMusicFiles(string $directory = ""): array
{
	DEBUG and debug(__FUNCTION__, "passed directory: \"$directory\"");

	$musicFiles = [];
	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

	foreach ($iterator as $file)
		if ($file->isFile() and in_array(strtolower($file->getExtension()), AUDIO_FILE_EXTENSIONS, true)) {
			$pathname = $file->getPath();
			$filename = $file->getFilename();
			DEBUG and debug(__FUNCTION__, "found music file, pathname: \"$pathname\", filename: \"$filename\"");
			$musicFiles[] = ["pathname" => $pathname, "filename" => $filename];
		}

	DEBUG and debug(__FUNCTION__, "returning " . count($musicFiles) . " music files: " . var_export($musicFiles, true));

	return $musicFiles;
}

#╔═════════════════════════════════════════════════════════════════════════════
#║	Run a database operation.
#║	@link https://www.php.net/manual/en/book.sqlite3.php
#║	@link https://phpdelusions.net/pdo
#║	@link https://www.tutorialspoint.com/sqlite/sqlite_php.htm
#╚═════════════════════════════════════════════════════════════════════════════
function database_operation(string $sql = "", array $parameters = []): array
{
	DEBUG and debug(__FUNCTION__, "passed sql: \"$sql\" and parameters: " . var_export($parameters, true));

	try {
		$pdo = new PDO("sqlite:" . DB_FILE_NAME, null, null, DB_OPTIONS);
		$stmt = $pdo->prepare($sql);
		$stmt->execute($parameters);
		$result = $stmt->fetchAll();
	} catch (PDOException $e) {
		$result = ["error" => $e->getMessage()];
	}

	DEBUG and debug(__FUNCTION__, "returning " . count($result) . " results: " . var_export($result, true));

	return $result;
}

#╔═════════════════════════════════════════════════════════════════════════════
#║	Resize an image.
#╚═════════════════════════════════════════════════════════════════════════════
function resizeImage(string $image_path = ""): string
{
	$image = imagecreatefromjpeg($image_path);
	$imgResized = imagescale($image, ALBUM_ART_WIDTH, ALBUM_ART_HEIGHT);
	ob_start();
	imagejpeg($imgResized);
	$image_data = ob_get_contents();
	ob_end_clean();
	return sodium_bin2base64($image_data, SODIUM_BASE64_VARIANT_URLSAFE);
}
