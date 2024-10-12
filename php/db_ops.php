<?php

/**
 * Get albums.
 *
 * @link https://www.php.net/manual/en/book.sqlite3.php
 * @link https://phpdelusions.net/pdo
 * @link https://www.tutorialspoint.com/sqlite/sqlite_php.htm
 * 
 * @return void
 */

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

if (!$pdo = new PDO("sqlite:db.sqlite3", null, null, $options))
    exit($pdo->lastErrorMsg());

//  Get all artists
echo "Connected to DB\nAll artists:\n";
$stmt = $pdo->query('SELECT name FROM artists');
while ($row = $stmt->fetch())
    echo $row['name'], "\n";

//  Get one artist
echo "\nGet one artist:\n";
$stmt = $pdo->prepare('SELECT name FROM artists WHERE name = :name');
$stmt->execute(["name" => "Artist 5"]);
$artist_name = $stmt->fetch();
if ($artist_name)
    echo "Found ", $artist_name["name"], "\n";
else
    exit("Did not find artist");

//  Add artist
echo "\nAdd artist:\n";
$stmt = $pdo->prepare("INSERT INTO artists (name) values (:name)");
$stmt->execute([':name' => "Artist 7"]);
echo $stmt->rowCount(), " records inserted\n";

//  Add album
echo "\nAdd artist:\n";
$img = "img/Fred Flintstone.jpg";
$stmt = $pdo->prepare("INSERT INTO albums (name, cover_art, cover_art_hash) values (:name, :cover_art, :cover_art_hash)");
$stmt->execute([':name' => "The Flintstones", "cover_art" => shrink_it($img), hash("xxh32", $img)]);
echo $stmt->rowCount(), " records inserted\n";

/*
//  Add songs

//  Update

//  Delete
*/

function shrink_it($fn)
{
    //  https://stackoverflow.com/questions/14649645/resize-image-in-php

}
