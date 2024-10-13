<?php

define("PRODUCTION", false);
define("MUSIC_DIR", "music/Alot of FLAC/");

ini_set("error_reporting", E_ALL);
ini_set("log_errors", true);
ini_set("error_log", "bdp4error.log");

if (PRODUCTION) {
    ini_set("display_errors", false);
    ini_set("display_startup_errors", false);
} else {
    ini_set("display_errors", true);
    ini_set("display_startup_errors", true);
}
