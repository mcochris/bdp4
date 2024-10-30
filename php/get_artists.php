<?php

declare(strict_types=1);

#╔═════════════════════════════════════════════════════════════════════════════
#║	This script is called on by the scan_music.php script.
#║	It's passed a list of all the directories that contain music file(s).
#║	This script determines the artists' name in each directory.
#║	It adds the artists to the database if they don't already exist.
#║	Artists no longer in the folder are removed from the database.
#╚═════════════════════════════════════════════════════════════════════════════

if (PHP_SAPI !== "cli") {
	error_log(__FILE__ . " tried to run in non-CLI mode");
	exit("This program must be run from the command line.");
}

require_once "php/include.php";

function get_artists(array $music_dirs = []): array
{
	DEBUG and debug(__FUNCTION__, "passed " . count($music_dirs) . " music directories: " . var_export($music_dirs, true));

	$tmp =	preg_split("|\\\\|", MUSIC_DIR);
	DEBUG and debug(__FUNCTION__, "preg_split on MUSIC_DIR: " . var_export($tmp, true));
	$music_dir_suffix = $tmp[count($tmp) - 1];
	DEBUG and debug(__FUNCTION__, "music_dir_suffix: \"$music_dir_suffix\"");
	$artists = [];

	foreach ($music_dirs as $music_dir) {
		$tmp = preg_split("|\\\\|", $music_dir["pathname"]);
		$artist = $tmp[count($tmp) - 2];
		if ($artist === $music_dir_suffix)
			$artist = $tmp[count($tmp) - 1];
		DEBUG and debug(__FUNCTION__, "turned \"" . $music_dir["pathname"] . "\" into artist: \"$artist\"");
		$artists[] = $artist;
	}

	return array_unique($artists);
}
