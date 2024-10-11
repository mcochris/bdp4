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
        echo '
            <figure class="figure">
            	<a href="boo">
            		<img src="', get_album_cover($dir), '" height="200" width="200" class="figure-img img-fluid">
            	</a>
            	<figcaption class="figure-caption">', basename(dirname($dir)), '<br>', basename($dir), '</figcaption>
            </figure>';
    }
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
function glob_recursive(string $baseDir, string $pattern, int $flags = GLOB_NOSORT | GLOB_BRACE)
{
    $paths = glob(rtrim($baseDir, '\/') . DIRECTORY_SEPARATOR . $pattern, $flags);
    if (is_array($paths))
        foreach ($paths as $path)
            if (is_dir($path)) {
                $subPaths = (__FUNCTION__)($path, $pattern, $flags);
                if ($subPaths !== false) {
                    $subPaths = (array) $subPaths;
                    array_push($paths, ...$subPaths);
                }
            }

    return $paths;
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
