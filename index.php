<?php
// index.php

// ...existing code...

$mainData = getMainData(); // fetch main data
$archiveData = getArchiveData(); // fetch archive data

if (empty($mainData) && !empty($archiveData)) {
    echo '<div class="empty-state">No active data found, but there are items in the archive.</div>';
} else {
    // ...existing code to display main data...
}

// ...existing code...