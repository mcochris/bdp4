<?php

declare(strict_types=1);

#╔═════════════════════════════════════════════════════════════════════════════
#║	This script is started by cron. It updates the bdp4 database by scanning
#║	the music directory for artists, albums, and songs.
#╚═════════════════════════════════════════════════════════════════════════════

if (PHP_SAPI !== "cli") {
	error_log(__FILE__ . " tried to run in non-CLI mode");
	exit("This program must be run from the command line.");
}

require_once "php/include.php";
require_once "php/get_artists.php";
require_once "php/update_artist_db_table.php";

// Make it a function to enable debugging
scan_filesystem_4music();

function scan_filesystem_4music(): void
{
	#╔═════════════════════════════════════════════════════════════════════════════
	#║	Get the directory and filename of all the music files.
	#╚═════════════════════════════════════════════════════════════════════════════
	$music_files = getMusicFiles(MUSIC_DIR);
	DEBUG and debug(__FUNCTION__, "found " . count($music_files) . " music files in filesystem: " . var_export($music_files, true));

	#╔═════════════════════════════════════════════════════════════════════════════
	#║	Get a list of the artists from the results of the scan.
	#╚═════════════════════════════════════════════════════════════════════════════
	$artists = get_artists($music_files);
	DEBUG and debug(__FUNCTION__, "found " . count($artists) . " artists from filesystem: " . var_export($artists, true));

	#╔═════════════════════════════════════════════════════════════════════════════
	#║	Get a list of the artists from the DB.
	#╚═════════════════════════════════════════════════════════════════════════════
	$artists_db  = database_operation("SELECT name FROM artists");
	DEBUG and debug(__FUNCTION__, "found " . count($artists_db) . " artists in the DB: " . var_export($artists_db, true));

	#╔═════════════════════════════════════════════════════════════════════════════
	#║	Compare the artists from the filesystem scan with the artists from the DB.
	#║	Update the DB as necessary.
	#╚═════════════════════════════════════════════════════════════════════════════
	$result = update_artist_db_table($artists, $artists_db);
	DEBUG and debug(__FUNCTION__, "update_artist_db_table returns: " . var_export($result, true));
}
