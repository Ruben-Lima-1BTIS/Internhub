<?php

namespace App\Services;

use App\Models\User;
use App\Models\Company;
use App\Models\ClassModel;
use App\Models\Internship;
use App\Models\Hour;
use App\Models\Report;
use App\Models\UserClass;

class DashboardService
{
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
            return [
                'myClasses' => 0,
                'myStudents' => 0,
                'classes' => [],
            ];
        }

        $studentIds = UserClass::whereIn('class_id', $classIds)
            ->whereHas('user', fn($q) => $q->where('role', User::ROLE_STUDENT))
            ->pluck('user_id')
            ->unique();

        if ($studentIds->isEmpty()) {
            return [
                'myClasses' => $classes->count(),
                'myStudents' => 0,
                'classes' => [],
            ];
        }

        $students = User::whereIn('id', $studentIds)
            ->where('role', User::ROLE_STUDENT)
            ->get()
            ->keyBy('id');

        $internships = Internship::whereHas('studentAssignments', function ($q) use ($studentIds) {
            $q->whereIn('user_id', $studentIds);
        })
            ->with(['studentAssignments' => fn($q) => $q->whereIn('user_id', $studentIds)])
            ->get()
            ->flatMap(function ($internship) {
                return $internship->studentAssignments->map(fn($assignment) => [
                    'user_id' => $assignment->user_id,
                    'internship' => $internship,
                ]);
            })
            ->groupBy('user_id')
            ->map(fn($items) => $items->first()['internship']);

        // All-time hours grouped by student + status
        $hoursByStudent = Hour::whereIn('student_id', $studentIds)
            ->selectRaw('
                student_id,
                status,
                SUM(GREATEST(TIMESTAMPDIFF(MINUTE, start_time, end_time) - 60, 0)) as total_minutes
            ')
            ->groupBy('student_id', 'status')
            ->get()
            ->groupBy('student_id');

        // Weekly hours (Mon–Fri) per student
        $weeklyHoursByStudent = Hour::whereIn('student_id', $studentIds)
            ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
            ->selectRaw('
                student_id,
                DAYOFWEEK(date) as day,
                SUM(GREATEST(TIMESTAMPDIFF(MINUTE, start_time, end_time) - 60, 0)) / 60 as hours
            ')
            ->groupBy('student_id', 'day')
            ->get()
            ->groupBy('student_id');

        $reportsCount = Report::whereIn('student_id', $studentIds)
            ->selectRaw('student_id, COUNT(*) as total')
            ->groupBy('student_id')
            ->pluck('total', 'student_id');

        $classData = $classes->map(function ($class) use ($students, $hoursByStudent, $weeklyHoursByStudent, $reportsCount, $internships) {
            $classStudentIds = UserClass::where('class_id', $class->id)
                ->pluck('user_id');

            $studentsData = $classStudentIds->map(function ($studentId) use ($students, $hoursByStudent, $weeklyHoursByStudent, $reportsCount, $internships) {
                $student = $students[$studentId] ?? null;
                if (!$student) return null;

                $studentHours = $hoursByStudent[$studentId] ?? collect();

                $approved = ($studentHours->firstWhere('status', 'approved')->total_minutes ?? 0) / 60;
                $pending  = ($studentHours->firstWhere('status', 'pending')->total_minutes ?? 0) / 60;
                $rejected = ($studentHours->firstWhere('status', 'rejected')->total_minutes ?? 0) / 60;

                $internship = $internships[$studentId] ?? null;
                $required   = $internship?->total_hours_required ?? 0;
                $remaining  = max($required - $approved - $pending, 0);

                // Mon(2) Tue(3) Wed(4) Thu(5) Fri(6) – DAYOFWEEK is 1-indexed from Sunday
                $weekly = $weeklyHoursByStudent[$studentId] ?? collect();
                $weeklyHours = collect([2, 3, 4, 5, 6])->map(
                    fn($day) => round($weekly->firstWhere('day', $day)?->hours ?? 0, 1)
                )->values()->all();

                return [
                    'id'               => $student->id,
                    'name'             => $student->name,
                    'internship'       => $internship?->title ?? null,
                    'company'          => $internship?->company?->name ?? null,
                    'approved_hours'   => round($approved, 1),
                    'pending_hours'    => round($pending, 1),
                    'rejected_hours'   => round($rejected, 1),
                    'remaining_hours'  => round($remaining, 1),
                    'total_required'   => $required,
                    'reports_submitted' => $reportsCount[$studentId] ?? 0,
                    'weekly_hours'     => $weeklyHours,
                ];
            })->filter()->values();

            return [
                'id'       => $class->id,
                'course'   => $class->course,
                'sigla'    => $class->sigla,
                'students' => $studentsData,
            ];
        });

        return [
            'myClasses'  => $classes->count(),
            'myStudents' => $studentIds->count(),
            'classes'    => $classData,
        ];
    }

    private function getStudentStats(): array
    {
        $studentId = auth()->id();

        $internship = Internship::whereHas('studentAssignments', function ($q) use ($studentId) {
            $q->where('user_id', $studentId);
        })->first();

        $minutesByStatus = Hour::where('student_id', $studentId)
            ->selectRaw('status, SUM(GREATEST(TIMESTAMPDIFF(MINUTE, start_time, end_time) - 60, 0)) as total_minutes')
            ->groupBy('status')
            ->pluck('total_minutes', 'status');

        $approvedHours  = round(($minutesByStatus['approved'] ?? 0) / 60, 1);
        $pendingHours   = round(($minutesByStatus['pending'] ?? 0) / 60, 1);
        $rejectedHours  = round(($minutesByStatus['rejected'] ?? 0) / 60, 1);

        $totalHoursRequired = $internship?->total_hours_required ?? 0;
        $remainingHours = max($totalHoursRequired - $approvedHours - $pendingHours - $rejectedHours, 0);

        $weeklyHours = Hour::where('student_id', $studentId)
            ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
            ->selectRaw('DAYOFWEEK(date) as day, SUM(GREATEST(TIMESTAMPDIFF(MINUTE, start_time, end_time) - 60, 0)) / 60 as hours')
            ->groupBy('day')
            ->pluck('hours', 'day');

        return [
            'myInternships'      => $internship ? 1 : 0,
            'totalHoursRequired' => $totalHoursRequired,
            'approvedHours'      => $approvedHours,
            'pendingHours'       => $pendingHours,
            'rejectedHours'      => $rejectedHours,
            'remainingHours'     => $remainingHours,
            'reportsSubmitted'   => Report::where('student_id', $studentId)->count(),
            'weeklyHours' => [
                round($weeklyHours[2] ?? 0, 1),
                round($weeklyHours[3] ?? 0, 1),
                round($weeklyHours[4] ?? 0, 1),
                round($weeklyHours[5] ?? 0, 1),
                round($weeklyHours[6] ?? 0, 1),
            ],
        ];
    }

    private function getSupervisorStats(): array
    {
        return [
            'myInterns' => Internship::whereHas('supervisorAssignment', function ($q) {
                $q->where('user_id', auth()->id());
            })->count(),
        ];
    }
}