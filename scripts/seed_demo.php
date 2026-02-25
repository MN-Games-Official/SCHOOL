#!/usr/bin/env php
<?php
/**
 * Demo Mode Seeding Script
 * 
 * Creates a demo user account and sample data for testing.
 * Run: php scripts/seed_demo.php
 */

// Bootstrap the application
$rootDir = dirname(__DIR__);
require_once $rootDir . '/includes/config.php';
require_once $rootDir . '/includes/storage.php';
require_once $rootDir . '/includes/auth.php';

echo "=== SCHOOL AI — Demo Seeding Script ===\n\n";

// ── 1. Create demo user ─────────────────────────────────────────────────────

$demoUsername = 'demo';
$demoEmail    = 'demo@school-ai.local';
$demoPassword = 'demo1234';

echo "[1/5] Creating demo user account...\n";

$usersDir = DATA_DIR . '/users';
ensureDir($usersDir);

$existingUserId = null;
foreach (listFiles($usersDir, '*.json') as $file) {
    $u = readJson($file);
    if ($u && isset($u['username']) && strtolower($u['username']) === $demoUsername) {
        $existingUserId = $u['id'];
        break;
    }
}

if ($existingUserId) {
    $userId = $existingUserId;
    echo "  ✓ Demo user already exists (id: {$userId}). Skipping creation.\n";
} else {
    $userId = generateId();
    $user = [
        'id'           => $userId,
        'username'     => $demoUsername,
        'email'        => $demoEmail,
        'passwordHash' => password_hash($demoPassword, PASSWORD_BCRYPT, ['cost' => 12]),
        'createdAt'    => gmdate('c'),
        'settings'     => new stdClass(),
    ];
    if (writeJson($usersDir . '/' . $userId . '.json', $user)) {
        echo "  ✓ Created demo user (id: {$userId}).\n";
    } else {
        echo "  ✗ Failed to create demo user. Aborting.\n";
        exit(1);
    }
}

echo "  Credentials: username=\"demo\" password=\"demo1234\"\n\n";

// ── 2. Create sample lesson ─────────────────────────────────────────────────

echo "[2/5] Creating sample lesson (Algebra Basics)...\n";

$lessonsDir = DATA_DIR . '/lessons/' . $userId;
ensureDir($lessonsDir);

$lessonId = generateId();
$lesson = [
    'id'         => $lessonId,
    'userId'     => $userId,
    'topic'      => 'Algebra Basics',
    'subtopic'   => 'Solving Linear Equations',
    'level'      => 'Intermediate',
    'createdAt'  => gmdate('c'),
    'data'       => [
        'title'    => 'Algebra Basics — Solving Linear Equations',
        'schema_version' => '1.0',
        'overview' => 'This lesson introduces the fundamental concepts of algebra, focusing on variables, expressions, and solving linear equations.',
        'sections' => [
            [
                'heading' => 'Key Concepts',
                'body'    => '**Variables** represent unknown quantities, typically written as letters like $x$, $y$, or $z$.' . "\n\n" .
                             'An **equation** states that two expressions are equal: $2x + 3 = 11$.' . "\n\n" .
                             'The **goal** of solving an equation is to isolate the variable on one side.',
            ],
            [
                'heading' => 'Worked Examples',
                'body'    => '### Example 1' . "\n" . 'Solve $2x + 3 = 11$' . "\n\n" .
                             '$$2x + 3 = 11$$' . "\n" . '$$2x = 11 - 3 = 8$$' . "\n" . '$$x = \frac{8}{2} = 4$$' . "\n\n" .
                             '### Example 2' . "\n" . 'Solve $3(x - 2) = 12$' . "\n\n" .
                             '$$3x - 6 = 12$$' . "\n" . '$$3x = 18$$' . "\n" . '$$x = 6$$',
            ],
            [
                'heading' => 'Common Mistakes',
                'body'    => '- Forgetting to apply an operation to **both sides** of the equation.' . "\n" .
                             '- Sign errors when distributing negatives: $-(x - 3) = -x + 3$, not $-x - 3$.' . "\n" .
                             '- Dividing only one term by the coefficient instead of the entire side.',
            ],
            [
                'heading' => 'Quick Practice',
                'body'    => '1. Solve $5x - 7 = 18$' . "\n" .
                             '2. Solve $\frac{x}{3} + 4 = 10$' . "\n" .
                             '3. Solve $2(x + 5) = 3x - 1$',
            ],
        ],
    ],
];

