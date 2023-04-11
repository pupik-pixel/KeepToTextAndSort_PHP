<?

const DIRNAME_WITH_GOOGLE_KEEP_NOTES = __DIR__ . '\Takeout\Google Keep';
function getZipArchiveFilename(): string|Exception
{
    $isFileFound = false;

    $filesInCurrentDir = scandir(__DIR__);
    foreach ($filesInCurrentDir as $filename) {
        if (preg_match('/\\.zip$/', $filename)) {
            $isFileFound = true;
            return $filename;
        }
    }
    if (!$isFileFound) {
        throw new Exception('file not found');
    }
}

function unzipArchive()
{
    $zip = new ZipArchive();
    if ($zip->open(getZipArchiveFilename()) === true) {
        $zip->extractTo(__DIR__);
    } else {
        throw new Exception('error unzip file');
    }
}

function getNameWithoutProhibitedCharacters($name)
{
    $nameWithoutProhibitedCharacters = trim($name);
    if (str_contains($name, '\\')) {
        $nameWithoutProhibitedCharacters = str_ireplace('\\', 'OR', $name);
    }
    if (str_contains($name, '/')) {
        $nameWithoutProhibitedCharacters = str_ireplace('/', 'OR', $name);
    }
    if (str_contains($name, '|')) {
        $nameWithoutProhibitedCharacters = str_ireplace('|', 'OR', $name);
    }
    if (str_contains($name, ':')) {
        $nameWithoutProhibitedCharacters = str_ireplace(':', 'COLON', $name);
    }
    if (str_contains($name, '*')) {
        $nameWithoutProhibitedCharacters = str_ireplace('*', 'STAR', $name);
    }
    if (str_contains($name, '?')) {
        $nameWithoutProhibitedCharacters = str_ireplace('?', 'QUESTION_MARK', $name);
    }
    if (str_contains($name, '"')) {
        $nameWithoutProhibitedCharacters = str_ireplace('"', 'DOUBLE_QUOTES', $name);
    }
    if (str_contains($name, '<')) {
        $nameWithoutProhibitedCharacters = str_ireplace('<', 'LESS', $name);
    }
    if (str_contains($name, '>')) {
        $nameWithoutProhibitedCharacters = str_ireplace('>', 'MORE', $name);
    }
    return $nameWithoutProhibitedCharacters;
}

function writeNoteToTextFile($label, $title, $content)
{
    if (
        file_put_contents(
            __DIR__ . '\\SortedNotes\\' . $label . '\\' . $title . '.txt',
            $content
        ) !== false
    ) {
        return true;
    } else {
        return false;
    }
}

function rrmdir($src) {
    $dir = opendir($src);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            $full = $src . '/' . $file;
            if ( is_dir($full) ) {
                rrmdir($full);
            }
            else {
                unlink($full);
            }
        }
    }
    closedir($dir);
    rmdir($src);
}

unzipArchive();

if (mkdir('SortedNotes')) {
    $filesWithNotes = scandir(DIRNAME_WITH_GOOGLE_KEEP_NOTES);

    foreach ($filesWithNotes as $htmlOrJsonFilename) {
        if (preg_match('/\\.json$/', $htmlOrJsonFilename)) {
            $note = json_decode(file_get_contents(__DIR__ . '\Takeout\Google Keep\\' . $htmlOrJsonFilename), true);
            if (isset($note['labels'])) {
                foreach ($note['labels'] as $label) {
                    $labelWithoutProhibitedCharacters = getNameWithoutProhibitedCharacters($label['name']);
                    if (file_exists('SortedNotes\\' . $labelWithoutProhibitedCharacters) and is_dir('SortedNotes\\' . $labelWithoutProhibitedCharacters)) {
                        $titleWithoutProhibitedCharacters = getNameWithoutProhibitedCharacters($note['title']);
                        if (!writeNoteToTextFile($labelWithoutProhibitedCharacters, $titleWithoutProhibitedCharacters, $note['textContent'])) {
                            throw new Exception('failed to write file ' . $titleWithoutProhibitedCharacters . '.txt');
                        }
                    } elseif (mkdir('SortedNotes\\' . $labelWithoutProhibitedCharacters)) {
                        $titleWithoutProhibitedCharacters = getNameWithoutProhibitedCharacters($note['title']);
                        if (!writeNoteToTextFile($labelWithoutProhibitedCharacters, $titleWithoutProhibitedCharacters, $note['textContent'])) {
                            throw new Exception('failed to write file ' . $titleWithoutProhibitedCharacters . '.txt');
                        }
                    } else {
                        throw new Exception('failed to create folder ' . $labelWithoutProhibitedCharacters);
                    }
                }
            } elseif (file_exists('SortedNotes\\' . 'NotesWithoutLabel') and is_dir('SortedNotes\\' . 'NotesWithoutLabel')) {
                $titleWithoutProhibitedCharacters = getNameWithoutProhibitedCharacters($note['title']);
                if (!writeNoteToTextFile('NotesWithoutLabel', $titleWithoutProhibitedCharacters, $note['textContent'])) {
                    throw new Exception('failed to write file ' . $titleWithoutProhibitedCharacters . '.txt');
                }
            } elseif (mkdir('SortedNotes\\' . 'NotesWithoutLabel')) {
                $titleWithoutProhibitedCharacters = getNameWithoutProhibitedCharacters($note['title']);
                if (!writeNoteToTextFile('NotesWithoutLabel', $titleWithoutProhibitedCharacters, $note['textContent'])) {
                    throw new Exception('failed to write file ' . $titleWithoutProhibitedCharacters . '.txt');
                }
            } else {
                throw new Exception('failed to create folder SortedNotes\\NotesWithoutLabel');
            }
        }
    }
} else {
    throw new Exception('failed to create folder SortedNotes');
}

rrmdir('Takeout');