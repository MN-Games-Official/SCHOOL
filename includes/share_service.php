<?php
/**
 * Share Service
 *
 * Manages password-protected share tokens for documents, quizzes,
 * and other resources.
 *
 * @package School
 */

/**
 * Create a share link for a resource.
 *
 * @param  string      $userId    Owner user ID.
 * @param  string      $docId     Resource ID to share.
 * @param  string|null $password  Optional password (will be hashed).
 * @return array                  ['success' => true, 'share' => ...] or ['error' => '...']
 */
function createShare(string $userId, string $docId, ?string $password = null): array
{
    $shareId = generateId();
    $now     = gmdate('c');

    $share = [
        'id'           => $shareId,
        'userId'       => $userId,
        'resourceId'   => $docId,
        'passwordHash' => $password ? password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]) : null,
        'createdAt'    => $now,
        'expiresAt'    => null,
        'viewCount'    => 0,
    ];

    $dir  = DATA_DIR . '/shares';
    $path = $dir . '/' . $shareId . '.json';

    ensureDir($dir);
    if (!writeJson($path, $share)) {
        return ['error' => 'Failed to create share link.'];
    }

    return [
        'success' => true,
        'share'   => [
            'id'           => $shareId,
            'resourceId'   => $docId,
            'hasPassword'  => $password !== null,
            'createdAt'    => $now,
            'url'          => '/share/' . $shareId,
        ],
    ];
}

/**
 * Verify access to a share (check password if set).
 *
 * @param  string      $shareId   Share token ID.
 * @param  string|null $password  Password attempt (null if not required).
 * @return array                  ['success' => true] or ['error' => '...']
 */
function verifyShare(string $shareId, ?string $password = null): array
{
    $path  = DATA_DIR . '/shares/' . $shareId . '.json';
    $share = readJson($path);

    if (!$share) {
        return ['error' => 'Share not found.'];
    }

    // Check expiry
    if ($share['expiresAt'] && strtotime($share['expiresAt']) < time()) {
        return ['error' => 'This share link has expired.'];
    }

    // Check password
    if ($share['passwordHash']) {
        if (!$password || !password_verify($password, $share['passwordHash'])) {
            return ['error' => 'Incorrect password.'];
        }
    }

    // Increment view count
    $share['viewCount'] = ($share['viewCount'] ?? 0) + 1;
    writeJson($path, $share);

    return ['success' => true, 'share' => $share];
}

/**
 * Get the data associated with a share (the actual resource content).
 *
 * @param  string $shareId  Share token ID.
 * @return array|null       Share record with resource data, or null.
 */
function getShareData(string $shareId): ?array
{
    $path  = DATA_DIR . '/shares/' . $shareId . '.json';
    $share = readJson($path);

    if (!$share) {
        return null;
    }

    // Load the referenced resource – try writing docs first, then quizzes/lessons
    $resourceId = $share['resourceId'];
    $userId     = $share['userId'];

    $resource = null;

    // Try writing document
    $writingPath = DATA_DIR . '/writing/' . $userId . '/' . $resourceId . '.json';
    if (file_exists($writingPath)) {
        $resource = readJson($writingPath);
        if ($resource) {
            $resource['_type'] = 'writing';
        }
    }

    // Try quiz
    if (!$resource) {
        $quizPath = DATA_DIR . '/quizzes/' . $userId . '/' . $resourceId . '.json';
        if (file_exists($quizPath)) {
            $resource = readJson($quizPath);
            if ($resource) {
                $resource['_type'] = 'quiz';
            }
        }
    }

    // Try lesson
    if (!$resource) {
        $lessonPath = DATA_DIR . '/lessons/' . $userId . '/' . $resourceId . '.json';
        if (file_exists($lessonPath)) {
            $resource = readJson($lessonPath);
            if ($resource) {
                $resource['_type'] = 'lesson';
            }
        }
    }

    // Try exam
    if (!$resource) {
        $examPath = DATA_DIR . '/exams/' . $userId . '/' . $resourceId . '.json';
        if (file_exists($examPath)) {
            $resource = readJson($examPath);
            if ($resource) {
                $resource['_type'] = 'exam';
            }
        }
    }

    return [
        'share'    => $share,
        'resource' => $resource,
    ];
}
