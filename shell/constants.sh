readonly MUSIC_DIR="../../Music/Alot of FLAC/Beethoven/"
readonly IMAGE_FILE_EXTENSIONS='.*\.(jpg|jpeg|webp|bmp|gif|png|ico|jpt|pgx|tiff)$'
readonly AUDIO_FILE_EXTENSIONS='.*\.(mp3|flac|wav|ogg|pcm|aiff|aac|wma|alac|wma)$'
readonly PID_FILE="pidFile"
readonly DB="../bdp4.sqlite"
readonly LOG=true
readonly LOG_FILE="find_largest_images.log"

[ -f "$DB" ] || {
	echo Database file "$DB" does not exist >&2
	exit 10
}

[ -s "$DB" ] || {
	echo Database file "$DB" is empty >&2
	exit 11
}

[ -d "$MUSIC_DIR" ] || {
	echo Music directory "$MUSIC_DIR" does not exist >&2
	exit 111
}

[ -z "$IMAGE_FILE_EXTENSIONS" ] && {
	echo Image file extensions are not set >&2
	exit 12
}

[ -z "$AUDIO_FILE_EXTENSIONS" ] && {
	echo Audio file extensions are not set >&2
	exit 13
}

[ -z "$PID_FILE" ] && {
	echo PID file is not set >&2
	exit 14
}

[ -f "$PID_FILE" ] && {
	[ $(find "$PID_FILE" -mmin +60) ] && {
		echo This program \($(basename $0)\) has been running for over an hour, something is wrong. Will restart program. >&2
		rm -f "$PID_FILE"
	} || {
		echo This program is already running >&2
		exit 15
	}
}

[ -f $LOG_FILE ] && {
	[ $(find "$LOG_FILE" -size 10k) ] && {
		echo Trimming large log file \($LOG_FILE\). >&2
		tail --lines=250 $LOG_FILE > /tmp/$LOG_FILE
		mv /tmp/$LOG_FILE $LOG_FILE
	}
}

return 0