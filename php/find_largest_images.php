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
		