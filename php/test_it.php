<?php

declare(strict_types=1);

require_once "php/include.php";
require_once "php/get_artists.php";

$music_dirs = getMusicFiles(MUSIC_DIR);
echo "get artists returns: ", var_export(get_artists($music_dirs), true), PHP_EOL;
