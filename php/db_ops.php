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
    PDO::ATTR_EMULATE_PREPARES   => false
];

if (!$pdo = new PDO("sqlite:/home/chris/bdp4/bdp4.sqlite", null, null, $options))
    exit($pdo->lastErrorMsg());


//  Get all artists
echo "Connected to DB\nAll artists:\n";
$stmt = $pdo->query('SELECT * FROM artists');
while ($row = $stmt->fetch())
    echo var_export($row, true), "\n";


//  Get one artist
echo "\nGet one artist:\n";
$stmt = $pdo->prepare('SELECT name FROM artists WHERE name = :name');
$stmt->execute(["name" => "Artist 5"]);
$artist_name = $stmt->fetch();
if ($artist_name)
    echo "Found ", $artist_name["name"], "\n";
else
    echo "Did not find artist 5";


//  Add artist
echo "\nAdd artist:\n";
$stmt = $pdo->prepare("INSERT INTO artists (name) values (:name) returning ArtistId");
$rand = random_int(10, 1000);
$stmt->execute(['name' => "Artist $rand"]);
$ArtistIdInserted = $stmt->fetch()["ArtistId"];

//  Add album
echo "\nAdd album:\n";
$img = "img/Fred Flintstone.jpg";
if (!file_exists($img))
    exit("No cover file");

$stmt = $pdo->prepare("INSERT INTO albums (ArtistId, name, AlbumCoverFilePath, AlbumCoverFileHash, AlbumCoverArtImage) values (:ArtistId, :name, :AlbumCoverFilePath, :AlbumCoverFileHash, :AlbumCoverArtImage)");
$stmt->execute(["ArtistId" => $ArtistIdInserted, "name" => "The Flintstone project", 'AlbumCoverFilePath' => $img, "AlbumCoverFileHash" => hash_file("xxh32", $img), "AlbumCoverArtImage" => resizeImage($img)]);
echo $stmt->rowCount(), " records inserted\n";

//  Get album

//  Add songs

//  Update

//  Delete

function resizeImage($image_path)
{
    $image = imagecreatefromjpeg($image_path);
    $imgResized = imagescale($image, 200, 200);
    ob_start();
    imagejpeg($imgResized);
    $image_data = ob_get_contents();
    ob_end_clean();
    return base64_encode($image_data);
}
