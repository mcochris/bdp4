#!/usr/bin/env sh

#╔═════════════════════════════════════════════════════════════════════════════
#║	Borurne shell script to get a list of all the directories that contain
#║	image files and store a resized version of the largest image file in the
#║	directory in the database.
#╚═════════════════════════════════════════════════════════════════════════════
. ./constants.sh || {
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
=============================================" >> "$LOG_FILE"

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
#║	See if the PID file is allready there. If it is, quit because this script
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
if echo $$ > "$PID_FILE"
then
	$LOG && log_this "PID file initialized"
else
	$LOG && log_this "Could not initialize PID file"
	echo "Error: could not initialize PID file" >&2
	exit 13
fi

#╔═════════════════════════════════════════════════════════════════════════════
#║	Get the directory name of all images. If there are several images in the directory, the
#║	directory will be listed several times. Use the uniq command to eliminate the duplicates.
#╚═════════════════════════════════════════════════════════════════════════════
find -E "$MUSIC_DIR" -iregex "$IMAGE_FILE_EXTENSIONS" -not -iregex ".*\$recycle\.bin.*" -exec dirname {} \; | uniq |
while read -r image_dir;
do
	$LOG && log_this "Directory \"$image_dir\" has image files"

	#	Don't include directories that have a image file but no music.
	num_audio_files=$(find -E "$image_dir" -iregex "$AUDIO_FILE_EXTENSIONS" | wc -l)
	
	[ "$num_audio_files" -eq 0 ] && {
		$LOG && "Skipping \"$image_dir\" because it has no music files"
		continue
	}

	$LOG && log_this "Found $num_audio_files audio files in \"$image_dir\""

	#	Get largest image file in the directory.
	largest_image_file=$(find -E "$image_dir" -iregex "$IMAGE_FILE_EXTENSIONS" -exec stat -f "%z %N" {} \; |
		sort --reverse --numeric-sort --field-separator " " --key 1 |
		head --lines 1 |
		cut -d " " -f 2-)

	$LOG && log_this "Largest image file in \"$image_dir\" is \"$largest_image_file\""

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
	[ "$count" -eq 0 ] && {
		$LOG && log_this "Image directory is not in the database, will insert it"

		get_image_file_hash "$largest_image_file"
		get_image_file_b64 "$largest_image_file"

		#	Insert the image file into the SQL insert file.
		return=$("sqlite3 \"$DB\" INSERT INTO directories (directoryPath, imageFilename, imageHash, imageB64) VALUES (\"$image_dir\", \"$largest_image_file\", \"$image_file_hash\", \"$b64\");")

		$LOG && log_this "sqlite3 \"$DB\" INSERT INTO directories (directoryPath, imageFilename, imageHash, imageB64) VALUES (\"$image_dir\", \"$largest_image_file\", \"$image_file_hash\", \"$b64\"); returns \"$return\""
		continue
	}

	#╔═════════════════════════════════════════════════════════════════════════════
	#║	image directory path is in the DB. Check if the hash is the same. If it's
	#║	not, it means the image file has changed. Recompress the image file and
	#║	update the DB.
	#╚═════════════════════════════════════════════════════════════════════════════
	db_hash=$("sqlite3 \"$DB\" SELECT imageHash FROM directories WHERE directoryPath=\"$image_dir\";")
	get_image_file_hash "$largest_image_file"
	if [ "$db_hash" = "$image_file_hash" ]
	then
		continue
	else
		$LOG && log_this "Hash in DB not not match hash of largest image file. Update the DB."
		get_image_file_b64 "$largest_image_file"
		#	Update the image file in the SQL insert file.
		return=$("sqlite3 \"$DB\" UPDATE directories SET imageHash=\"$image_file_hash\", imageB64=\"$b64\" WHERE directoryPath=\"$image_dir\";")
		$LOG && log_this "sqlite3 \"$DB\" UPDATE directories SET imageHash=\"$image_file_hash\", imageB64=\"$b64\" WHERE directoryPath=\"$image_dir\"; returns \"$return\""
	fi
done

if rm "$PID_FILE"
then
	log_this "PID file removed"
else
	echo "Error: could not remove PID file" >&2
	exit 14
fi