if (writeJson($lessonsDir . '/' . $lessonId . '.json', $lesson)) {
    echo "  ✓ Created lesson (id: {$lessonId}).\n\n";
} else {
    echo "  ✗ Failed to create lesson.\n\n";
}

// ── 3. Create sample quiz ───────────────────────────────────────────────────

echo "[3/5] Creating sample quiz (Algebra Basics — 5 questions)...\n";

$quizzesDir = DATA_DIR . '/quizzes/' . $userId;
ensureDir($quizzesDir);

$quizId = generateId();
$quiz = [
    'id'            => $quizId,
    'userId'        => $userId,
    'topic'         => 'Algebra Basics',
    'subtopic'      => 'Linear Equations',
    'difficulty'    => 'Intermediate',
    'createdAt'     => gmdate('c'),
    'results'       => [
        'score'   => 80.0,
        'correct' => 4,
        'total'   => 5,
        'gradedAt'=> gmdate('c'),
        'details' => [],
    ],
    'data'          => [
        'schema_version' => '1.0',
        'questions' => [
            [
                'id'      => 'q1',
                'type'    => 'multiple_choice_4',
                'prompt_md'=> 'Solve for $x$: $2x + 5 = 13$',
                'options' => ['$x = 3$', '$x = 4$', '$x = 5$', '$x = 9$'],
                'correct' => '$x = 4$',
                'difficulty' => 'Beginner',
                'topicIds' => ['algebra-basics'],
                'domainTags' => ['Algebra'],
            ],
            [
                'id'      => 'q2',
                'type'    => 'multiple_choice_4',
                'prompt_md'=> 'Which property allows you to add the same value to both sides of an equation?',
                'options' => ['Distributive Property', 'Addition Property of Equality', 'Commutative Property', 'Associative Property'],
                'correct' => 'Addition Property of Equality',
                'difficulty' => 'Beginner',
                'topicIds' => ['algebra-basics'],
                'domainTags' => ['Algebra'],
            ],
            [
                'id'      => 'q3',
                'type'    => 'multiple_choice_4',
                'prompt_md'=> 'Simplify: $3(x + 4) - 2x$',
                'options' => ['$x + 12$', '$x + 4$', '$5x + 12$', '$5x + 4$'],
                'correct' => '$x + 12$',
                'difficulty' => 'Intermediate',
                'topicIds' => ['algebra-basics'],
                'domainTags' => ['Algebra'],
            ],
            [
                'id'      => 'q4',
                'type'    => 'multiple_choice_4',
                'prompt_md'=> 'Solve for $x$: $\\frac{x}{4} = 7$',
                'options' => ['$x = 11$', '$x = 1.75$', '$x = 28$', '$x = 3$'],
                'correct' => '$x = 28$',
                'difficulty' => 'Intermediate',
                'topicIds' => ['algebra-basics'],
                'domainTags' => ['Algebra'],
            ],
            [
                'id'      => 'q5',
                'type'    => 'multiple_choice_4',
                'prompt_md'=> 'What is the value of $x$ in $5x - 3 = 2x + 9$?',
                'options' => ['$x = 2$', '$x = 4$', '$x = 6$', '$x = 12$'],
                'correct' => '$x = 4$',
                'difficulty' => 'Moderate',
                'topicIds' => ['algebra-basics'],
                'domainTags' => ['Algebra'],
            ],
        ],
    ],
];

if (writeJson($quizzesDir . '/' . $quizId . '.json', $quiz)) {
    echo "  ✓ Created quiz (id: {$quizId}).\n\n";
} else {
    echo "  ✗ Failed to create quiz.\n\n";
}

// ── 4. Create sample writing document ───────────────────────────────────────

echo "[4/5] Creating sample writing document...\n";

$writingDir = DATA_DIR . '/writing/' . $userId;
ensureDir($writingDir);

