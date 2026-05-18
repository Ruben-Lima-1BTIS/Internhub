<?php

namespace App\Services;

use App\Models\User;
use App\Models\Company;
use App\Models\ClassModel;
use App\Models\Internship;
use App\Models\Hour;
use App\Models\Report;
use App\Models\UserClass;
use App\Models\UserInternship;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private function isoDowExpr(string $column): string
    {
        return match (DB::getDriverName()) {
            'pgsql' => "EXTRACT(ISODOW FROM $column)::int",
            'sqlite' => "CAST(strftime('%u', $column) AS INTEGER)",
            'sqlsrv' => "((DATEPART(WEEKDAY, $column) + 5) % 7 + 1)",
            'mysql' => "((DAYOFWEEK($column) + 5) % 7 + 1)",
            default => throw new \RuntimeException('Unsupported DB driver: ' . DB::getDriverName()),
        };
    }
    private function weekdayNumbers(): array
    {
        return [1, 2, 3, 4, 5];
    }
    public function getStats(User $user): array
    {
        return match ($user->role) {
            User::ROLE_ADMIN => $this->getAdminStats(),
            User::ROLE_COORDINATOR => $this->getCoordinatorStats(),
            User::ROLE_SUPERVISOR => $this->getSupervisorStats(),
            User::ROLE_STUDENT => $this->getStudentStats(),
            default => [],
        };
    }

    private function getAdminStats(): array
    {
        return [
            'totalUsers' => User::where('role', '!=', User::ROLE_ADMIN)->count(),
            'totalCoordinators' => User::where('role', User::ROLE_COORDINATOR)->count(),
            'totalSupervisors' => User::where('role', User::ROLE_SUPERVISOR)->count(),
            'totalStudents' => User::where('role', User::ROLE_STUDENT)->count(),
            'totalCompanies' => Company::count(),
            'totalClasses' => ClassModel::count(),
            'totalInternships' => Internship::count(),
        ];
    }

    private function getCoordinatorStats(): array
    {
        $coordinatorId = auth()->id();

        $classIds = UserClass::where('user_id', $coordinatorId)->pluck('class_id');
        $classes = ClassModel::whereIn('id', $classIds)->get();

        if ($classIds->isEmpty()) {
            return ['myClasses' => 0, 'myStudents' => 0, 'classes' => []];
        }

        $studentIds = UserClass::whereIn('class_id', $classIds)
            ->whereHas('user', fn($q) => $q->where('role', User::ROLE_STUDENT))
            ->pluck('user_id')
            ->unique();

        if ($studentIds->isEmpty()) {
            return ['myClasses' => $classes->count(), 'myStudents' => 0, 'classes' => []];
        }

        $students = User::whereIn('id', $studentIds)
            ->where('role', User::ROLE_STUDENT)
            ->get()
            ->keyBy('id');

        $internships = Internship::whereHas('studentAssignments', fn($q) => $q->whereIn('user_id', $studentIds))
            ->with(['studentAssignments' => fn($q) => $q->whereIn('user_id', $studentIds)])
            ->get()
            ->flatMap(fn($internship) => $internship->studentAssignments->map(fn($a) => [
                'user_id' => $a->user_id,
                'internship' => $internship,
            ]))
            ->groupBy('user_id')
            ->map(fn($items) => $items->first()['internship']);

        $dowExpr = $this->isoDowExpr('date');

        $hoursByStudent = Hour::whereIn('student_id', $studentIds)
            ->selectRaw("student_id, status, SUM(duration_hours) as total_hours")
            ->groupBy('student_id', 'status')
            ->get()
            ->groupBy('student_id');

        $weeklyHoursByStudent = Hour::whereIn('student_id', $studentIds)
            ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
            ->selectRaw("student_id, $dowExpr as day, SUM(duration_hours) as hours")
            ->groupBy('student_id', 'day')
            ->get()
            ->groupBy('student_id');

        $reportsCount = Report::whereIn('student_id', $studentIds)
            ->selectRaw('student_id, COUNT(*) as total')
            ->groupBy('student_id')
            ->pluck('total', 'student_id');

        $classData = $classes->map(function ($class) use ($students, $hoursByStudent, $weeklyHoursByStudent, $reportsCount, $internships) {
            $classStudentIds = UserClass::where('class_id', $class->id)->pluck('user_id');

            $studentsData = $classStudentIds->map(function ($studentId) use ($students, $hoursByStudent, $weeklyHoursByStudent, $reportsCount, $internships) {
                $student = $students[$studentId] ?? null;
                if (!$student)
                    return null;

                $studentHours = $hoursByStudent[$studentId] ?? collect();
                $approved = $studentHours->firstWhere('status', 'approved')->total_hours ?? 0;
                $pending = $studentHours->firstWhere('status', 'pending')->total_hours ?? 0;
                $rejected = $studentHours->firstWhere('status', 'rejected')->total_hours ?? 0;

                $internship = $internships[$studentId] ?? null;
                $required = $internship?->total_hours_required ?? 0;
                $remaining = max($required - $approved - $pending - $rejected, 0);

                $weekly = $weeklyHoursByStudent[$studentId] ?? collect();
                $weeklyHours = collect($this->weekdayNumbers())
                    ->map(fn($day) => round($weekly->firstWhere('day', $day)?->hours ?? 0, 1))
                    ->values()
                    ->all();

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'internship' => $internship?->title ?? null,
                    'company' => $internship?->company?->name ?? null,
                    'approved_hours' => round($approved, 1),
                    'pending_hours' => round($pending, 1),
                    'rejected_hours' => round($rejected, 1),
                    'remaining_hours' => round($remaining, 1),
                    'total_required' => $required,
                    'reports_submitted' => $reportsCount[$studentId] ?? 0,
                    'weekly_hours' => $weeklyHours,
                ];
            })->filter()->values();

            return [
                'id' => $class->id,
                'course' => $class->course,
                'sigla' => $class->sigla,
                'students' => $studentsData,
            ];
        });

        return [
            'myClasses' => $classes->count(),
            'myStudents' => $studentIds->count(),
            'classes' => $classData,
        ];
    }

    private function getStudentStats(): array
    {
        $studentId = auth()->id();

        $internship = Internship::whereHas('studentAssignments', fn($q) => $q->where('user_id', $studentId))->first();

        $dowExpr = $this->isoDowExpr('date');

        $HourByStatus = Hour::where('student_id', $studentId)
            ->selectRaw("status, SUM(duration_hours) as total_hours")
            ->groupBy('status')
            ->pluck('total_hours', 'status');

        $approvedHours = round($HourByStatus['approved'] ?? 0, 1);
        $pendingHours = round($HourByStatus['pending'] ?? 0, 1);
        $rejectedHours = round($HourByStatus['rejected'] ?? 0, 1);

        $totalHoursRequired = $internship?->total_hours_required ?? 0;
        $remainingHours = max($totalHoursRequired - $approvedHours - $pendingHours - $rejectedHours, 0);

        $weeklyRaw = Hour::where('student_id', $studentId)
            ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
            ->selectRaw("$dowExpr as day, SUM(duration_hours) as hours")
            ->groupBy('day')
            ->pluck('hours', 'day');

        $weeklyHours = collect($this->weekdayNumbers())
            ->map(fn($day) => round($weeklyRaw[$day] ?? 0, 1))
            ->values()
            ->all();

        return [
            'myInternships' => $internship ? 1 : 0,
            'totalHoursRequired' => $totalHoursRequired,
            'approvedHours' => $approvedHours,
            'pendingHours' => $pendingHours,
            'rejectedHours' => $rejectedHours,
            'remainingHours' => $remainingHours,
            'reportsSubmitted' => Report::where('student_id', $studentId)->count(),
            'weeklyHours' => $weeklyHours,
        ];
    }

    private function getSupervisorStats(): array
    {
        $supervisorId = auth()->id();

        $internshipIds = UserInternship::where('user_id', $supervisorId)
            ->pluck('internship_id')
            ->unique();

        if ($internshipIds->isEmpty()) {
            return [
                'myInternships' => 0,
                'myStudents' => 0,
                'internships' => [],
            ];
        }

        $internships = Internship::whereIn('id', $internshipIds)
            ->with('company')
            ->get();

        $studentIds = UserInternship::whereIn('internship_id', $internshipIds)
            ->whereHas('user', fn($q) => $q->where('role', User::ROLE_STUDENT))
            ->pluck('user_id')
            ->unique();

        if ($studentIds->isEmpty()) {
            return [
                'myInternships' => $internships->count(),
                'myStudents' => 0,
                'internships' => $internships
                    ->map(fn($internship) => [
                        'id' => $internship->id,
                        'title' => $internship->title,
                        'company' => $internship->company?->name ?? null,
                        'students' => [],
                    ])
                    ->values(),
            ];
        }

        $internships->load([
            'studentAssignments' => fn($q) => $q->whereIn('user_id', $studentIds),
        ]);

        $students = User::whereIn('id', $studentIds)
            ->where('role', User::ROLE_STUDENT)
            ->get()
            ->keyBy('id');

        $dowExpr = $this->isoDowExpr('date');

        $hoursByStudent = Hour::whereIn('student_id', $studentIds)
            ->selectRaw("student_id, status, SUM(duration_hours) as total_hours")
            ->groupBy('student_id', 'status')
            ->get()
            ->groupBy('student_id');

        $weeklyHoursByStudent = Hour::whereIn('student_id', $studentIds)
            ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
            ->selectRaw("student_id, $dowExpr as day, SUM(duration_hours) as hours")
            ->groupBy('student_id', 'day')
            ->get()
            ->groupBy('student_id');

        $reportsCount = Report::whereIn('student_id', $studentIds)
            ->selectRaw('student_id, COUNT(*) as total')
            ->groupBy('student_id')
            ->pluck('total', 'student_id');

        $internshipData = $internships->map(function ($internship) use ($students, $hoursByStudent, $weeklyHoursByStudent, $reportsCount) {
            $studentsData = $internship->studentAssignments
                ->map(function ($assignment) use ($students, $hoursByStudent, $weeklyHoursByStudent, $reportsCount, $internship) {
                    $studentId = $assignment->user_id;
                    $student = $students[$studentId] ?? null;
                    if (!$student) {
                        return null;
                    }

                    $studentHours = $hoursByStudent[$studentId] ?? collect();
                    $approved = $studentHours->firstWhere('status', 'approved')->total_hours ?? 0;
                    $pending = $studentHours->firstWhere('status', 'pending')->total_hours ?? 0;
                    $rejected = $studentHours->firstWhere('status', 'rejected')->total_hours ?? 0;

                    $required = $internship->total_hours_required ?? 0;
                    $remaining = max($required - $approved - $pending - $rejected, 0);

                    $weekly = $weeklyHoursByStudent[$studentId] ?? collect();
                    $weeklyHours = collect($this->weekdayNumbers())
                        ->map(fn($day) => round($weekly->firstWhere('day', $day)?->hours ?? 0, 1))
                        ->values()
                        ->all();

                    return [
                        'id' => $student->id,
                        'name' => $student->name,
                        'internship' => $internship->title ?? null,
                        'company' => $internship->company?->name ?? null,
                        'approved_hours' => round($approved, 1),
                        'pending_hours' => round($pending, 1),
                        'rejected_hours' => round($rejected, 1),
                        'remaining_hours' => round($remaining, 1),
                        'total_required' => $required,
                        'reports_submitted' => $reportsCount[$studentId] ?? 0,
                        'weekly_hours' => $weeklyHours,
                    ];
                })
                ->filter()
                ->values();

            return [
                'id' => $internship->id,
                'title' => $internship->title,
                'company' => $internship->company?->name ?? null,
                'students' => $studentsData,
            ];
        });

        return [
            'myInternships' => $internships->count(),
            'myStudents' => $studentIds->count(),
            'internships' => $internshipData,
        ];
    }
}