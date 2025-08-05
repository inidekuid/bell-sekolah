<?php
// config.php

define('DB_PATH', __DIR__ . '/database/school_bell.db');
define('AUDIO_PATH', __DIR__ . '/audio');
define('BELL_COMMAND', 'mpg123'); // 'mpg123' untuk MP3 files


function get_db()
{
    $db = new SQLite3(DB_PATH);
    $db->exec("PRAGMA foreign_keys = ON;");
    return $db;
}
