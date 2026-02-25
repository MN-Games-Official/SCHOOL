<?php
/**
 * API Handler – Dashboard
 *
 * Returns aggregated statistics for the authenticated user's dashboard.
 *
 * @package School
 */

/**
 * Dispatch dashboard actions.
 *
 * @param  string $action  One of: stats.
 * @param  array  $params  Route parameters (unused).
 * @return void
 */
function handle_dashboard_handler(string $action, array $params): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        jsonError('Method not allowed', 405);
    }

    $user = requireAuth();

    switch ($action) {
        case 'stats':
            _handleDashboardStats($user);
            break;
        default:
            jsonError('Unknown dashboard action', 400);
    }
}

/**
 * Collect and return dashboard statistics.
 *
 * Gathers counts and recent-activity data from lessons, quizzes, exams,
 * and writing documents.
 *
 * @param  array $user  Current user.
 * @return void
 */
function _handleDashboardStats(array $user): void
{
    $userId = $user['id'];

    // ── Lessons ──────────────────────────────────────────────────────────────
    $lessonFiles = listFiles(DATA_DIR . '/lessons/' . $userId, '*.json');
    $lessonCount = count($lessonFiles);

    // ── Quizzes ──────────────────────────────────────────────────────────────
    $quizFiles  = listFiles(DATA_DIR . '/quizzes/' . $userId, '*.json');
    $quizCount  = count($quizFiles);
    $quizScores = [];

    foreach ($quizFiles as $file) {
        $quiz = readJson($file);
        if ($quiz && isset($quiz['results']['score'])) {
            $quizScores[] = $quiz['results']['score'];
        }
    }

    $avgQuizScore = !empty($quizScores)
        ? round(array_sum($quizScores) / count($quizScores), 1)
        : null;

    // ── Exams ────────────────────────────────────────────────────────────────
    $examFiles = listFiles(DATA_DIR . '/exams/' . $userId, '*.json');
    $examCount = count($examFiles);

    // ── Writing ──────────────────────────────────────────────────────────────
    $writingFiles = listFiles(DATA_DIR . '/writing/' . $userId, '*.json');
    $writingCount = count($writingFiles);
    $totalWords   = 0;

    foreach ($writingFiles as $file) {
        $doc = readJson($file);
        if ($doc && isset($doc['metadata']['wordCount'])) {
            $totalWords += (int) $doc['metadata']['wordCount'];
        }
    }

    // ── Recent Activity (last 5 items across all types) ──────────────────────
    $recent = [];

    foreach (array_slice($lessonFiles, -5) as $f) {
        $l = readJson($f);
        if ($l) {
            $recent[] = ['type' => 'lesson', 'title' => $l['data']['title'] ?? $l['topic'], 'date' => $l['createdAt']];
        }
    }
    foreach (array_slice($quizFiles, -5) as $f) {
        $q = readJson($f);
        if ($q) {
            $recent[] = ['type' => 'quiz', 'title' => $q['topic'], 'date' => $q['createdAt']];
        }
    }
    foreach (array_slice($examFiles, -5) as $f) {
        $e = readJson($f);
        if ($e) {
            $recent[] = ['type' => 'exam', 'title' => $e['examType'], 'date' => $e['createdAt']];
        }
    }
    foreach (array_slice($writingFiles, -5) as $f) {
        $d = readJson($f);
        if ($d) {
            $recent[] = ['type' => 'writing', 'title' => $d['title'], 'date' => $d['updatedAt']];
        }
    }

    // Sort by date descending, take top 10
    usort($recent, fn($a, $b) => strcmp($b['date'], $a['date']));
    $recent = array_slice($recent, 0, 10);

    jsonResponse([
        'stats' => [
            'lessons'       => $lessonCount,
            'quizzes'       => $quizCount,
            'avgQuizScore'  => $avgQuizScore,
            'exams'         => $examCount,
            'documents'     => $writingCount,
            'totalWords'    => $totalWords,
        ],
        'recentActivity' => $recent,
    ]);
}
