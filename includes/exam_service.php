<?php
/**
 * Exam Service
 *
 * Provides exam configuration for standardised tests (ACT, SAT, PreACT,
 * MCA) and scaled-score estimation utilities.
 *
 * @package School
 */

/**
 * Get the full configuration for a supported exam type.
 *
 * @param  string $examType  One of: ACT, SAT, PreACT, MCA.
 * @return array|null        Configuration array or null if unsupported.
 */
function getExamConfig(string $examType): ?array
{
    $configs = [
        'ACT' => [
            'name'     => 'ACT',
            'sections' => [
                [
                    'name'         => 'English',
                    'num_questions' => [45, 50],
                    'time_minutes'  => 35,
                    'passage_based' => true,
                    'question_types'=> ['multiple_choice'],
                    'subject_area'  => 'English grammar, punctuation, sentence structure, rhetoric',
                ],
                [
                    'name'         => 'Math',
                    'num_questions' => [45, 60],
                    'time_minutes'  => [50, 60],
                    'passage_based' => false,
                    'question_types'=> ['multiple_choice'],
                    'subject_area'  => 'Pre-algebra, algebra, geometry, trigonometry',
                ],
                [
                    'name'         => 'Reading',
                    'num_questions' => [36, 40],
                    'time_minutes'  => 40,
                    'passage_based' => true,
                    'question_types'=> ['multiple_choice'],
                    'subject_area'  => 'Reading comprehension, prose fiction, social science, humanities, natural science',
                ],
                [
                    'name'         => 'Science',
                    'num_questions' => [40, 40],
                    'time_minutes'  => 40,
                    'passage_based' => true,
                    'question_types'=> ['multiple_choice'],
                    'subject_area'  => 'Data representation, research summaries, conflicting viewpoints',
                ],
            ],
            'score_range' => [1, 36],
        ],

        'SAT' => [
            'name'     => 'SAT',
            'sections' => [
                [
                    'name'         => 'Reading & Writing Module 1',
                    'num_questions' => [27, 27],
                    'time_minutes'  => 32,
                    'passage_based' => true,
                    'question_types'=> ['multiple_choice'],
                    'subject_area'  => 'Reading comprehension, vocabulary, grammar, expression',
                ],
                [
                    'name'         => 'Reading & Writing Module 2',
                    'num_questions' => [27, 27],
                    'time_minutes'  => 32,
                    'passage_based' => true,
                    'question_types'=> ['multiple_choice'],
                    'subject_area'  => 'Reading comprehension, vocabulary, grammar, expression',
                ],
                [
                    'name'         => 'Math Module 1',
                    'num_questions' => [22, 22],
                    'time_minutes'  => 35,
                    'passage_based' => false,
                    'question_types'=> ['multiple_choice', 'grid_in'],
                    'subject_area'  => 'Algebra, advanced math, problem solving, geometry, trigonometry',
                ],
                [
                    'name'         => 'Math Module 2',
                    'num_questions' => [22, 22],
                    'time_minutes'  => 35,
                    'passage_based' => false,
                    'question_types'=> ['multiple_choice', 'grid_in'],
                    'subject_area'  => 'Algebra, advanced math, problem solving, geometry, trigonometry',
                ],
            ],
            'score_range' => [400, 1600],
        ],

        'PreACT' => [
            'name'     => 'PreACT',
            'sections' => [
                [
                    'name'         => 'English',
                    'num_questions' => [45, 45],
                    'time_minutes'  => 30,
                    'passage_based' => true,
                    'question_types'=> ['multiple_choice'],
                    'subject_area'  => 'English grammar, punctuation, sentence structure, rhetoric',
                ],
                [
                    'name'         => 'Math',
                    'num_questions' => [36, 36],
                    'time_minutes'  => 40,
                    'passage_based' => false,
                    'question_types'=> ['multiple_choice'],
                    'subject_area'  => 'Pre-algebra, algebra, geometry',
                ],
                [
                    'name'         => 'Reading',
                    'num_questions' => [25, 25],
                    'time_minutes'  => 30,
                    'passage_based' => true,
                    'question_types'=> ['multiple_choice'],
                    'subject_area'  => 'Reading comprehension',
                ],
                [
                    'name'         => 'Science',
                    'num_questions' => [30, 30],
                    'time_minutes'  => 30,
                    'passage_based' => true,
                    'question_types'=> ['multiple_choice'],
                    'subject_area'  => 'Data representation, research summaries',
                ],
            ],
            'score_range' => [1, 36],
        ],

        'MCA' => [
            'name'     => 'MCA',
            'sections' => [
                [
                    'name'          => 'Mathematics (Grade 3-8)',
                    'num_questions' => [45, 55],
                    'time_minutes'  => 90,
                    'passage_based' => false,
                    'question_types'=> ['multiple_choice', 'short_answer'],
                    'subject_area'  => 'Number sense, algebra, geometry, data analysis',
                ],
                [
                    'name'          => 'Mathematics (Grade 11)',
                    'num_questions' => [55, 65],
                    'time_minutes'  => 120,
                    'passage_based' => false,
                    'question_types'=> ['multiple_choice', 'short_answer'],
                    'subject_area'  => 'Algebra, functions, geometry, statistics',
                ],
                [
                    'name'          => 'Reading (Grade 3-8)',
                    'num_questions' => [40, 50],
                    'time_minutes'  => 80,
                    'passage_based' => true,
                    'question_types'=> ['multiple_choice'],
                    'subject_area'  => 'Reading comprehension, vocabulary, literary analysis',
                ],
                [
                    'name'          => 'Reading (Grade 10)',
                    'num_questions' => [50, 55],
                    'time_minutes'  => 100,
                    'passage_based' => true,
                    'question_types'=> ['multiple_choice'],
                    'subject_area'  => 'Reading comprehension, analysis, synthesis',
                ],
                [
                    'name'          => 'Science (Grade 5, 8)',
                    'num_questions' => [45, 50],
                    'time_minutes'  => 80,
                    'passage_based' => true,
                    'question_types'=> ['multiple_choice', 'short_answer'],
                    'subject_area'  => 'Life science, earth science, physical science',
                ],
                [
                    'name'          => 'Science (High School)',
                    'num_questions' => [50, 60],
                    'time_minutes'  => 100,
                    'passage_based' => true,
                    'question_types'=> ['multiple_choice', 'short_answer'],
                    'subject_area'  => 'Biology, chemistry, physics, earth science',
                ],
            ],
            'score_range' => [200, 399],
        ],
    ];

    return $configs[$examType] ?? null;
}

