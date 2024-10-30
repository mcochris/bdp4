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

function get_artists(array $music_dirs = []): void
{
	DEBUG and debug(__FUNCTION__, "passed " . count($music_dirs) . " music directories: " . var_export($music_dirs, true));

	$artists = [];
	foreach ($music_dirs as $music_dir) {
		$tmp = preg_split("|\\\\|", $music_dir["pathname"]);
		$artist = $tmp[count($tmp) - 2];
		DEBUG and debug(__FUNCTION__, "found artist: \"$artist\"");
	}
}
