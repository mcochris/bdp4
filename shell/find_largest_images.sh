#!/usr/bin/env sh

#╔═════════════════════════════════════════════════════════════════════════════
#║	Borurne shell script to get a list of all the directories that contain
#║	image files and store a resized version of the largest image file in the
#║	directory in the database.
#╚═════════════════════════════════════════════════════════════════════════════

. ./constants.sh || {
	echo "Error: constants.sh not found" >&2
	exit 1
}

trap $(
	rm -f "$COVER_FILE_SQL_INSERTS"
	exit 1
) 1 2 3 15

$LOG && echo "=============================================
Logging started $(date)
=============================================" >>"$LOG_FILE"

#╔═════════════════════════════════════════════════════════════════════════════
#║	User-defined functions
#╚═════════════════════════════════════════════════════════════════════════════
log_this() {
	[ -z "$1" ] && {
		echo "Error: log_this() needs a string argument" >&2
		exit 1
	}
	[ -z "$LOG_FILE" ] && {
		echo "Error: log_this() needs a LOG_FILE variable" >&2
		exit 2
	}
	echo "$(date)| $1" >>"$LOG_FILE"
}

get_image_file_hash() {
	image_file_hash=$(xxhsum "$1" | cut -d ' ' -f 1)
	[ -z "$image_file_hash" ] && {
		echo "Error: get_image_file_hash() failed" >&2
		exit 7
	}
	$LOG && log_this "get_image_file_hash(): sets \$image_file_hash to $image_file_hash"
}

get_image_file_b64() {
	b64=$(convert "$1" -resize 200x200 -.jpg | base64 -w 0)
	[ -z "$b64" ] && {
		echo "Error: get_image_file_b64() failed" >&2
		exit 7
	}
	$LOG && log_this "get_image_file_b64(): sets \$b64 to a ${#b64} byte base64 string"
}

#╔═════════════════════════════════════════════════════════════════════════════
#║	Sanity checks.
#╚═════════════════════════════════════════════════════════════════════════════
[ -f "$DB" ] || {
	echo Database file "$DB" does not exist >&2
	$LOG && log_this "get_image_file_hash(): $image_file_hash"
	exit 4
}

[ -s "$DB" ] || {
	echo Database file "$DB" is empty >&2
	$LOG && log_this "get_image_file_hash(): $image_file_hash"
	exit 4
}

$LOG && log_this "Passed all sanity checks"

#
#       See if the SQL insert file is allready there. If it is, quit because this script
#       is currently running.
#
[ -f "$COVER_FILE_SQL_INSERTS" ] && {
	echo This program is already running >&2
	$LOG && log_this "Program is already running, exiting"
	exit 5
}

#	Initialize SQL inserts file
>"$COVER_FILE_SQL_INSERTS"
$LOG && log_this "COVER_FILE_SQL_INSERTS file initialized"

#       Get the directory name of all images. If there are several images in the directory, the
#       directory will be listed several times. Use the uniq command to eliminate the duplicates.
#
image_dirs=$(find -E "$MUSIC_DIR" -iregex "$IMAGE_FILE_EXTENSIONS" -not -iregex '.*\$recycle\.bin.*' -exec dirname {} \; |
	uniq)

