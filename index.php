<?php

echo "<pre><p>Hello from BDP4!</p>";

define("MUSIC_DIR", "../Music/");

$dirs = glob_recursive(MUSIC_DIR, "*", GLOB_ONLYDIR | GLOB_NOSORT);

foreach ($dirs as $dir) {
	if (str_contains($dir, "\$RECYCLE.BIN"))
		continue;
	echo "<br>" . $dir;
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
	if (is_array($paths)) {
		foreach ($paths as $path) {
			if (is_dir($path)) {
				$subPaths = (__FUNCTION__)($path, $pattern, $flags);
				if ($subPaths !== false) {
					$subPaths = (array) $subPaths;
					array_push($paths, ...$subPaths);
				}
			}
		}
	}

	return $paths;
}