/**
 * Estimate a scaled score range from a raw score.
 *
 * Uses a simple linear interpolation against known score ranges.
 * Real score conversions use equating tables; this is an approximation.
 *
 * @param  string $examType        Exam type identifier.
 * @param  int    $rawScore        Number of correct answers.
 * @param  int    $totalQuestions   Total number of questions.
 * @return array|null              ['low' => int, 'high' => int, 'scale' => string] or null.
 */
function estimateScaledScore(string $examType, int $rawScore, int $totalQuestions): ?array
{
    if ($totalQuestions <= 0) {
        return null;
    }

    $pct = $rawScore / $totalQuestions;

    switch ($examType) {
        case 'SAT':
            // SAT: 400-1600 composite scale
            $low  = (int) round(400 + ($pct * 1200) - 30);
            $high = (int) round(400 + ($pct * 1200) + 30);
            return [
                'low'   => max(400, $low),
                'high'  => min(1600, $high),
                'scale' => '400-1600',
            ];

        case 'ACT':
        case 'PreACT':
            // ACT / PreACT: 1-36 composite scale
            $low  = (int) round(1 + ($pct * 35) - 1);
            $high = (int) round(1 + ($pct * 35) + 1);
            return [
                'low'   => max(1, $low),
                'high'  => min(36, $high),
                'scale' => '1-36',
            ];

        case 'MCA':
            // MCA: 200-399 scale
            $low  = (int) round(200 + ($pct * 199) - 10);
            $high = (int) round(200 + ($pct * 199) + 10);
            return [
                'low'   => max(200, $low),
                'high'  => min(399, $high),
                'scale' => '200-399',
            ];

        default:
            return null;
    }
}
