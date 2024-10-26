<?php

declare(strict_types=1);

require_once("php/include.php");

if (PHP_SAPI !== "cli") {
	error_log(__FILE__ . " tried to run in non-CLI mode");
	exit("This program must be run from the command line.");
}

$directories = get_directories(MUSIC_DIR);

// find_music_files($directories);

function get_directories(string $music_directory = ""): array
{
	if (empty($music_directory)) {
		error_log("Music directory not supplied.");
		exit(0);
	}

	if (!is_dir($music_directory)) {
		error_log("Specified music directory \"$music_directory\" is not a directory.");
		exit(0);
	}

	$sub_directory_list = [];

	$sub_directories = glob_recursive($music_directory, "*", GLOB_ONLYDIR | GLOB_NOSORT);
	if (empty($sub_directories)) {
		error_log("No subdirectories under music directory \"$music_directory\"");
		return [];
	}

	if (!sort($sub_directories, SORT_NATURAL | SORT_FLAG_CASE)) {
		error_log("sort failed on subdirectories list under music directory \"$music_directory\"");
		return [];
	}

	foreach ($sub_directories as $sub_directory) {
		if ($sub_directory === "\$RECYCLE.BIN")
			continue;

		//  Don't count sub_directories that have no subdirectories. These subdirectories just contain the music files.
		if (!empty(array_filter(glob("$sub_directory/*"), "is_dir")))
			continue;

		$sub_directory_list[] = $sub_directory;
	}

	debug(__FUNCTION__, "List of subdirectories: " . var_export($sub_directory_list, true));
	return $sub_directory_list;
}
