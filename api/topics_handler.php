<?php
/**
 * API Handler – Topics
 *
 * Handles GET /api/topics – returns the available topic list.
 *
 * @package School
 */

/**
 * Dispatch topics actions.
 *
 * @param  string $action  One of: list.
 * @param  array  $params  Route parameters (unused).
 * @return void
 */
function handle_topics_handler(string $action, array $params): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        jsonError('Method not allowed', 405);
    }

    switch ($action) {
        case 'list':
            _handleTopicsList();
            break;
        default:
            jsonError('Unknown topics action', 400);
    }
}

/**
 * Return the master topics list.
 *
 * Topics are stored as a single JSON file in /data/topics/topics.json.
 * If it does not exist yet, a default set is returned.
 *
 * @return void
 */
function _handleTopicsList(): void
{
    $path = DATA_DIR . '/topics/topics.json';
    $data = readJson($path);

    // The topics file has a wrapper with schema_version, lastUpdated, topics[]
    $topics = [];
    if ($data && isset($data['topics']) && is_array($data['topics'])) {
        $topics = $data['topics'];
    }

    if (empty($topics)) {
        // Provide sensible defaults when no topic file exists yet
        $topics = [
            ['id' => 'math',       'name' => 'Mathematics',       'subject' => 'Mathematics',  'tags' => ['math']],
            ['id' => 'science',    'name' => 'Science',           'subject' => 'Science',      'tags' => ['science']],
            ['id' => 'english',    'name' => 'English',           'subject' => 'English',      'tags' => ['english']],
            ['id' => 'history',    'name' => 'History',           'subject' => 'History',      'tags' => ['history']],
            ['id' => 'cs',         'name' => 'Computer Science',  'subject' => 'CS',           'tags' => ['cs', 'programming']],
            ['id' => 'languages',  'name' => 'World Languages',   'subject' => 'Languages',    'tags' => ['languages']],
        ];
    }

    jsonResponse(['topics' => $topics]);
}
