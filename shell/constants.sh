#!/usr/bin/env sh

#╔═════════════════════════════════════════════════════════════════════════════
#║	Constants for the find_largest_images.sh script.
#╚═════════════════════════════════════════════════════════════════════════════

readonly MUSIC_DIR="../../Music/Alot of FLAC/"
readonly IMAGE_FILE_EXTENSIONS='.*\.(jpg|jpeg|webp|bmp|gif|png|ico|jpt|pgx|tiff)$'
readonly AUDIO_FILE_EXTENSIONS='.*\.(mp3|flac|wav|ogg|pcm|aiff|aac|wma|alac|wma)$'
readonly PID_FILE="pidFile"
readonly DB="../bdp4.sqlite"
readonly LOG=true
readonly LOG_FILE="find_largest_images.log"
readonly MAX_LOG_SIZE=1000