$LOG && log_this "Found $(echo $image_dirs | wc -l) directories with image files: $image_dirs"

	#
	#	Start looping thru all the image directories. A $dir string example:
	#
	#           34700 test_dir/Fred Flintstone/album1/1direction.webp
	#
	echo $image_dirs | while read dir; do
		$LOG && log_this "Directory \"$dir\" has image files"

		#	Don't include directories that have a image file but no music.
		num_audio_files=$(find -E "$dir" -iregex "$AUDIO_FILE_EXTENSIONS" | wc -l)
		
		[ $num_audio_files -eq 0 ] && {
			$LOG && "Skipping $dir because it has no music files"
			continue
		}

		$LOG && log_this "Found $num_audio_files audio files in $dir"

		#	Get largest image file in the directory. The du command can't be used because it
		#	dosen't return the exact filesize.
		largest_image_file=$(find -E "$dir" -iregex "$IMAGE_FILE_EXTENSIONS" -exec stat -f %z {} \; |
			sort --reverse --numeric-sort |
			head --lines 1)

		$LOG && log_this "Largest image file in $dir is $largest_image_file"

		[ -z "$largest_image_file" ] && {
			echo "Error: largest_image_file is empty" >&2
			$LOG && log_this "Largest image file is empty, skipping to next directory"
			continue
		}

		#
		#	At this point three things can happen:
		#		1.	This image file is not in the DB. In this case, add the record with the hash and the
		#			base64 string.
		#		2.	This image file is in the directory but the hash is different. In this case, update the record
		#			with the new hash and the new base64 string.
		#		3.	This image file is in the directory and the hash is the same. In this case, do nothing.
		#
		#	See if this image file is not in the database.
		count=$(sqlite3 "$DB" "SELECT COUNT(*) FROM albums WHERE AlbumCoverFilePath=\"$largest_image_file\";")
		$LOG && log_this "sqlite3 \"$DB\" SELECT COUNT(*) FROM albums WHERE AlbumCoverFilePath=\"$largest_image_file\"; returns $count"
		[ $count -eq 0 ] && {
			$LOG && log_this "Image file is not in the database, will insert it"

			get_image_file_hash "$largest_image_file"
			get_image_file_b64 "$largest_image_file"

			#	Insert the image file into the SQL insert file.
			return=$(sqlite3 "$DB" INSERT INTO albums (AlbumCoverFilePath, AlbumCoverFileHash, AlbumCoverArtImage) VALUES (\"$largest_image_file\", \"$hash\", \"$b64\");)

			$LOG && log_this "sqlite3 \"$DB\" INSERT INTO albums (AlbumCoverFilePath, AlbumCoverFileHash, AlbumCoverArtImage) VALUES (\"$largest_image_file\", \"$hash\", \"$b64\"); returns $return"
			continue
		}

		#	image file name is in the DB. Check if the hash is the same.
		db_hash=$(sqlite3 "$DB" "SELECT AlbumCoverFileHash FROM albums WHERE AlbumCoverFilePath=\"$largest_image_file\";")
		[ "$db_hash" != get_image_file_hash "$largest_image_file" ] && {
			get_image_file_hash "$largest_image_file"
			get_image_file_b64 "$largest_image_file"
			#	Update the image file in the SQL insert file.
			echo "UPDATE albums SET AlbumCoverFileHash=\"$hash\", AlbumCoverArtImage=\"$b64\" WHERE AlbumCoverFilePath=\"$largest_image_file\";"
		}

		#	See if this image file is already in the database. If it is, don't insert it again.
		[ $(sqlite3 bdp4.db "SELECT COUNT(*) FROM albums WHERE AlbumCoverFilePath=\"$largest_image_file\";") -gt 0 ] && continue
		#	Get the hash of the largest image file. This will be used later to check if the image
		#	file has changed and we need to update the database.
		image_file_hash=$(xxhsum "$largest_image_file" | cut -d ' ' -f 1)
		#	Check if the image file is allready in the database. If it is, don't insert it again.
		[ $(sqlite3 bdp4.db "SELECT COUNT(*) FROM albums WHERE =\"$image_file_hash\";") -gt 0 ] && continue
		#	Use ImageMagicK to resize the image file to 200x200 pixels and output
		#	the image file to a base64 string.
		image_cover_b64_string=$(convert "$largest_image_file" -resize 200x200 -.jpg | base64 -w 0)
		#	Insert the image file into the SQL insert file.
		echo "INSERT INTO albums (AlbumCoverFilePath, AlbumCoverFileHash, AlbumCoverArtImage) VALUES (\"$largest_image_file\", \"$image_file_hash\", \"$image_cover_b64_string\");"
	done >>"$COVER_FILE_SQL_INSERTS"

#	Run SQL commands.
sqlite3 bdp4.db <"$COVER_FILE_SQL_INSERTS" && (
	rm "$COVER_FILE_SQL_INSERTS"
	exit 0
) || (
	echo "SQL insert failed" >&2
	exit 6
)

# DB schema for the albums table, where the album cover is stored:
#
# CREATE TABLE "albums"
# (
# 	[AlbumId] INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
# 	[Name] STRING  NOT NULL,
# 	[ArtistId] INTEGER  NOT NULL,
# 	[AlbumCoverFilePath] STRING,
# 	[AlbumCoverFileHash] STRING,
# 	[AlbumCoverArtImage] STRING,
# 	FOREIGN KEY ([ArtistId]) REFERENCES "artists" ([ArtistId])
# 				ON DELETE NO ACTION ON UPDATE NO ACTION
# );
