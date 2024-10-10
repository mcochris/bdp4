<?php

function get_albums()
{
    $dirs = glob_recursive(MUSIC_DIR, "*", GLOB_ONLYDIR | GLOB_NOSORT);
    sort($dirs, SORT_NATURAL | SORT_FLAG_CASE);
    foreach ($dirs as $dir) {
        if (str_contains($dir, "\$RECYCLE.BIN"))
            continue;
        echo "<div class=\"col-sm\">$dir</div>";
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
 * Sample data
 * 
../Music/Bill Laswell/Bill Laswell and Pete Namlook/Outland 3
../Music/Pierre Bensusan/Spices
../Music/Harold Budd & Brian Eno/The Pearl
../Music/Johnette Napolitano & Holly Vincent/Vowel Movement
../Music/Eminem/The Marshall Mathers LP
../Music/The Firm/Mean Business
../Music/Mariah Carey/Music Box
../Music/Alot of FLAC/Eagle-Eye Cherry
../Music/Alot of FLAC/Beethoven/Beethoven 5th
../Music/Alot of FLAC/Beethoven/Beethoven 7th
../Music/Alot of FLAC/Beethoven/Beethoven 9th
../Music/Alot of FLAC/Rolling Stones/Sticky Fingers
../Music/Alot of FLAC/Rolling Stones/Let It Bleed

 */
