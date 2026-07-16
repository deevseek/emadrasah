<?php

declare(strict_types=1);

namespace App\Enums;

enum AssessmentType: string
{
    case Assignment = 'assignment';
    case DailyTest = 'daily_test';
    case Practice = 'practice';
    case Project = 'project';
    case Portfolio = 'portfolio';
    case Midterm = 'midterm';
    case FinalExam = 'final_exam';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Assignment => 'Assignment',
            self::DailyTest => 'Daily Test',
            self::Practice => 'Practice',
            self::Project => 'Project',
            self::Portfolio => 'Portfolio',
            self::Midterm => 'Midterm',
            self::FinalExam => 'Final Exam',
            self::Other => 'Other',
        };
    }
}
