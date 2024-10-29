PRAGMA foreign_keys = 1;

PRAGMA optimize;

-- PRAGMA journal_mode = wal;
-- PRAGMA synchronous = normal;
PRAGMA encoding = "UTF-8";

PRAGMA user_version = "1.0";

CREATE TABLE artists (
	artist_id INTEGER PRIMARY KEY,
	name TEXT NOT NULL,
	genre TEXT
) STRICT;

CREATE TABLE albums (
	album_id INTEGER PRIMARY KEY,
	title TEXT NOT NULL,
	release_date TEXT,
	artist_id INTEGER NOT NULL,
	cover_art BLOB,
	-- binary of original cover art image file, resized for speedy display
	cover_art_hash TEXT,
	-- hash of original album art. Used to detect a change to the cover art file so it's image can be resized and updated in the DB
	FOREIGN KEY (artist_id) REFERENCES artists(artist_id)
) STRICT;

CREATE TABLE songs (
	song_id INTEGER PRIMARY KEY,
	directory TEXT NOT NULL,
	filename TEXT NOT NULL,
	title TEXT NOT NULL,
	duration INTEGER,
	-- Duration in seconds
	album_id INTEGER,
	artist_id INTEGER,
	metadata TEXT,
	-- json string of song metadata
	FOREIGN KEY (album_id) REFERENCES albums(album_id),
	FOREIGN KEY (artist_id) REFERENCES artists(artist_id),
	CHECK (
		album_id IS NOT NULL
		OR artist_id IS NOT NULL
	),
	-- Ensures either album_id or artist_id is present
	CHECK (
		album_id IS NULL
		OR artist_id IS NULL
	) -- Ensures song is linked to only one of album or artist
) STRICT;

CREATE TABLE playlists (
	playlist_id INTEGER PRIMARY KEY,
	name TEXT NOT NULL,
	description TEXT
) STRICT;

CREATE TABLE playlist_songs (
	playlist_id INTEGER,
	song_id INTEGER,
	PRIMARY KEY (playlist_id, song_id),
	FOREIGN KEY (playlist_id) REFERENCES playlists(playlist_id),
	FOREIGN KEY (song_id) REFERENCES songs(song_id)
) STRICT;

CREATE VIEW song_details AS
SELECT
	s.title AS song_name,
	a.name AS artist_name,
	al.title AS album_title,
	al.cover_art AS album_cover_art
FROM
	songs s
	LEFT JOIN artists a ON s.artist_id = a.artist_id
	LEFT JOIN albums al ON s.album_id = al.album_id;