<?php

require_once "php/include.php";

function get_albums()
{
	$dirs = glob_recursive(MUSIC_DIR, "*", GLOB_ONLYDIR | GLOB_NOSORT);
	sort($dirs, SORT_NATURAL | SORT_FLAG_CASE);

	foreach ($dirs as $dir) {
		if (str_contains($dir, "\$RECYCLE.BIN"))
			continue;
		if (!empty(array_filter(glob("$dir/*"), "is_dir")))
			continue;
		get_album_cover($dir);
	}
}


/**
 * Get album cover.
 *
 * @param string $dir Directory
 * @return string
 */
function get_album_cover($dir)
{
	$cover_file_name =  "img/dummy_200x200_ffffff_cccccc_.png";
	$max_filesize = 0;

	$cover_file_names = glob("$dir/*.{jpg,jpeg,png}", GLOB_BRACE);
	foreach ($cover_file_names as $tmp) {
		$filesize = filesize($tmp);
		if ($filesize > $max_filesize) {
			$max_filesize = $filesize;
			$cover_file_name = $tmp;
		}
	}

	return $cover_file_name;
}
