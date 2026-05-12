<?php

namespace App\Services;

use App\Models\Hour;
use App\Models\Internship;
use App\Models\UserInternship;
use Carbon\Carbon;
use Yasumi\Yasumi;

class InternshipCalendarService
{
    private array $holidayCache = [];

    public function buildCalendar(int $studentId, ?string $monthKey = null): array
    {
        $timezone = config('app.timezone', 'Europe/Lisbon');
        $internship = $this->getActiveInternship($studentId);

        $start = Carbon::parse($internship->start_date, $timezone)->startOfDay();
        $end = Carbon::parse($internship->end_date, $timezone)->startOfDay();
        $today = Carbon::now($timezone)->startOfDay();

        // count all days except weekends
        $month = $this->resolveMonth($monthKey, $start, $end, $today, $timezone);

        $hours = Hour::where('student_id', $studentId)
            ->where('internship_id', $internship->id)
            ->get();

        $loggedMinutesByDate = [];
        $completedMinutes = 0;

        foreach ($hours as $hour) {
            if ($hour->status === 'rejected') {
                continue;
            }

            $minutes = $this->calculateWorkedMinutes($hour->start_time, $hour->end_time);
            if ($minutes <= 0) {
                continue;
            }

            $completedMinutes += $minutes;
            $dateKey = Carbon::parse($hour->date, $timezone)->format('Y-m-d');
            $loggedMinutesByDate[$dateKey] = ($loggedMinutesByDate[$dateKey] ?? 0) + $minutes;
        }

        $totalRequiredMinutes = (int) round($internship->total_hours_required * 60);
        $remainingMinutes = max($totalRequiredMinutes - $completedMinutes, 0);

        $holidayMap = $this->holidayMap($start, $end);
        $plannedDates = $this->collectPlannableDates($start, $end, $today, $holidayMap, $loggedMinutesByDate);
        $plannedMinutesByDate = $this->distributePlannedMinutes($plannedDates, $remainingMinutes);

        $weeks = $this->buildMonthGrid(
            $month,
            $start,
            $end,
            $today,
            $holidayMap,
            $loggedMinutesByDate,
            $plannedMinutesByDate
        );

        $stats = [
            'totalRequired' => $this->formatHoursFromMinutes($totalRequiredMinutes),
            'completed' => $this->formatHoursFromMinutes($completedMinutes),
            'remaining' => $this->formatHoursFromMinutes($remainingMinutes),
            'remainingDays' => count($plannedDates),
        ];

        $prevMonth = $month->copy()->subMonth()->startOfMonth();
        $nextMonth = $month->copy()->addMonth()->startOfMonth();
        $minMonth = $start->copy()->startOfMonth();
        $maxMonth = $end->copy()->startOfMonth();

        return [
            'internship' => $internship,
            'month' => $month,
            'weeks' => $weeks,
            'stats' => $stats,
            'prevMonthKey' => $prevMonth->format('Y-m'),
            'nextMonthKey' => $nextMonth->format('Y-m'),
            'canPrev' => $prevMonth->gte($minMonth),
            'canNext' => $nextMonth->lte($maxMonth),
        ];
    }

    public function holidayNameForDate(Carbon $date): ?string
    {
        $dateKey = $date->format('Y-m-d');
        $map = $this->holidayMapForYear((int) $date->year);

        return $map[$dateKey] ?? null;
    }

    private function resolveMonth(
        ?string $monthKey,
        Carbon $start,
        Carbon $end,
        Carbon $today,
        string $timezone
    ): Carbon {
        $defaultMonth = $today->between($start, $end, true)
            ? $today->copy()
            : $start->copy();

        if (!$monthKey) {
            return $defaultMonth->startOfMonth();
        }

        try {
            $month = Carbon::createFromFormat('Y-m', $monthKey, $timezone)->startOfMonth();
        } catch (\Throwable $e) {
            return $defaultMonth->startOfMonth();
        }

        if ($month->lt($start->copy()->startOfMonth())) {
            return $start->copy()->startOfMonth();
        }

        if ($month->gt($end->copy()->startOfMonth())) {
            return $end->copy()->startOfMonth();
        }

        return $month;
    }

    private function collectPlannableDates(
        Carbon $start,
        Carbon $end,
        Carbon $today,
        array $holidayMap,
        array $loggedMinutesByDate
    ): array {
        $planStart = $today->copy();
        if ($planStart->lt($start)) {
            $planStart = $start->copy();
        }

        $dates = [];
        $cursor = $planStart->copy();
        while ($cursor->lte($end)) {
            $dateKey = $cursor->format('Y-m-d');
            $isWeekend = $cursor->isWeekend();
            $isHoliday = array_key_exists($dateKey, $holidayMap);
            $hasLogged = array_key_exists($dateKey, $loggedMinutesByDate);

            if (!$isWeekend && !$isHoliday && !$hasLogged) {
                $dates[] = $dateKey;
            }

            $cursor->addDay();
        }

        return $dates;
    }