$docId = generateId();
$document = [
    'id'        => $docId,
    'userId'    => $userId,
    'title'     => 'The Impact of Technology on Education',
    'createdAt' => gmdate('c'),
    'updatedAt' => gmdate('c'),
    'metadata'  => ['wordCount' => 192, 'docType' => 'essay'],
    'content'   => "# The Impact of Technology on Education\n\n" .
                   "## Introduction\n\n" .
                   "Technology has fundamentally transformed the landscape of modern education. From interactive " .
                   "whiteboards in classrooms to AI-powered tutoring systems, digital tools are reshaping how " .
                   "students learn and how teachers teach.\n\n" .
                   "## Body\n\n" .
                   "### Accessibility\n\n" .
                   "One of the most significant benefits of educational technology is increased accessibility. " .
                   "Students in remote areas can now access world-class lectures through platforms like Khan Academy " .
                   "and Coursera. This democratization of knowledge has the potential to reduce educational inequality " .
                   "on a global scale.\n\n" .
                   "### Personalized Learning\n\n" .
                   "Adaptive learning systems can tailor content to each student's pace and skill level. Rather than " .
                   "a one-size-fits-all curriculum, students receive targeted practice on concepts they find " .
                   "challenging while advancing quickly through material they have mastered.\n\n" .
                   "### Concerns\n\n" .
                   "However, technology in education is not without drawbacks. Screen fatigue, data privacy concerns, " .
                   "and the digital divide remain pressing issues that educators and policymakers must address.\n\n" .
                   "## Conclusion\n\n" .
                   "While technology offers tremendous opportunities for enhancing education, its implementation must " .
                   "be thoughtful and equitable. The goal should be to use technology as a tool that empowers both " .
                   "students and teachers, not as a replacement for human connection in learning.\n",
    'snapshots' => [
        [
            'id'        => generateId(),
            'createdAt' => gmdate('c'),
            'wordCount' => 192,
            'label'     => 'Initial draft',
        ],
    ],
    'aiInteractions' => [],
];

if (writeJson($writingDir . '/' . $docId . '.json', $document)) {
    echo "  ✓ Created writing document (id: {$docId}).\n\n";
} else {
    echo "  ✗ Failed to create writing document.\n\n";
}

// ── 5. Create sample exam attempt metadata ──────────────────────────────────

echo "[5/5] Creating sample exam attempt metadata...\n";

$examsDir = DATA_DIR . '/exams/' . $userId;
ensureDir($examsDir);

$examId = generateId();
$exam = [
    'id'          => $examId,
    'userId'      => $userId,
    'examType'    => 'ACT',
    'createdAt'   => gmdate('c'),
    'completedAt' => gmdate('c'),
    'status'      => 'completed',
    'sections'    => [
        [
            'name'         => 'English',
            'questionCount' => 75,
            'timeLimitSec' => 2700,
            'completed'    => true,
            'rawScore'     => 58,
            'scaledScore'  => 25,
        ],
        [
            'name'         => 'Math',
            'questionCount' => 60,
            'timeLimitSec' => 3600,
            'completed'    => true,
            'rawScore'     => 42,
            'scaledScore'  => 24,
        ],
        [
            'name'         => 'Reading',
            'questionCount' => 40,
            'timeLimitSec' => 2100,
            'completed'    => true,
            'rawScore'     => 30,
            'scaledScore'  => 26,
        ],
        [
            'name'         => 'Science',
            'questionCount' => 40,
            'timeLimitSec' => 2100,
            'completed'    => true,
            'rawScore'     => 28,
            'scaledScore'  => 23,
        ],
    ],
    'compositeScore' => 25,
    'estimatedRange' => '23–27',
];

if (writeJson($examsDir . '/' . $examId . '.json', $exam)) {
    echo "  ✓ Created exam attempt (id: {$examId}).\n\n";
} else {
    echo "  ✗ Failed to create exam attempt.\n\n";
}

// ── Done ─────────────────────────────────────────────────────────────────────

echo "=== Demo seeding complete! ===\n";
echo "Log in with username \"demo\" and password \"demo1234\".\n";
