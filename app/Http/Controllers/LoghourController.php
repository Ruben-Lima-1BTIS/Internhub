<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Hour;
use App\Models\Internship;
use App\Models\UserInternship;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoghourController extends Controller
{
    private function formatHours(float $hours): string
    {
        $h = (int) $hours;
        $m = (int) round(($hours - $h) * 60);
        return sprintf('%02d:%02d', $h, $m);
    }

    private function calculateWorkedHours(string $startTime, string $endTime): float
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
        return max($start->floatDiffInHours($end) - 1, 0);
    }

    private function getActiveInternship(int $studentId): Internship
    {
        $internshipIds = UserInternship::where('user_id', $studentId)->pluck('internship_id');

        return Internship::whereIn('id', $internshipIds)
            ->where('status', 'active')
            ->firstOrFail();
    }

    private function futureLogPreventionRules(string $internshipStartDate): array
    {
        return [
            'date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:' . $internshipStartDate],
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ];
    }

    public function index(Request $request)
    {
        $studentId = $request->user()->id;

        $logs = Hour::where('student_id', $studentId)
            ->orderByDesc('date')
            ->get();

        $totalHours = $logs->where('status', 'approved')
            ->whereNotNull('duration_hours')
            ->sum(fn($log) => (float) $log->duration_hours);

        $totalHoursFormatted = $this->formatHours($totalHours);

        // take the last 10 logs for display
        $logsLimitedForDisplay = $logs->take(10);

        return view('student.hours.index', compact('logs', 'totalHoursFormatted', 'logsLimitedForDisplay'));
    }

    public function store(Request $request)
    {
        $studentId = Auth::id();
        $internship = $this->getActiveInternship($studentId);

        $rules = $this->futureLogPreventionRules($internship->start_date);

        $validated = $request->validate($rules, [
            'date.before_or_equal' => 'You cannot log hours for a future date.',
            'date.after_or_equal' => 'Logged hours must be after your internship start date.',
            'end_time.after' => 'End time must be after start time.',
        ]);

        DB::beginTransaction();

        try {
            $existingHours = Hour::where('student_id', $studentId)
                ->whereDate('date', $validated['date'])
                ->lockForUpdate()
                ->get();

            if ($existingHours->where('status', '!=', 'rejected')->count() > 0) {
                DB::rollBack();
                return back()->withErrors('You already logged hours for this day.');
            }

            $existingHours->where('status', 'rejected')->each->delete();

            $hoursWorked = $this->calculateWorkedHours(
                $validated['start_time'],
                $validated['end_time']
            );

        if ($hoursWorked < 4) {
            DB::rollBack();
            return back()->withErrors('Logged hours must be at least 4 hours, excluding 1 hour for lunch.');
        }

        Hour::create([
            'student_id' => $studentId,
            'internship_id' => $internship->id,
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'duration_hours' => round($hoursWorked, 2),
        ]);

        DB::commit();
        return back()->with('success', 'Hours logged successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error logging hours: ', ['exception' => $e, 'user' => $studentId]);
            return back()->withErrors('An error occurred while logging hours. Please try again.');
        }
    }

}