<?php

function convertGemtextToHtml(string $content): string {

    # Initialize HTML content
    $html = '';

    # Parse Gemtext and generate HTML
    $lines = explode("\n", $content);
    $body = '';

    $pre = false;
    $previousNewLine = false;

    foreach ($lines as $line) {
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
        } elseif (str_starts_with($line, '###')) {
            # Header Level 3
            $headerContent = htmlspecialchars(substr($line, 4));
            $id = sanitizeId($headerContent);
            $body .= "<h3 id='$id'>$headerContent</h3>\n";
        } elseif (str_starts_with($line, '##')) {
            # Header Level 2
            $headerContent = htmlspecialchars(substr($line, 3));
            $id = sanitizeId($headerContent);
            $body .= "<h2 id='$id'>$headerContent</h2>";
        } elseif (str_starts_with($line, '#')) {
            # Header Level 1
            $headerContent = htmlspecialchars(substr($line, 2));
            $id = sanitizeId($headerContent);
            $body .= "<h1 id='$id'>$headerContent</h1>\n";
        } elseif (str_starts_with($line, '=>')) {
            # Link or Image Link
            $lineWithoutHaystack = substr($line, 3);
            $parts = preg_split('/\s+/', $lineWithoutHaystack, 2);
            $url = htmlspecialchars($parts[0]);
            $text = isset($parts[1]) ? htmlspecialchars($parts[1]) : $url;

            if (isImage($url)) {
                $body .= "<a href='$url'><img src='$url' alt='$text'></a><br>\n";
            } else {
                $body .= "<a href='$url'>$text</a><br>\n";
            }
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
        }

        if ($blockquote) {
            $body .= "</blockquote>\n";
        }
    }

    $html .= $body;

    return $html;
}