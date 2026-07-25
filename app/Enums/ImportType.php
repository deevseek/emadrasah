<?php

declare(strict_types=1);

namespace App\Enums;

enum ImportType: string { case TeachingAssignment = 'teaching_assignment'; case LessonSchedule = 'lesson_schedule'; }
