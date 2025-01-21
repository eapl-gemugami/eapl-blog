<?php
# Gemtext to HTML Converter

require_once 'libs/gemtextToHtml.php';
require_once 'libs/fileDates.php';

function processFile($inputFile, $outputFile, bool $isPage) {
    # Load header and footer content
    $headerFileName = 'data/header';
    $header = file_exists($headerFileName) ? file_get_contents($headerFileName) : '';
    $header = convertGemtextToHtml($header);

    $footerFileName = 'data/footer';
    $footer = file_exists($footerFileName) ? file_get_contents($footerFileName) : '';
    $footer = convertGemtextToHtml($footer);

    # Read the input Gemtext file
    if (!file_exists($inputFile)) {
        die("Input file does not exist: $inputFile");
    }
    $gemtext = file_get_contents($inputFile);

    $lines = explode("\n", $gemtext);

    if (count($lines) === 0) {
        die("Empty file");
    }

    $title = htmlspecialchars(substr($lines[0], 2));

    # Initialize HTML content
    # Load this from a template
    # TODO: Get title from first line
    $html =
        "<!DOCTYPE html>
<html>
<head>
    <meta charset=\"UTF-8\">
    <link href=\"../style.css\" rel=\"stylesheet\">
    <title>$title</title>
</head>
<body>
";

    # Add header
    $html .= $header;

    # Parse Gemtext and generate HTML
    $body = '';

    $toc = "\n<div class='toc'>\n\t<h2>Content</h2>\n";

    $pre = false;
    $previousNewLine = false;
    $hasHeaders = false;

    foreach (array_slice($lines, 1) as $line) {
        $line = trim($line);

        # TODO: Add this only for new lines, not after a header
        if (empty($line)) {
            if ($previousNewLine) {
                $previousNewLine = false;
                $body .= "<br>\n";
                continue;
            } else {
                $previousNewLine = true;
            }
        }

        $blockquote = false;
        if (str_starts_with($line, '> ')) {
            $blockquote = true;
            $body .= "<blockquote>\n";
            $line = substr($line, 2);
        }

        if (str_starts_with($line, '```')) {
            $pre = !$pre;

            if ($pre) {
                $body .= "<pre>\n";
            } else {
                $body .= "</pre>\n";
            }
        } elseif (str_starts_with($line, '###')) { # Header Level 3
            $headerContent = htmlspecialchars(substr($line, 4));
            $id = sanitizeId($headerContent);
            $body .= "<h3 id='$id'>$headerContent</h3>\n";
            $toc .= "\t<div style='margin-left: 40px;'><a href='#$id'>$headerContent</a></div>\n";
            $hasHeaders = true;
        } elseif (str_starts_with($line, '##')) { # Header Level 2
            $headerContent = htmlspecialchars(substr($line, 3));
            $id = sanitizeId($headerContent);
            $body .= "<h2 id='$id'>$headerContent</h2>";
            $toc .= "\t<div style='margin-left: 20px;'><a href='#$id'>$headerContent</a></div>\n";
            $hasHeaders = true;
        } elseif (str_starts_with($line, '#')) { # Header Level 1
            $headerContent = htmlspecialchars(substr($line, 2));
            $id = sanitizeId($headerContent);
            $body .= "<h1 id='$id'>$headerContent</h1>\n";
            $toc .= "\t<div><a href='#$id'>$headerContent</a></div>\n";
            $hasHeaders = true;
        } elseif (str_starts_with($line, '=>')) { # Link or Image Link
            $lineWithoutHaystack = substr($line, 3);
            $parts = preg_split('/\s+/', $lineWithoutHaystack, 2);
            $url = htmlspecialchars($parts[0]);
            $text = isset($parts[1]) ? htmlspecialchars($parts[1]) : $url;

            if (isImage($url)) {
                $body .= "<a href='$url'><img src='$url' alt='$text'></a><br>\n";
            } else {
                $body .= "<a href='$url'>$text</a><br>\n";
            }
        } elseif (str_starts_with($line, '---')) { # Link or Image Link
            $body .= "<hr>\n";
        } else {
            if ($line === '') { # Ignore empty lines
                continue;
            }
            if ($pre) {
                $body .= htmlspecialchars($line) . "\n";
            } else {
                # Paragraph
                $body .= "<p>" . htmlspecialchars($line) . "</p>\n";
            }
            $previousNewLine = false;
        }

        if ($blockquote) {
            $body .= "</blockquote>\n";
        }
    }

    $toc .= "</div>\n\n";

    # Add Table of Contents and Body
    $html .= "<h1>$title</h1>\n";
    # Pages don't have a date (or should they?)
    if (!$isPage) {
        $html .= getFileCreationModificationDates($inputFile) . "\n";
    }

    if ($hasHeaders) {
        $html .= "$toc<br>\n";
    }

    $html .= $body;

    $html .= $footer;

    # Close HTML structure
    $html .= "</body></html>";

    # Write to the output file
    file_put_contents($outputFile, $html);
}

if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}

function sanitizeId($text) {
    # Convert accented characters to their unaccented equivalents
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    # Remove any characters that are not alphanumeric, hyphen, or underscore
    $text = preg_replace('/[^a-zA-Z0-9-_]/', '-', strtolower($text));
    return $text;
}

function isImage($url) {
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
    $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
    return in_array($extension, $imageExtensions);
}

# Usage example
$fileName = 'index';
$inputFile = "data/$fileName";
$outputFile = "out/$fileName.html";
processFile($inputFile, $outputFile, true);
