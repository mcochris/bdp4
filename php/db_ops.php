<?php

class MyDB extends SQLite3
{
    function __construct()
    {
        $this->open('db.sqlite3', SQLITE3_OPEN_READWRITE);
    }
}

$db = new MyDB();
if (!$db) {
    echo $db->lastErrorMsg();
} else {
    echo "Opened database successfully\n";
}

$stmt = $db->query('SELECT * FROM artists');
while ($row = $stmt->fetchArray(SQLITE3_ASSOC)) {
    var_export($row);
}
