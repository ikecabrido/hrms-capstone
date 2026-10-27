<?php
/**
 * AICourseBlueprint - validation and deterministic quality checks for generated
 * course blueprints.
 *
 * The class is deliberately independent of the provider. It accepts decoded
 * JSON, normalizes it, rejects unsupported or unsafe structures, and keeps
 * model assumptions/warnings separate from trainee-facing content.
 */
class AICourseBlueprint
{
    private array $settings;
    /** @var array<int, array{id: int, name: string}> */
    private array $activeSkills;
    /** @var array<int, array{id: int, name: string}> */
    private array $activeById;
    /** @var array<int, int>|null */
    private ?array $allowedSkillIds;
    private array $brief;

    /**
     * @param array<string, mixed> $settings
     * @param array<int, array<string, mixed>> $activeSkills
     * @param array<string, mixed> $brief
     * @param array<int, mixed>|null $allowedSkillIds Null allows every active skill
     *        for backwards compatibility. An empty array allows no skill links.
     */
    public function __construct(array $settings, array $activeSkills, array $brief, ?array $allowedSkillIds = null)
    {
        $this->settings = $settings;
        $this->brief = $brief;
        $this->activeSkills = [];
        $this->activeById = [];
        $this->allowedSkillIds = null;
        if ($allowedSkillIds !== null) {
            $this->allowedSkillIds = [];
            foreach ($allowedSkillIds as $value) {
                if (!is_int($value) && !(is_string($value) && ctype_digit($value))) {
                    continue;
                }
                $id = (int) $value;
                if ($id > 0) {
                    $this->allowedSkillIds[$id] = $id;
                }
            }
        }

        foreach ($activeSkills as $skill) {
            if (!is_array($skill)) {
                continue;
            }
            $id = (int) ($skill['id'] ?? 0);
            $name = trim((string) ($skill['name'] ?? ''));
            if ($id <= 0 || $name === '') {
                continue;
            }
            $entry = ['id' => $id, 'name' => $name];
            $this->activeSkills[] = $entry;
            $this->activeById[$id] = $entry;
        }
    }