    private function distributePlannedMinutes(array $dateKeys, int $remainingMinutes): array
    {
        $count = count($dateKeys);
        if ($count === 0 || $remainingMinutes <= 0) {
            return [];
        }

        $units = (int) round($remainingMinutes / 30);
        if ($units <= 0) {
            return [];
        }

        $baseUnits = intdiv($units, $count);
        $baseEvenUnits = ($baseUnits % 2 === 0) ? $baseUnits : max($baseUnits - 1, 0);

        $distributionUnits = array_fill(0, $count, $baseEvenUnits);
        $remainingUnits = $units - ($baseEvenUnits * $count);

        $fullExtras = intdiv($remainingUnits, 2);
        $halfExtras = $remainingUnits % 2;

        if ($fullExtras > 0) {
            $acc = 0;
            $added = 0;
            for ($i = 0; $i < $count && $added < $fullExtras; $i++) {
                $acc += $fullExtras;
                if ($acc >= $count) {
                    $distributionUnits[$i] += 2;
                    $acc -= $count;
                    $added++;
                }
            }
        }

        if ($halfExtras > 0) {
            $minUnits = min($distributionUnits);
            $index = array_search($minUnits, $distributionUnits, true);
            if ($index === false) {
                $index = 0;
            }
            $distributionUnits[$index] += 1;
        }

        $plannedMinutesByDate = [];
        foreach ($dateKeys as $i => $dateKey) {
            $plannedMinutesByDate[$dateKey] = $distributionUnits[$i] * 30;
        }

        return $plannedMinutesByDate;
    }

    private function buildMonthGrid(
        Carbon $month,
        Carbon $start,
        Carbon $end,
        Carbon $today,
        array $holidayMap,
        array $loggedMinutesByDate,
        array $plannedMinutesByDate
    ): array {
        $startOfGrid = $month->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $endOfGrid = $month->copy()->endOfMonth()->endOfWeek(Carbon::FRIDAY);

        $weeks = [];
        $cursor = $startOfGrid->copy();

        while ($cursor->lte($endOfGrid)) {
            $week = [];

            while (count($week) < 5 && $cursor->lte($endOfGrid)) {
                if ($cursor->isWeekend()) {
                    $cursor->addDay();
                    continue;
                }
                $dateKey = $cursor->format('Y-m-d');
                $inMonth = $cursor->month === $month->month;
                $inRange = $cursor->between($start, $end, true);
                $isWeekend = $cursor->isWeekend();
                $holidayName = $holidayMap[$dateKey] ?? null;
                $isHoliday = $holidayName !== null;
                $isToday = $cursor->isSameDay($today);

                $loggedMinutes = $loggedMinutesByDate[$dateKey] ?? 0;
                $plannedMinutes = $plannedMinutesByDate[$dateKey] ?? 0;

                $status = 'out';
                if ($inMonth && $inRange) {
                    if ($isHoliday) {
                        $status = 'holiday';
                    } elseif ($isWeekend) {
                        $status = 'weekend';
                    } elseif ($loggedMinutes > 0) {
                        $status = 'completed';
                    } elseif ($cursor->lt($today)) {
                        $status = 'missing';
                    } else {
                        $status = 'planned';
                    }
                }

                $week[] = [
                    'date' => $dateKey,
                    'day' => $cursor->day,
                    'inMonth' => $inMonth,
                    'inRange' => $inRange,
                    'isWeekend' => $isWeekend,
                    'isHoliday' => $isHoliday,
                    'holidayName' => $holidayName,
                    'isToday' => $isToday,
                    'status' => $status,
                    'loggedLabel' => $loggedMinutes > 0 ? $this->formatHoursFromMinutes($loggedMinutes) : null,
                    'plannedLabel' => $plannedMinutes > 0 ? $this->formatHoursFromMinutes($plannedMinutes) : null,
                    'canConfirm' => $status === 'planned' && $cursor->lte($today) && $plannedMinutes > 0,
                ];

                $cursor->addDay();
            }

            $weeks[] = $week;
        }

        return $weeks;
    }

    private function holidayMap(Carbon $start, Carbon $end): array
    {
        $map = [];
        for ($year = $start->year; $year <= $end->year; $year++) {
            foreach ($this->holidayMapForYear((int) $year) as $date => $name) {
                $map[$date] = $name;
            }
        }

        return $map;
    }

    private function holidayMapForYear(int $year): array
    {
        if (array_key_exists($year, $this->holidayCache)) {
            return $this->holidayCache[$year];
        }

        $provider = Yasumi::create('Portugal', $year);
        $map = [];

        foreach ($provider as $holiday) {
            $map[$holiday->format('Y-m-d')] = $holiday->getName();
        }

        $this->holidayCache[$year] = $map;

        return $map;
    }

    private function getActiveInternship(int $studentId): Internship
    {
        $internshipIds = UserInternship::where('user_id', $studentId)->pluck('internship_id');

        return Internship::whereIn('id', $internshipIds)
            ->where('status', 'active')
            ->firstOrFail();
    }

    private function calculateWorkedMinutes(string $startTime, string $endTime): int
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        return max($start->diffInMinutes($end) - 60, 0);
    }

    private function formatHoursFromMinutes(int $minutes): string
    {
        $hours = $minutes / 60;
        $rounded = round($hours * 2) / 2;
        $whole = (int) round($rounded);

        if (abs($rounded - $whole) < 0.001) {
            return $whole . 'H';
        }

        return number_format($rounded, 1, '.', '') . 'H';
    }
}
