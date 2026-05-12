<?php

namespace App\Http\Controllers;

use App\Models\Hour;
use App\Models\Internship;
use App\Models\UserInternship;
use App\Services\InternshipCalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentCalendarController extends Controller
{
    public function index(Request $request, InternshipCalendarService $calendarService)
    {
        $studentId = $request->user()->id;

        $initial = $calendarService->buildCalendar(
            $studentId,
            $request->query('month')
        );

        $internship = $initial['internship'];
        $rangeStart = Carbon::parse($internship->start_date)->startOfMonth();
        $rangeEnd = Carbon::parse($internship->end_date)->startOfMonth();

        $months = [];
        $cursor = $rangeStart->copy();
        while ($cursor->lte($rangeEnd)) {
            $monthKey = $cursor->format('Y-m');
            $months[$monthKey] = $calendarService->buildCalendar($studentId, $monthKey);
            $cursor->addMonth();
        }

        return view('student.calendar.index', [
            'months' => $months,
            'selectedMonthKey' => $initial['month']->format('Y-m'),
            'stats' => $initial['stats'],
            'internship' => $internship,
        ]);
    }

    public function confirm(Request $request, InternshipCalendarService $calendarService)
    {
        $studentId = Auth::id();
        $internship = $this->getActiveInternship($studentId);

        $validated = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:' . $internship->start_date],
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ], [
            'date.before_or_equal' => 'You cannot log hours for a future date.',
            'date.after_or_equal' => 'Logged hours must be after your internship start date.',
            'end_time.after' => 'End time must be after start time.',
        ]);

        $timezone = config('app.timezone', 'Europe/Lisbon');
        $date = Carbon::parse($validated['date'], $timezone)->startOfDay();
        $today = Carbon::now($timezone)->startOfDay();
        $internshipEnd = Carbon::parse($internship->end_date, $timezone)->startOfDay();

        if ($date->gt($today)) {
            return back()->withErrors('You cannot confirm future days.');
        }

        if ($date->gt($internshipEnd)) {
            return back()->withErrors('Logged hours must be before the internship end date.');
        }

        if ($date->isWeekend()) {
            return back()->withErrors('Weekends cannot receive internship hours.');
        }

        if ($calendarService->holidayNameForDate($date)) {
            return back()->withErrors('Holidays cannot receive internship hours.');
        }

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
            Log::error('Error confirming planned hours: ', ['exception' => $e, 'user' => $studentId]);
            return back()->withErrors('An error occurred while logging hours. Please try again.');
        }
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
}