    /**
     * @return array{success: bool, message: string, outline: array<string, mixed>|null, errors: array<int, string>, warnings: array<int, string>}
     */
    public function validateOutline(mixed $decoded): array
    {
        $decoded = $this->unwrap($decoded, 'outline');
        if (!is_array($decoded)) {
            return $this->failure('The outline was not a JSON object.');
        }

        $errors = [];
        $warnings = [];
        $courseRaw = is_array($decoded['course'] ?? null) ? $decoded['course'] : $decoded;
        $modulesRaw = $decoded['modules'] ?? $courseRaw['modules'] ?? null;

        $title = $this->titleText($courseRaw['title'] ?? '', 3, 255, 'Course title', $errors);
        $description = $this->bodyText($courseRaw['description'] ?? '', 10, 2000, 'Course description', $errors);
        $category = $this->titleText($courseRaw['category'] ?? '', 0, 100, 'Course category', $errors);
        $deliveryMode = strtolower(trim((string) ($courseRaw['delivery_mode'] ?? 'online')));
        if (!in_array($deliveryMode, ['online', 'face_to_face', 'hybrid'], true)) {
            $errors[] = 'Course delivery mode must be online, face_to_face, or hybrid.';
            $deliveryMode = 'online';
        }

        $courseSkillIds = $this->skillIds(
            $courseRaw['skill_ids'] ?? [],
            $errors,
            'course.skill_ids',
            $this->briefSelectedSkillIds()
        );

        $modules = [];
        $moduleTitles = [];
        $moduleKeys = [];
        $moduleCount = is_array($modulesRaw) ? count($modulesRaw) : 0;
        $maxModules = $this->intSetting('max_modules', 8, 1, 20);
        $maxLessons = $this->intSetting('max_lessons_per_module', 6, 1, 20);

        if (!is_array($modulesRaw) || $moduleCount < 1) {
            $errors[] = 'The outline must contain at least one module.';
        } elseif ($moduleCount > $maxModules) {
            $errors[] = "The outline contains {$moduleCount} modules; the maximum is {$maxModules}.";
        }

        if (is_array($modulesRaw)) {
            foreach (array_values($modulesRaw) as $index => $moduleRaw) {
                if (!is_array($moduleRaw)) {
                    $errors[] = 'Module ' . ($index + 1) . ' is not a JSON object.';
                    continue;
                }

                $moduleNumber = $index + 1;
                $moduleTitle = $this->titleText(
                    $moduleRaw['title'] ?? '',
                    3,
                    255,
                    "Module {$moduleNumber} title",
                    $errors
                );
                $moduleDescription = $this->bodyText(
                    $moduleRaw['description'] ?? '',
                    0,
                    2000,
                    "Module {$moduleNumber} description",
                    $errors
                );
                $moduleKey = $this->key($moduleRaw['key'] ?? '', 'module-' . $moduleNumber);
                if (isset($moduleKeys[$moduleKey])) {
                    $errors[] = "Module key {$moduleKey} is duplicated.";
                }
                $moduleKeys[$moduleKey] = true;

                $titleKey = mb_strtolower($moduleTitle);
                if ($titleKey !== '' && isset($moduleTitles[$titleKey])) {
                    $errors[] = "Module title \"{$moduleTitle}\" is duplicated.";
                }
                $moduleTitles[$titleKey] = true;

                $moduleSkillIds = $this->skillIds(
                    $moduleRaw['skill_ids'] ?? [],
                    $errors,
                    "modules[{$index}].skill_ids",
                    $courseSkillIds
                );

                $lessonsRaw = $moduleRaw['lessons'] ?? null;
                $lessonCount = is_array($lessonsRaw) ? count($lessonsRaw) : 0;
                if (!is_array($lessonsRaw) || $lessonCount < 1) {
                    $errors[] = "Module {$moduleNumber} must contain at least one lesson.";
                } elseif ($lessonCount > $maxLessons) {
                    $errors[] = "Module {$moduleNumber} contains {$lessonCount} lessons; the maximum is {$maxLessons}.";
                }

                $lessons = [];
                $lessonTitles = [];
                $lessonKeys = [];
                if (is_array($lessonsRaw)) {
                    foreach (array_values($lessonsRaw) as $lessonIndex => $lessonRaw) {
                        if (!is_array($lessonRaw)) {
                            $errors[] = "Module {$moduleNumber} lesson " . ($lessonIndex + 1) . ' is not a JSON object.';
                            continue;
                        }
                        $lessonNumber = $lessonIndex + 1;
                        $lessonTitle = $this->titleText(
                            $lessonRaw['title'] ?? '',
                            3,
                            255,
                            "Module {$moduleNumber} lesson {$lessonNumber} title",
                            $errors
                        );
                        $lessonTitleKey = mb_strtolower($lessonTitle);
                        if ($lessonTitleKey !== '' && isset($lessonTitles[$lessonTitleKey])) {
                            $errors[] = "Module {$moduleNumber} has a duplicate lesson title \"{$lessonTitle}\".";
                        }
                        $lessonTitles[$lessonTitleKey] = true;

                        $lessonKey = $this->key($lessonRaw['key'] ?? '', 'lesson-' . $lessonNumber);
                        if (isset($lessonKeys[$lessonKey])) {
                            $errors[] = "Module {$moduleNumber} lesson key {$lessonKey} is duplicated.";
                        }
                        $lessonKeys[$lessonKey] = true;

                        $lessons[] = [
                            'key' => $lessonKey,
                            'title' => $lessonTitle,
                            'objectives' => $this->stringList(
                                $lessonRaw['objectives'] ?? [],
                                8,
                                300
                            ),
                            'estimated_minutes' => $this->boundedInt(
                                $lessonRaw['estimated_minutes'] ?? 20,
                                5,
                                600,
                                20
                            ),
                            'order_index' => $lessonNumber,
                        ];
                    }
                }

                $modules[] = [
                    'key' => $moduleKey,
                    'title' => $moduleTitle,
                    'description' => $moduleDescription,
                    'skill_ids' => $moduleSkillIds,
                    'skills' => $this->skillsForIds($moduleSkillIds),
                    'lessons' => $lessons,
                    'assumptions' => $this->stringList($moduleRaw['assumptions'] ?? [], 10, 500),
                    'warnings' => $this->stringList($moduleRaw['warnings'] ?? [], 10, 500),
                    'order_index' => $moduleNumber,
                ];

                if ($lessonCount > 0 && $lessonCount !== $this->briefLessonsPerModule()) {
                    $warnings[] = "Module {$moduleNumber} has {$lessonCount} lessons; the brief requested "
                        . $this->briefLessonsPerModule() . '.';
                }
            }
        }

        if ($moduleCount > 0 && $moduleCount !== $this->briefModuleCount()) {
            $warnings[] = "The outline has {$moduleCount} modules; the brief requested "
                . $this->briefModuleCount() . '.';
        }

        if ($errors !== []) {
            return $this->failure('The outline did not pass validation.', $errors, $warnings);
        }

        $outline = [
            'version' => 1,
            'course' => [
                'title' => $title,
                'description' => $description,
                'category' => $category,
                'delivery_mode' => $deliveryMode,
                'difficulty' => $this->briefDifficulty(),
                'skill_ids' => $courseSkillIds,
                'skills' => $this->skillsForIds($courseSkillIds),
                'assumptions' => $this->stringList($courseRaw['assumptions'] ?? [], 10, 500),
                'warnings' => $this->stringList($courseRaw['warnings'] ?? [], 10, 500),
            ],
            'modules' => $modules,
            'warnings' => array_values(array_unique($warnings)),
        ];

        return [
            'success' => true,
            'message' => 'Outline validated.',
            'outline' => $outline,
            'errors' => [],
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    /**
     * @param array<string, mixed> $outlineModule
     * @param array<string, mixed> $course
     * @return array{success: bool, message: string, module: array<string, mixed>|null, errors: array<int, string>, warnings: array<int, string>}
     */
    public function validateModule(mixed $decoded, array $outlineModule, array $course): array
    {
        $decoded = $this->unwrap($decoded, 'module');
        if (!is_array($decoded)) {
            return $this->failure('The module was not a JSON object.');
        }

        $errors = [];
        $warnings = [];
        $outlineLessons = is_array($outlineModule['lessons'] ?? null) ? $outlineModule['lessons'] : [];
        $expectedLessons = count($outlineLessons);
        $lessonsRaw = $decoded['lessons'] ?? null;
        $actualLessons = is_array($lessonsRaw) ? count($lessonsRaw) : 0;

        if (!is_array($lessonsRaw) || $actualLessons !== $expectedLessons) {
            $errors[] = "The module must contain exactly {$expectedLessons} lessons; the response contained {$actualLessons}.";
        }

        $lessons = [];
        if (is_array($lessonsRaw)) {
            foreach (array_values($outlineLessons) as $index => $outlineLesson) {
                if (!is_array($outlineLesson)) {
                    continue;
                }
                $lessonRaw = $lessonsRaw[$index] ?? null;
                if (!is_array($lessonRaw)) {
                    $errors[] = 'Lesson ' . ($index + 1) . ' is missing from the module response.';
                    continue;
                }

                $lessonNumber = $index + 1;
                $lessonTitle = $this->titleText(
                    $lessonRaw['title'] ?? ($outlineLesson['title'] ?? ''),
                    3,
                    255,
                    "Lesson {$lessonNumber} title",
                    $errors
                );
                $content = $this->bodyText(
                    $lessonRaw['content_markdown'] ?? '',
                    40,
                    20000,
                    "Lesson {$lessonNumber} content",
                    $errors
                );
                $lessons[] = [
                    'key' => (string) ($outlineLesson['key'] ?? ('lesson-' . $lessonNumber)),
                    'title' => $lessonTitle,
                    'content_markdown' => $content,
                    'estimated_minutes' => $this->boundedInt(
                        $lessonRaw['estimated_minutes'] ?? ($outlineLesson['estimated_minutes'] ?? 20),
                        5,
                        600,
                        (int) ($outlineLesson['estimated_minutes'] ?? 20)
                    ),
                    'objectives' => $this->stringList(
                        $lessonRaw['objectives'] ?? ($outlineLesson['objectives'] ?? []),
                        8,
                        300
                    ),
                    'order_index' => $lessonNumber,
                ];
            }
        }

        $moduleSkillIds = $this->skillIds(
            $decoded['skill_ids'] ?? ($outlineModule['skill_ids'] ?? []),
            $errors,
            'module.skill_ids',
            is_array($outlineModule['skill_ids'] ?? null) ? $outlineModule['skill_ids'] : []
        );

        $quiz = $this->validateQuiz(
            $decoded['quiz'] ?? null,
            'Module quiz',
            $this->intSetting('max_quiz_questions_per_module', 10, 1, 30),
            $errors
        );

        $moduleDescription = $this->bodyText(
            $decoded['description'] ?? ($outlineModule['description'] ?? ''),
            0,
            2000,
            'Module description',
            $errors
        );

        if ($errors !== []) {
            return $this->failure('The module did not pass validation.', $errors, $warnings);
        }

        $module = [
            'key' => (string) ($outlineModule['key'] ?? 'module-1'),
            'title' => (string) ($outlineModule['title'] ?? ''),
            'description' => $moduleDescription,
            'order_index' => (int) ($outlineModule['order_index'] ?? 1),
            'skill_ids' => $moduleSkillIds,
            'skills' => $this->skillsForIds($moduleSkillIds),
            'lessons' => $lessons,
            'quiz' => $quiz,
            'assumptions' => $this->stringList($decoded['assumptions'] ?? [], 10, 500),
            'warnings' => $this->stringList($decoded['warnings'] ?? [], 10, 500),
        ];

        return [
            'success' => true,
            'message' => 'Module validated.',
            'module' => $module,
            'errors' => [],
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    /**
     * @param array<string, mixed> $course
     * @param array<int, array<string, mixed>> $modules
     * @return array{success: bool, message: string, evaluation: array<string, mixed>|null, errors: array<int, string>, warnings: array<int, string>}
     */
    public function validateEvaluation(mixed $decoded, array $course, array $modules): array
    {
        $decoded = $this->unwrap($decoded, 'evaluation');
        if (!is_array($decoded)) {
            return $this->failure('The evaluation was not a JSON object.');
        }

        $errors = [];
        $warnings = [];
        $maxQuestions = $this->intSetting('max_evaluation_questions', 20, 1, 50);
        $questions = $this->validateQuestions(
            $decoded['questions'] ?? null,
            $maxQuestions,
            'Evaluation',
            $errors
        );

        if ($questions === []) {
            $errors[] = 'The evaluation must contain at least one valid question.';
        }

        $evaluationTitle = $this->titleText(
            $decoded['title'] ?? ('Final evaluation: ' . (string) ($course['title'] ?? 'Course')),
            3,
            255,
            'Evaluation title',
            $errors
        );

        if ($errors !== []) {
            return $this->failure('The evaluation did not pass validation.', $errors, $warnings);
        }

        $evaluation = [
            'title' => $evaluationTitle,
            'duration_seconds' => $this->boundedInt($decoded['duration_seconds'] ?? 1200, 60, 7200, 1200),
            'passing_score' => $this->boundedFloat($decoded['passing_score'] ?? 70, 0, 100, 70),
            'max_attempts' => $this->boundedInt($decoded['max_attempts'] ?? 2, 1, 10, 2),
            'show_answers_after_submit' => $this->boolValue($decoded['show_answers_after_submit'] ?? 1),
            'questions' => $questions,
            'assumptions' => $this->stringList($decoded['assumptions'] ?? [], 10, 500),
            'warnings' => $this->stringList($decoded['warnings'] ?? [], 10, 500),
            'module_count' => count($modules),
        ];

        return [
            'success' => true,
            'message' => 'Evaluation validated.',
            'evaluation' => $evaluation,
            'errors' => [],
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    /**
     * Deterministic quality report. Blocking issues prevent the later apply
     * phase; warnings remain visible to the admin.
     *
     * @param array<string, mixed> $blueprint
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function quality(array $blueprint, array $context): array
    {
        $blocking = [];
        $warnings = [];
        $modelWarnings = [];
        $stats = [
            'modules' => 0,
            'lessons' => 0,
            'quiz_questions' => 0,
            'evaluation_questions' => 0,
            'skills' => 0,
            'estimated_minutes' => 0,
        ];

        $course = is_array($blueprint['course'] ?? null) ? $blueprint['course'] : [];
        $modules = is_array($blueprint['modules'] ?? null) ? array_values($blueprint['modules']) : [];
        $evaluation = is_array($blueprint['evaluation'] ?? null) ? $blueprint['evaluation'] : [];

        $maxModules = $this->intSetting('max_modules', 8, 1, 20);
        $maxLessons = $this->intSetting('max_lessons_per_module', 6, 1, 20);
        $maxQuiz = $this->intSetting('max_quiz_questions_per_module', 10, 1, 30);
        $maxEvaluation = $this->intSetting('max_evaluation_questions', 20, 1, 50);

        $courseTitle = trim((string) ($course['title'] ?? ''));
        if (mb_strlen($courseTitle) < 3) {
            $blocking[] = ['code' => 'course_title', 'message' => 'The course title is missing or too short.'];
        }
        if (mb_strlen(trim((string) ($course['description'] ?? ''))) < 10) {
            $blocking[] = ['code' => 'course_description', 'message' => 'The course description is missing or too short.'];
        }

        if ($modules === []) {
            $blocking[] = ['code' => 'modules_missing', 'message' => 'The course has no generated modules.'];
        } elseif (count($modules) > $maxModules) {
            $blocking[] = [
                'code' => 'module_limit',
                'message' => 'The course has more modules than the configured maximum of ' . $maxModules . '.',
            ];
        }

        if (count($modules) !== $this->briefModuleCount()) {
            $warnings[] = 'The generated module count differs from the requested course shape.';
        }

        $moduleTitles = [];
        $questionTexts = [];
        $skillIds = [];
        $courseSkillIds = is_array($course['skill_ids'] ?? null) ? $course['skill_ids'] : [];
        foreach ($courseSkillIds as $id) {
            $skillIds[(int) $id] = true;
        }

        foreach ($modules as $moduleIndex => $module) {
            if (!is_array($module)) {
                $blocking[] = ['code' => 'module_invalid', 'message' => 'A generated module is not valid.'];
                continue;
            }
            $stats['modules']++;
            $moduleTitle = trim((string) ($module['title'] ?? ''));
            if (mb_strlen($moduleTitle) < 3) {
                $blocking[] = ['code' => 'module_title', 'message' => 'Module ' . ($moduleIndex + 1) . ' has no usable title.'];
            }
            $titleKey = mb_strtolower($moduleTitle);
            if ($titleKey !== '' && isset($moduleTitles[$titleKey])) {
                $blocking[] = ['code' => 'module_duplicate', 'message' => "Module title \"{$moduleTitle}\" is duplicated."];
            }
            $moduleTitles[$titleKey] = true;

            $moduleSkillIds = is_array($module['skill_ids'] ?? null) ? $module['skill_ids'] : [];
            foreach ($moduleSkillIds as $id) {
                $skillIds[(int) $id] = true;
            }
            $moduleWarnings = is_array($module['warnings'] ?? null) ? $module['warnings'] : [];
            foreach ($moduleWarnings as $warning) {
                if (is_string($warning) && trim($warning) !== '') {
                    $modelWarnings[] = trim($warning);
                }
            }

            $lessons = is_array($module['lessons'] ?? null) ? $module['lessons'] : [];
            if ($lessons === []) {
                $blocking[] = ['code' => 'lessons_missing', 'message' => "Module \"{$moduleTitle}\" has no lessons."];
            } elseif (count($lessons) > $maxLessons) {
                $blocking[] = [
                    'code' => 'lesson_limit',
                    'message' => "Module \"{$moduleTitle}\" has more lessons than the configured maximum.",
                ];
            }

            $lessonTitles = [];
            foreach ($lessons as $lessonIndex => $lesson) {
                if (!is_array($lesson)) {
                    $blocking[] = ['code' => 'lesson_invalid', 'message' => 'A generated lesson is not valid.'];
                    continue;
                }
                $stats['lessons']++;
                $stats['estimated_minutes'] += max(0, (int) ($lesson['estimated_minutes'] ?? 0));
                $lessonTitle = trim((string) ($lesson['title'] ?? ''));
                if (mb_strlen($lessonTitle) < 3) {
                    $blocking[] = [
                        'code' => 'lesson_title',
                        'message' => "Module \"{$moduleTitle}\" lesson " . ($lessonIndex + 1) . ' has no usable title.',
                    ];
                }
                $lessonKey = mb_strtolower($lessonTitle);
                if ($lessonKey !== '' && isset($lessonTitles[$lessonKey])) {
                    $warnings[] = "Module \"{$moduleTitle}\" has a duplicate lesson title \"{$lessonTitle}\".";
                }
                $lessonTitles[$lessonKey] = true;

                $content = (string) ($lesson['content_markdown'] ?? '');
                if (mb_strlen(trim($content)) < 40) {
                    $blocking[] = [
                        'code' => 'lesson_content',
                        'message' => "Lesson \"{$lessonTitle}\" has insufficient content.",
                    ];
                }
                if ($this->hasRawHtml($content)) {
                    $warnings[] = "Lesson \"{$lessonTitle}\" contains raw HTML; it will be sanitized before display.";
                }
            }

            $quiz = is_array($module['quiz'] ?? null) ? $module['quiz'] : [];
            $questions = is_array($quiz['questions'] ?? null) ? $quiz['questions'] : [];
            if ($questions === []) {
                $blocking[] = ['code' => 'quiz_missing', 'message' => "Module \"{$moduleTitle}\" has no quiz questions."];
            } elseif (count($questions) > $maxQuiz) {
                $blocking[] = ['code' => 'quiz_limit', 'message' => "Module \"{$moduleTitle}\" has too many quiz questions."];
            }
            $stats['quiz_questions'] += count($questions);
            $this->inspectQuestions($questions, $questionTexts, $warnings, $blocking, $moduleTitle);
        }

        $evaluationQuestions = is_array($evaluation['questions'] ?? null) ? $evaluation['questions'] : [];
        if ($evaluationQuestions === []) {
            $blocking[] = ['code' => 'evaluation_missing', 'message' => 'The final evaluation has no questions.'];
        } elseif (count($evaluationQuestions) > $maxEvaluation) {
            $blocking[] = ['code' => 'evaluation_limit', 'message' => 'The final evaluation has too many questions.'];
        }
        $stats['evaluation_questions'] = count($evaluationQuestions);
        $this->inspectQuestions($evaluationQuestions, $questionTexts, $warnings, $blocking, 'Final evaluation');
        $evaluationWarnings = is_array($evaluation['warnings'] ?? null) ? $evaluation['warnings'] : [];
        foreach ($evaluationWarnings as $warning) {
            if (is_string($warning) && trim($warning) !== '') {
                $modelWarnings[] = trim($warning);
            }
        }

        foreach (array_keys($skillIds) as $id) {
            if ($id > 0 && !isset($this->activeById[$id])) {
                $blocking[] = [
                    'code' => 'skill_unknown',
                    'message' => "The blueprint references unknown or archived skill id {$id}.",
                ];
            }
        }
        $stats['skills'] = count(array_filter(array_keys($skillIds), static fn($id): bool => (int) $id > 0));

        if (!empty($context['limited_evidence'])) {
            $warnings[] = 'The course was generated from limited trainee evidence. Review assumptions carefully.';
        }
        $courseWarnings = is_array($course['warnings'] ?? null) ? $course['warnings'] : [];
        foreach ($courseWarnings as $warning) {
            if (is_string($warning) && trim($warning) !== '') {
                $modelWarnings[] = trim($warning);
            }
        }
        $courseAssumptions = is_array($course['assumptions'] ?? null) ? $course['assumptions'] : [];
        if ($courseAssumptions !== []) {
            $warnings[] = 'The model recorded ' . count($courseAssumptions) . ' assumption(s); review them before applying.';
        }

        $warnings = array_values(array_unique($warnings));
        $modelWarnings = array_values(array_unique($modelWarnings));
        $score = max(0, 100 - (count($blocking) * 20) - (count($warnings) * 3));

        return [
            'passed' => $blocking === [],
            'score' => $score,
            'checked_at' => gmdate('c'),
            'blocking' => $blocking,
            'warnings' => $warnings,
            'model_warnings' => $modelWarnings,
            'stats' => $stats,
        ];
    }

    /**
     * @param array<int, mixed> $questions
     * @param array<string, bool> $questionTexts
     * @param array<int, string> $warnings
     * @param array<int, array{code: string, message: string}> $blocking
     */
    private function inspectQuestions(
        array $questions,
        array &$questionTexts,
        array &$warnings,
        array &$blocking,
        string $label
    ): void {
        foreach ($questions as $question) {
            if (!is_array($question)) {
                $blocking[] = ['code' => 'question_invalid', 'message' => "{$label} contains an invalid question."];
                continue;
            }
            $type = strtolower(trim((string) ($question['question_type'] ?? '')));
            if (!in_array($type, ['single_choice', 'true_false'], true)) {
                $blocking[] = [
                    'code' => 'question_type',
                    'message' => "{$label} contains an unsupported question type.",
                ];
            }
            $text = mb_strtolower(trim((string) ($question['question_text'] ?? '')));
            if ($text === '') {
                $blocking[] = ['code' => 'question_text', 'message' => "{$label} contains an empty question."];
                continue;
            }
            if (isset($questionTexts[$text])) {
                $warnings[] = "{$label} repeats a question from another part of the course.";
            }
            $questionTexts[$text] = true;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validateQuiz(mixed $raw, string $label, int $maxQuestions, array &$errors): array
    {
        if (!is_array($raw)) {
            $errors[] = "{$label} is missing.";
            return [];
        }

        $questions = $this->validateQuestions($raw['questions'] ?? null, $maxQuestions, $label, $errors);

        return [
            'title' => $this->titleText($raw['title'] ?? ($label . ' knowledge check'), 3, 255, "{$label} title", $errors),
            'duration_seconds' => $this->boundedInt($raw['duration_seconds'] ?? 600, 60, 7200, 600),
            'passing_score' => $this->boundedFloat($raw['passing_score'] ?? 70, 0, 100, 70),
            'max_attempts' => $this->boundedInt($raw['max_attempts'] ?? 2, 1, 10, 2),
            'show_answers_after_submit' => $this->boolValue($raw['show_answers_after_submit'] ?? 1),
            'questions' => $questions,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function validateQuestions(mixed $raw, int $maxQuestions, string $label, array &$errors): array
    {
        if (!is_array($raw) || $raw === []) {
            $errors[] = "{$label} must contain at least one question.";
            return [];
        }
        if (count($raw) > $maxQuestions) {
            $errors[] = "{$label} contains more than the maximum of {$maxQuestions} questions.";
        }

        $questions = [];
        $seenQuestions = [];
        foreach (array_values($raw) as $index => $questionRaw) {
            $number = $index + 1;
            if (!is_array($questionRaw)) {
                $errors[] = "{$label} question {$number} is not a JSON object.";
                continue;
            }

            $questionText = $this->bodyText(
                $questionRaw['question_text'] ?? '',
                5,
                2000,
                "{$label} question {$number}",
                $errors
            );
            $questionKey = mb_strtolower($questionText);
            if ($questionKey !== '' && isset($seenQuestions[$questionKey])) {
                $errors[] = "{$label} question {$number} is duplicated.";
            }
            $seenQuestions[$questionKey] = true;

            $type = strtolower(trim((string) ($questionRaw['question_type'] ?? '')));
            if (!in_array($type, ['single_choice', 'true_false'], true)) {
                $errors[] = "{$label} question {$number} uses an unsupported type. Only single_choice and true_false are enabled.";
                continue;
            }

            $optionsRaw = $questionRaw['options'] ?? null;
            $optionCount = is_array($optionsRaw) ? count($optionsRaw) : 0;
            if ($type === 'true_false') {
                if ($optionCount !== 2) {
                    $errors[] = "{$label} question {$number} must have exactly two true/false options.";
                }
            } elseif ($optionCount < 2 || $optionCount > 6) {
                $errors[] = "{$label} question {$number} must have between 2 and 6 options.";
            }

            $options = [];
            $correctCount = 0;
            $optionTexts = [];
            if (is_array($optionsRaw)) {
                foreach (array_values($optionsRaw) as $optionIndex => $optionRaw) {
                    $optionNumber = $optionIndex + 1;
                    if (!is_array($optionRaw)) {
                        $errors[] = "{$label} question {$number} option {$optionNumber} is not a JSON object.";
                        continue;
                    }
                    $optionText = $this->titleText(
                        $optionRaw['text'] ?? '',
                        1,
                        500,
                        "{$label} question {$number} option {$optionNumber}",
                        $errors
                    );
                    if ($type === 'true_false') {
                        $lower = mb_strtolower($optionText);
                        if ($lower === 'true') {
                            $optionText = 'True';
                        } elseif ($lower === 'false') {
                            $optionText = 'False';
                        } else {
                            $errors[] = "{$label} question {$number} true/false options must be True and False.";
                        }
                    }
                    $optionKey = mb_strtolower($optionText);
                    if ($optionKey !== '' && isset($optionTexts[$optionKey])) {
                        $errors[] = "{$label} question {$number} has a duplicate option.";
                    }
                    $optionTexts[$optionKey] = true;

                    $isCorrect = $this->boolValue($optionRaw['is_correct'] ?? false);
                    if ($isCorrect) {
                        $correctCount++;
                    }
                    $options[] = [
                        'text' => $optionText,
                        'is_correct' => $isCorrect,
                        'order_index' => $optionNumber,
                    ];
                }
            }

            if ($correctCount !== 1) {
                $errors[] = "{$label} question {$number} must have exactly one correct option.";
            }

            $questions[] = [
                'question_text' => $questionText,
                'question_type' => $type,
                'order_index' => $number,
                'options' => $options,
            ];
        }

        return $questions;
    }

    /**
     * @param array<int, mixed> $raw
     * @param int[] $fallback
     * @return int[]
     */
    private function skillIds(mixed $raw, array &$errors, string $field, array $fallback = []): array
    {
        if (!is_array($raw) || $raw === []) {
            $raw = $fallback;
        }
        if (!is_array($raw)) {
            $errors[] = "{$field} must be an array of skill ids.";
            return [];
        }

        $ids = [];
        foreach ($raw as $value) {
            if (!is_int($value) && !(is_string($value) && ctype_digit($value))) {
                $errors[] = "{$field} contains an invalid skill id.";
                continue;
            }
            $id = (int) $value;
            if ($id <= 0) {
                $errors[] = "{$field} contains an invalid skill id.";
                continue;
            }
            if (!isset($this->activeById[$id])) {
                $errors[] = "{$field} references unknown or archived skill id {$id}.";
                continue;
            }
            if ($this->allowedSkillIds !== null && !isset($this->allowedSkillIds[$id])) {
                $errors[] = "{$field} references skill id {$id}, which is not a current gap or selected skill.";
                continue;
            }
            $ids[$id] = $id;
        }

        return array_values($ids);
    }

    /**
     * @param int[] $ids
     * @return array<int, array{id: int, name: string}>
     */
    private function skillsForIds(array $ids): array
    {
        $skills = [];
        foreach ($ids as $id) {
            if (isset($this->activeById[(int) $id])) {
                $skills[] = $this->activeById[(int) $id];
            }
        }
        return $skills;
    }

    private function titleText(mixed $value, int $min, int $max, string $label, array &$errors): string
    {
        if (!is_string($value)) {
            if ($min > 0) {
                $errors[] = "{$label} is missing.";
            }
            return '';
        }
        $text = $this->cleanText($value);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = trim($text);
        $length = mb_strlen($text);
        if ($length < $min) {
            $errors[] = "{$label} is too short.";
        } elseif ($length > $max) {
            $errors[] = "{$label} is too long.";
            $text = mb_substr($text, 0, $max);
        }
        return $text;
    }

    private function bodyText(mixed $value, int $min, int $max, string $label, array &$errors): string
    {
        if (!is_string($value)) {
            if ($min > 0) {
                $errors[] = "{$label} is missing.";
            }
            return '';
        }
        $text = $this->cleanText($value);
        $length = mb_strlen($text);
        if ($length < $min) {
            $errors[] = "{$label} is too short.";
        } elseif ($length > $max) {
            $errors[] = "{$label} is too long.";
            $text = mb_substr($text, 0, $max);
        }
        return $text;
    }

    private function cleanText(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? $value;
        return trim($value);
    }

    /**
     * @return array<int, string>
     */
    private function stringList(mixed $value, int $maxItems, int $maxLength): array
    {
        if (!is_array($value)) {
            return [];
        }
        $items = [];
        foreach ($value as $item) {
            if (!is_string($item)) {
                continue;
            }
            $item = trim($this->cleanText($item));
            if ($item === '') {
                continue;
            }
            $items[mb_strtolower($item)] = mb_substr($item, 0, $maxLength);
        }
        return array_slice(array_values($items), 0, $maxItems);
    }

    private function key(mixed $value, string $fallback): string
    {
        if (!is_string($value)) {
            return $fallback;
        }
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_-]+/', '-', $value) ?? '';
        $value = trim($value, '-_');
        return $value !== '' ? mb_substr($value, 0, 80) : $fallback;
    }

    private function unwrap(mixed $decoded, string $key): mixed
    {
        if (is_array($decoded) && is_array($decoded[$key] ?? null)) {
            return $decoded[$key];
        }
        return $decoded;
    }

    private function boolValue(mixed $value): bool
    {
        return $value === true
            || $value === 1
            || $value === '1'
            || (is_string($value) && strtolower(trim($value)) === 'true');
    }

    private function boundedInt(mixed $value, int $min, int $max, int $default): int
    {
        if (!is_numeric($value)) {
            return $default;
        }
        return max($min, min($max, (int) $value));
    }

    private function boundedFloat(mixed $value, float $min, float $max, float $default): float
    {
        if (!is_numeric($value)) {
            return $default;
        }
        return max($min, min($max, (float) $value));
    }

    private function intSetting(string $key, int $default, int $min, int $max): int
    {
        $value = $this->settings[$key] ?? $default;
        return max($min, min($max, (int) $value));
    }

    /**
     * @return int[]
     */
    private function briefSelectedSkillIds(): array
    {
        $ids = [];
        foreach (($this->brief['selected_skill_ids'] ?? []) as $id) {
            if (is_int($id) || (is_string($id) && ctype_digit($id))) {
                $ids[] = (int) $id;
            }
        }
        return array_values(array_unique($ids));
    }

    private function briefModuleCount(): int
    {
        return max(1, min(20, (int) ($this->brief['module_count'] ?? 1)));
    }

    private function briefLessonsPerModule(): int
    {
        return max(1, min(20, (int) ($this->brief['lessons_per_module'] ?? 1)));
    }

    private function briefDifficulty(): string
    {
        $difficulty = strtolower(trim((string) ($this->brief['difficulty'] ?? 'intermediate')));
        return in_array($difficulty, ['beginner', 'intermediate', 'advanced'], true)
            ? $difficulty
            : 'intermediate';
    }

    private function hasRawHtml(string $content): bool
    {
        return preg_match('/<\s*(script|iframe|object|embed|form|input|style)\b/i', $content) === 1
            || preg_match('/\bon[a-z]+\s*=/i', $content) === 1
            || stripos($content, 'javascript:') !== false;
    }

    /**
     * @param array<int, string> $errors
     * @param array<int, string> $warnings
     * @return array{success: bool, message: string, outline: null, module: null, evaluation: null, errors: array<int, string>, warnings: array<int, string>}
     */
    private function failure(string $message, array $errors = [], array $warnings = []): array
    {
        return [
            'success' => false,
            'message' => $message,
            'outline' => null,
            'module' => null,
            'evaluation' => null,
            'errors' => array_slice(array_values(array_unique($errors)), 0, 10),
            'warnings' => array_slice(array_values(array_unique($warnings)), 0, 20),
        ];
    }
}
