#!/usr/bin/env sh

#╔═════════════════════════════════════════════════════════════════════════════
#║	Borurne shell script to get a list of all the directories that contain
#║	image files and store a resized version of the largest image file in the
#║	directory in the database.
#╚═════════════════════════════════════════════════════════════════════════════
. ./constants.sh
[ $? -ne 0 ] && {
	echo "Error: constants.sh failed" >&2
	exit 1
}

trap $(
	rm -f "$$PID_FILE"
	exit 3
) 1 2 3 15

$LOG && echo "
=============================================
Logging started $(date)
=============================================" >>"$LOG_FILE"

#╔═════════════════════════════════════════════════════════════════════════════
#║	User-defined functions
#╚═════════════════════════════════════════════════════════════════════════════
log_this() {
	[ -z "$1" ] && {
		echo "Error: log_this() needs a string argument" >&2
		exit 4
	}
	[ -z "$LOG_FILE" ] && {
		echo "Error: log_this() needs a LOG_FILE variable" >&2
		exit 5
	}
	echo "$(date)| $1" >>"$LOG_FILE"
}

get_image_file_hash() {
	[ -z "$1" ] && {
		echo "Error: get_image_file_hash() needs a string argument" >&2
		exit 6
	}
	image_file_hash=$(xxhsum "$1" | cut -d ' ' -f 1)
	[ -z "$image_file_hash" ] && {
		echo "Error: get_image_file_hash() failed" >&2
		exit 7
	}
	$LOG && log_this "get_image_file_hash(): sets \$image_file_hash to $image_file_hash"
}

get_image_file_b64() {
	[ -z "$1" ] && {
		echo "Error: get_image_file_b64() needs a string argument" >&2
		exit 8
	}
	b64=$(convert "$1" -resize 200x200 -.jpg | base64 -w 0)
	[ -z "$b64" ] && {
		echo "Error: get_image_file_b64() failed" >&2
		exit 9
	}
	$LOG && log_this "get_image_file_b64(): sets \$b64 to a ${#b64} byte base64 string"
}

#╔═════════════════════════════════════════════════════════════════════════════
#║	Sanity checks.
#╚═════════════════════════════════════════════════════════════════════════════
[ -f "$DB" ] || {
	echo Database file "$DB" does not exist >&2
	$LOG && log_this "DB file does not exist"
	exit 10
}

[ -s "$DB" ] || {
	echo Database file "$DB" is empty >&2
	$LOG && log_this "DB file is empty"
	exit 11
}

$LOG && log_this "Passed sanity checks"

#╔═════════════════════════════════════════════════════════════════════════════
#║	See if the SQL insert file is allready there. If it is, quit because this script
#║	is currently running.
#╚═════════════════════════════════════════════════════════════════════════════
[ -f "$PID_FILE" ] && {
	echo This program is already running >&2
	$LOG && log_this "Program is already running, exiting"
	exit 12
}

#╔═════════════════════════════════════════════════════════════════════════════
#║	Initialize program process identification file
#╚═════════════════════════════════════════════════════════════════════════════
$(echo $$ > "$PID_FILE") && log_this "PID file initialized" || {
	echo "Error: could not initialize PID file" >&2
	exit 13
}

#╔═════════════════════════════════════════════════════════════════════════════
#║	Get the directory name of all images. If there are several images in the directory, the
#║	directory will be listed several times. Use the uniq command to eliminate the duplicates.
#╚═════════════════════════════════════════════════════════════════════════════
image_dirs=$(find -E "$MUSIC_DIR" -iregex "$IMAGE_FILE_EXTENSIONS" -not -iregex '.*\$recycle\.bin.*' -exec dirname {} \; | uniq)

$LOG && log_this "Found $(echo "$image_dirs" | wc -l) directories with image files: $image_dirs"

[ -z "$image_dirs" ] && {
	$LOG && log_this "No directories with image files found"
	exit 14
}

