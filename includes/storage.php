<?php
/**
 * JSON File-Based Storage Layer
 *
 * Provides atomic read/write helpers for JSON files with file-locking,
 * directory management, and secure ID generation.
 *
 * @package School
 */

/**
 * Read and decode a JSON file.
 *
 * @param  string $path  Absolute path to the JSON file.
 * @return mixed         Decoded data (array/object) or null on failure.
 */
function readJson(string $path)
{
    if (!file_exists($path)) {
        return null;
    }

    $contents = file_get_contents($path);
    if ($contents === false) {
        error_log("readJson: unable to read file {$path}");
        return null;
    }

    $data = json_decode($contents, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("readJson: JSON decode error in {$path}: " . json_last_error_msg());
        return null;
    }

    return $data;
}

/**
 * Encode data as JSON and write it to a file atomically with locking.
 *
 * @param  string $path  Absolute path to the JSON file.
 * @param  mixed  $data  Data to encode and write.
 * @return bool          True on success, false on failure.
 */
function writeJson(string $path, $data): bool
{
    ensureDir(dirname($path));

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        error_log("writeJson: JSON encode error: " . json_last_error_msg());
        return false;
    }

    // Write to a temp file then rename for atomicity
    $tmp = $path . '.tmp.' . bin2hex(random_bytes(4));
    $fp  = fopen($tmp, 'w');
    if ($fp === false) {
        error_log("writeJson: unable to open temp file {$tmp}");
        return false;
    }

    try {
        if (!flock($fp, LOCK_EX)) {
            error_log("writeJson: unable to acquire lock on {$tmp}");
            return false;
        }

        $written = fwrite($fp, $json);
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        if ($written === false) {
            @unlink($tmp);
            return false;
        }

        return rename($tmp, $path);
    } catch (\Throwable $e) {
        @fclose($fp);
        @unlink($tmp);
        error_log("writeJson: exception – " . $e->getMessage());
        return false;
    }
}

/**
 * Append an item to a JSON array file.
 *
 * If the file does not exist it is initialised as an empty array first.
 *
 * @param  string $path  Absolute path to the JSON array file.
 * @param  mixed  $item  The item to append.
 * @return bool          True on success.
 */
function appendJson(string $path, $item): bool
{
    $data = readJson($path);

    if (!is_array($data)) {
        $data = [];
    }

    $data[] = $item;

    return writeJson($path, $data);
}

/**
 * Ensure a directory exists, creating it recursively if needed.
 *
 * @param  string $path  Directory path.
 * @return bool          True if the directory exists (or was created).
 */
function ensureDir(string $path): bool
{
    if (is_dir($path)) {
        return true;
    }

    return mkdir($path, 0755, true);
}

/**
 * Generate a cryptographically secure random ID (16 hex characters).
 *
 * @return string  32-character hex string.
 */
function generateId(): string
{
    return bin2hex(random_bytes(16));
}

/**
 * List files in a directory that match a glob pattern.
 *
 * @param  string $dir     Directory path.
 * @param  string $pattern Glob pattern (e.g. "*.json").
 * @return array           Array of absolute file paths.
 */
function listFiles(string $dir, string $pattern = '*.json'): array
{
    if (!is_dir($dir)) {
        return [];
    }

    $files = glob(rtrim($dir, '/') . '/' . $pattern);
    return $files !== false ? $files : [];
}
