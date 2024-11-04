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

update_artist_db_table();

function update_artist_db_table(array $filesystem_artists = [], array $db_artists = []): array
{
	DEBUG and debug(__FUNCTION__, "passed " . count($filesystem_artists) . " filesystem artists: " . var_export($filesystem_artists, true));
	DEBUG and debug(__FUNCTION__, "passed " . count($db_artists) . " database artists: " . var_export($db_artists, true));

	$db_artists2 = array_column($db_artists, "name");
	DEBUG and debug(__FUNCTION__, "db_artists2: " . var_export($db_artists2, true));

	$artists_to_add = array_diff($filesystem_artists, $db_artists2);
	DEBUG and debug(__FUNCTION__, "found " . count($artists_to_add) . " artists to add: " . var_export($artists_to_add, true));

	$artists_to_remove = array_diff($db_artists2, $filesystem_artists);
	DEBUG and debug(__FUNCTION__, "found " . count($artists_to_remove) . " artists to remove: " . var_export($artists_to_remove, true));

	foreach ($artists_to_add as $artist) {
		$sql = "INSERT INTO artists (name) VALUES (:name)";
		$parameters = [":name" => $artist];
		$result = database_operation($sql, $parameters);
		DEBUG and debug(__FUNCTION__, "added artist: \"$artist\", result: " . var_export($result, true));
	}

	foreach ($artists_to_remove as $artist) {
		$sql = "DELETE FROM artists WHERE name = :name";
		$parameters = [":name" => $artist];
		$result = database_operation($sql, $parameters);
		DEBUG and debug(__FUNCTION__, "removed artist: \"$artist\", result: " . var_export($result, true));
	}

	return ["artists added to DB" => count($artists_to_add), "artists removed from DB" => count($artists_to_remove)];
}
