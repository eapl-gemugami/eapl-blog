<?php

function getFileCreationModificationDates(string $filePath) {
    if (!file_exists($filePath)) {
        return "Error: File does not exist.";
    }

    # Get the creation and modification dates
    $creationDate = date('Y-m-d', filectime($filePath));
    $modificationDate = date('Y-m-d', filemtime($filePath));

    if ($creationDate === $modificationDate) {
        return $creationDate;
    } else {
        return "$creationDate (Updated $modificationDate)";
    }
}