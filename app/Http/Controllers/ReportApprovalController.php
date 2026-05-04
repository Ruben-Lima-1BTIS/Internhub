<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\User;
use App\Models\UserClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportApprovalController extends Controller
{
    private function handleReview(int $id, string $status): RedirectResponse
    {
        $report = Report::findOrFail($id);

        if (!$this->isAuthorized($report)) {
            return back()->with('error', 'Not authorized to review this report.');
        }

        $report->update([
            'status' => $status,
            'coordinator_reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $label = ucfirst($status);

        return back()->with('success', "Report {$label} successfully.");
    }

    private function getCoordinatorClasses()
    {
        return UserClass::where('user_id', Auth::id())
            ->with('classModel')
            ->get()
            ->pluck('classModel')
            ->filter();
    }

    private function isAuthorized(Report $report): bool
    {
        $classIds = $this->getCoordinatorClasses()->pluck('id');

        return $classIds->isNotEmpty() && UserClass::whereIn('class_id', $classIds)
            ->where('user_id', $report->student_id)
            ->exists();
    }

    public function index(Request $request)
    {
        $coordinatorClasses = $this->getCoordinatorClasses();

        if ($coordinatorClasses->isEmpty()) {
            return back()->with('error', 'You are not assigned to any class.');
        }

        $classStudentIds = UserClass::whereIn('class_id', $coordinatorClasses->pluck('id'))->pluck('user_id');

        $classStudents = User::whereIn('id', $classStudentIds)
            ->where('role', 'student')
            ->with('userClass') // eager load so we can attach class_id
            ->get(['id', 'name']);

        $cleanedStudents = $classStudents->map(fn($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'class_id' => $s->userClass?->class_id,
        ])->all();

        $selectedStudentId = $request->integer('student_id');
        $isValidSelection = $selectedStudentId && $classStudents->pluck('id')->contains($selectedStudentId);

        $pendingReports = collect();
        $approvedReports = collect();
        $rejectedReports = collect();
        $stats = null;

        if ($isValidSelection) {
            $baseQuery = Report::with(['student', 'internship', 'reviewer'])
                ->where('student_id', $selectedStudentId);

            $pendingReports = (clone $baseQuery)->where('status', 'pending')->latest()->get();
            $approvedReports = (clone $baseQuery)->where('status', 'approved')->latest('reviewed_at')->get();
            $rejectedReports = (clone $baseQuery)->where('status', 'rejected')->latest('reviewed_at')->get();

            $stats = [
                'student' => $classStudents->find($selectedStudentId),
                'totalPending' => $pendingReports->count(),
                'totalApproved' => $approvedReports->count(),
                'totalRejected' => $rejectedReports->count(),
            ];
        }

        return view('coordinator.reports.index', compact(
            'coordinatorClasses',
            'cleanedStudents',
            'selectedStudentId',
            'pendingReports',
            'approvedReports',
            'rejectedReports',
            'stats',
        ));
    }

    public function approve(int $id): RedirectResponse
    {
        return $this->handleReview($id, 'approved');
    }

    public function reject(int $id): RedirectResponse
    {
        return $this->handleReview($id, 'rejected');
    }


}