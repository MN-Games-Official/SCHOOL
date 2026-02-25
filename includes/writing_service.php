<?php
/**
 * Writing Document Service
 *
 * Manages creation, saving, snapshots, and integrity logging for
 * student writing documents.
 *
 * @package School
 */

/**
 * Create a new writing document for a user.
 *
 * @param  string $userId  Owner user ID.
 * @param  string $title   Document title.
 * @return array           ['success' => true, 'document' => ...] or ['error' => '...']
 */
function createDocument(string $userId, string $title): array
{
    $title = trim($title);
    if (empty($title)) {
        return ['error' => 'Document title is required.'];
    }

    $docId = generateId();
    $now   = gmdate('c');

    $document = [
        'id'         => $docId,
        'userId'     => $userId,
        'title'      => $title,
        'content'    => '',
        'metadata'   => [
            'wordCount'   => 0,
            'charCount'   => 0,
            'docType'     => 'essay',
        ],
        'createdAt'  => $now,
        'updatedAt'  => $now,
    ];

    $dir  = DATA_DIR . '/writing/' . $userId;
    $path = $dir . '/' . $docId . '.json';

    ensureDir($dir);
    if (!writeJson($path, $document)) {
        return ['error' => 'Failed to create document.'];
    }

    // Initial snapshot
    saveSnapshot($userId, $docId, '', 'created');

    return ['success' => true, 'document' => $document];
}

/**
 * Save document content and metadata.
 *
 * @param  string $userId    Owner user ID.
 * @param  string $docId     Document ID.
 * @param  string $content   Document content.
 * @param  array  $metadata  Optional metadata overrides.
 * @return array             ['success' => true] or ['error' => '...']
 */
function saveDocument(string $userId, string $docId, string $content, array $metadata = []): array
{
    $path = DATA_DIR . '/writing/' . $userId . '/' . $docId . '.json';
    $doc  = readJson($path);

    if (!$doc) {
        return ['error' => 'Document not found.'];
    }

    if ($doc['userId'] !== $userId) {
        return ['error' => 'Access denied.'];
    }

    $doc['content']   = $content;
    $doc['updatedAt'] = gmdate('c');

    // Merge metadata
    $doc['metadata']['wordCount'] = str_word_count(strip_tags($content));
    $doc['metadata']['charCount'] = mb_strlen($content, 'UTF-8');
    $doc['metadata'] = array_merge($doc['metadata'], $metadata);

    if (!writeJson($path, $doc)) {
        return ['error' => 'Failed to save document.'];
    }

    return ['success' => true, 'document' => $doc];
}

/**
 * Save a revision snapshot of the document.
 *
 * @param  string $userId     Owner user ID.
 * @param  string $docId      Document ID.
 * @param  string $content    Content at this point in time.
 * @param  string $changeType Type of change (e.g. "manual_save", "auto_save", "created").
 * @return bool               True on success.
 */
function saveSnapshot(string $userId, string $docId, string $content, string $changeType): bool
{
    $dir = DATA_DIR . '/writing/' . $userId . '/snapshots/' . $docId;
    ensureDir($dir);

    $snapshot = [
        'id'         => generateId(),
        'docId'      => $docId,
        'userId'     => $userId,
        'content'    => $content,
        'changeType' => $changeType,
        'wordCount'  => str_word_count(strip_tags($content)),
        'timestamp'  => gmdate('c'),
    ];

    $path = $dir . '/' . $snapshot['id'] . '.json';
    return writeJson($path, $snapshot);
}

/**
 * Retrieve the integrity log for a document.
 *
 * @param  string $userId  Owner user ID.
 * @param  string $docId   Document ID.
 * @return array           Array of integrity events.
 */
function getIntegrityLog(string $userId, string $docId): array
{
    $path = DATA_DIR . '/integrity/' . $userId . '/' . $docId . '.json';
    $log  = readJson($path);

    return is_array($log) ? $log : [];
}

/**
 * Log a writing integrity event (e.g. paste, focus loss, tab switch).
 *
 * @param  string $userId  Owner user ID.
 * @param  string $docId   Document ID.
 * @param  array  $event   Event data (type, timestamp, details, etc.).
 * @return bool            True on success.
 */
function logIntegrityEvent(string $userId, string $docId, array $event): bool
{
    $dir  = DATA_DIR . '/integrity/' . $userId;
    $path = $dir . '/' . $docId . '.json';

    ensureDir($dir);

    $event['loggedAt'] = gmdate('c');

    return appendJson($path, $event);
}