echo $image_dirs
exit
#╔═════════════════════════════════════════════════════════════════════════════
#║	Start looping thru all the image directories. A $image_dir string example:
#║		34700 test_dir/Fred Flintstone/album1/1direction.webp
#╚═════════════════════════════════════════════════════════════════════════════
for image_dir in "$image_dirs; do
	$LOG && log_this "Directory \"$image_dir\" has image files"

	#	Don't include directories that have a image file but no music.
	num_audio_files=$(find -E "$image_dir" -iregex "$AUDIO_FILE_EXTENSIONS" | wc -l)
	
	echo num_audio_files: $num_audio_files
	exit

	[ $num_audio_files -eq 0 ] && {
		$LOG && "Skipping $image_dir because it has no music files"
		continue
	}

	$LOG && log_this "Found $num_audio_files audio files in $image_dir"

	#	Get largest image file in the directory.
	largest_image_file=$(find -E \"$image_dir\" -iregex "$IMAGE_FILE_EXTENSIONS" -exec stat -f %z {} \; |
		sort --reverse --numeric-sort |
		head --lines 1)

	$LOG && log_this "Largest image file in $image_dir is $largest_image_file"

	[ -z "$largest_image_file" ] && {
		echo "Error: largest_image_file is empty" >&2
		$LOG && log_this "Largest image file is empty, skipping to next directory"
		continue
	}

	#╔═════════════════════════════════════════════════════════════════════════════
	#║	At this point three things can happen:
	#║		1.	This image file is not in the DB. In this case, add the record with the hash and the
	#║			base64 string.
	#║		2.	This image file is in the directory but the hash is different. In this case, update the record
	#║			with the new hash and the new base64 string.
	#║		3.	This image file is in the directory and the hash is the same. In this case, do nothing.
	#║
	#║	See if this image file is not in the database.
	#╚═════════════════════════════════════════════════════════════════════════════
	count=$("sqlite3 \"$DB\" SELECT COUNT(*) FROM directories WHERE directoryPath=\"$image_dir\";")
	$LOG && log_this "sqlite3 \"$DB\" SELECT COUNT(*) FROM directories WHERE directoryPath=\"$image_dir\"; returns $count"
	[ $count -eq 0 ] && {
		$LOG && log_this "Image directory is not in the database, will insert it"

		get_image_file_hash "$largest_image_file"
		get_image_file_b64 "$largest_image_file"

		#	Insert the image file into the SQL insert file.
		return=$("sqlite3 \"$DB\" INSERT INTO directories (directoryPath, imageFilename, imageHash, imageB64) VALUES (\"$image_dir\", \"$largest_image_file\", \"$image_file_hash\", \"$b64\");")

		$LOG && log_this "sqlite3 \"$DB\" INSERT INTO directories (directoryPath, imageFilename, imageHash, imageB64) VALUES (\"$image_dir\", \"$largest_image_file\", \"$image_file_hash\", \"$b64\"); returns $return"
		continue
	}

	#╔═════════════════════════════════════════════════════════════════════════════
	#║	image directory path is in the DB. Check if the hash is the same.
	#╚═════════════════════════════════════════════════════════════════════════════
	db_hash=$("sqlite3 \"$DB\" SELECT imageHash FROM directories WHERE directoryPath=\"$image_dir\";")
	get_image_file_hash "$largest_image_file"
	[ "$db_hash" = "$image_file_hash" ]	&& continue || {
		$LOG && log_this "Hash in DB not not match hash of largest image file. Update the DB."
		get_image_file_b64 "$largest_image_file"
		#	Update the image file in the SQL insert file.
		return=$("sqlite \"$DB\" UPDATE directories SET imageHash=\"$hash\", imageB64=\"$b64\" WHERE directoryPath=\"$image_dir\";")
		$LOG && log_this "sqlite \"$DB\" UPDATE directories SET imageHash=\"$hash\", imageB64=\"$b64\" WHERE directoryPath=\"$image_dir\"; returns $return"
		continue
	}

	# #	See if this image file is already in the database. If it is, don't insert it again.
	# [ $(sqlite3 \"$DB\" SELECT COUNT(*) FROM directories WHERE imageFilename=\"$largest_image_file\";) -gt 0 ] && continue
	# #	Get the hash of the largest image file. This will be used later to check if the image
	# #	file has changed and we need to update the database.
	# image_file_hash=$(xxhsum "$largest_image_file" | cut -d ' ' -f 1)
	# #	Check if the image file is allready in the database. If it is, don't insert it again.
	# [ $(sqlite3 \"$DB\" SELECT COUNT(*) FROM directories WHERE =\"$imageB64\";) -gt 0 ] && continue
	# #	Use ImageMagicK to resize the image file to 200x200 pixels and output
	# #	the image file to a base64 string.
	# image_b64_string=$(convert "$largest_image_file" -resize 200x200 -.jpg | base64 -w 0)
	# #	Insert the image file into the SQL insert file.
	# echo "INSERT INTO directories (AlbumCoverFilePath, AlbumCoverFileHash, AlbumCoverArtImage) VALUES (\"$largest_image_file\", \"$image_file_hash\", \"$image_cover_b64_string\");"
done

$(rm $PID_FILE) && log_this "PID file removed" || {
	echo "Error: could not remove PID file" >&2
	exit 14
}