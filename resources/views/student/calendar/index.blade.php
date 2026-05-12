@extends('layouts.auth')

@section('content')
    @php
        $weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
        $statusStyles = [
            'completed' => 'bg-emerald-50 text-emerald-900 border-emerald-200',
            'planned' => 'bg-blue-50 text-blue-900 border-blue-200',
            'missing' => 'bg-rose-50 text-rose-900 border-rose-200',
            'weekend' => 'bg-slate-100 text-slate-400 border-slate-200',
            'holiday' => 'bg-slate-100 text-slate-400 border-slate-200',
            'out' => 'bg-slate-50 text-slate-300 border-slate-200',
        ];

        $calendarCardClass =
            'bg-white p-4 max-w-7xl rounded-2xl border border-slate-300 shadow-[0_1px_3px_rgba(59,130,246,0.1)]';

        $periodStart = \Carbon\Carbon::parse($internship->start_date)->format('M d, Y');
        $periodEnd = \Carbon\Carbon::parse($internship->end_date)->format('M d, Y');
    @endphp

    <div x-data="calendarConfirmModal()" class="space-y-6">
        <x-page-header title="Internship Calendar" subtitle="Track completed hours and confirm planned days" />

        <div class="{{ $calendarCardClass }}">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                <div class="text-sm text-slate-600">
                    Internship period: <span class="font-semibold text-slate-800">{{ $periodStart }} -
                        {{ $periodEnd }}</span>
                </div>

                <div class="flex items-center gap-3">
                    @if ($canPrev)
                        <a href="{{ route('student.calendar', ['month' => $prevMonthKey]) }}"
                            class="px-3 py-1 rounded-lg border border-slate-300 text-sm text-slate-700 hover:bg-slate-100">
                            Prev
                        </a>
                    @else
                        <span class="px-3 py-1 rounded-lg border border-slate-200 text-sm text-slate-400">
                            Prev
                        </span>
                    @endif

                    <span class="text-lg font-semibold text-slate-900">
                        {{ $month->format('F Y') }}
                    </span>

                    @if ($canNext)
                        <a href="{{ route('student.calendar', ['month' => $nextMonthKey]) }}"
                            class="px-3 py-1 rounded-lg border border-slate-300 text-sm text-slate-700 hover:bg-slate-100">
                            Next
                        </a>
                    @else
                        <span class="px-3 py-1 rounded-lg border border-slate-200 text-sm text-slate-400">
                            Next
                        </span>
                    @endif
                </div>
            </div>

            <div class="grid min-w-0 text-[0.65rem] uppercase tracking-[0.12em] text-slate-500 mb-2"
                style="grid-template-columns: repeat(5, minmax(0, 1fr));">
                @foreach ($weekdays as $weekday)
                    <div class="py-2 col-span-1 text-center">{{ $weekday }}</div>
                @endforeach
            </div>

            <div class="flex flex-col gap-px bg-slate-200 rounded-xl overflow-hidden ">
                @foreach ($weeks as $week)
                    <div class="grid min-w-0 gap-px bg-slate-200" style="grid-template-columns: repeat(5, minmax(0, 1fr));">
                        @foreach ($week as $day)
                            @if (!$day['inMonth'])
                                <div class="min-h-[90px] min-w-0 bg-slate-50"></div>
                            @else
                                @php
                                    $cellClasses =
                                        $statusStyles[$day['status']] ?? 'bg-white text-slate-700 border-slate-200';
                                @endphp

                                <div @click="openConfirm('{{ $day['date'] }}', '{{ $day['plannedLabel'] }}')"
                                    class="min-h-[90px] min-w-0 overflow-hidden border p-1.5 relative {{ $cellClasses }} {{ $day['isToday'] ? 'ring-2 ring-blue-400' : '' }}">
                                    <div class="flex items-start justify-between">
                                        <span class="text-xs font-semibold">{{ $day['day'] }}</span>
                                        @if ($day['isToday'])
                                            <span class="text-[0.55rem] uppercase tracking-[0.12em]">Today</span>
                                        @endif
                                    </div>

                                    @if ($day['status'] === 'completed')
                                        <div class="mt-1 text-[0.6rem] uppercase tracking-[0.12em]">Logged</div>
                                        <div class="text-xs font-semibold">{{ $day['loggedLabel'] }}</div>
                                    @elseif($day['status'] === 'planned')
                                        <div class="mt-1 text-[0.6rem] uppercase tracking-[0.12em]">Planned</div>
                                        @if ($day['plannedLabel'])
                                            @if ($day['canConfirm'])
                                                <button type="button"
                                                    class="text-xs font-semibold decoration-blue-500">
                                                    {{ $day['plannedLabel'] }}
                                                </button>
                                                <div class="text-[0.6rem] text-slate-600 mt-1">Click to log</div>
                                            @else
                                                <div class="text-xs font-semibold">{{ $day['plannedLabel'] }}</div>
                                            @endif
                                        @else
                                            <div class="text-xs font-semibold">0H</div>
                                        @endif
                                    @elseif($day['status'] === 'missing')
                                        <div class="mt-1 text-[0.6rem] uppercase tracking-[0.12em]">Missing</div>
                                        <div class="text-xs font-semibold">0H</div>
                                    @elseif($day['status'] === 'holiday')
                                        <div class="mt-1 text-[0.6rem] uppercase tracking-[0.12em]">Holiday</div>
                                        @if ($day['holidayName'])
                                            <div class="text-[0.6rem] mt-1 truncate">{{ $day['holidayName'] }}</div>
                                        @endif
                                    @elseif($day['status'] === 'weekend')
                                        <div class="mt-1 text-[0.6rem] uppercase tracking-[0.12em]">Weekend</div>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endforeach
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-4 text-xs text-slate-600">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span> Completed
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-400"></span> Planned
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span> Missing
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-slate-300"></span> Holiday
                </div>
            </div>
            <div class="mt-2 text-xs text-slate-500">Weekends are excluded from the grid.</div>
        </div>

        <div x-show="open" x-transition.opacity x-cloak @keydown.escape.window="closeConfirm()"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/20 backdrop-blur-sm">
            <div class="absolute inset-0" @click="closeConfirm()"></div>

            <div x-show="open" x-transition.scale class="relative bg-white rounded-lg shadow-xl w-full max-w-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Confirm Planned Hours</h3>
                <p class="text-gray-600 mb-6">
                    Planned: <span class="font-semibold" x-text="plannedLabel"></span>
                </p>

                <form method="POST" action="{{ route('student.calendar.confirm') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="date" :value="date" />

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700">Start</label>
                            <input type="time" name="start_time" required
                                class="mt-1 w-full rounded-lg border border-gray-300 p-2 focus:ring-2 focus:ring-gray-400" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700">End</label>
                            <input type="time" name="end_time" required
                                class="mt-1 w-full rounded-lg border border-gray-300 p-2 focus:ring-2 focus:ring-gray-400" />
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="px-4 py-2 rounded bg-blue-600 hover:bg-blue-700 text-white">
                            Confirm Hours
                        </button>
                        <button type="button" @click="closeConfirm()"
                            class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400 text-gray-700">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function calendarConfirmModal() {
            return {
                open: false,
                date: '',
                plannedLabel: '',
                openConfirm(date, plannedLabel) {
                    this.date = date;
                    this.plannedLabel = plannedLabel || '';
                    this.open = true;
                },
                closeConfirm() {
                    this.open = false;
                },
            };
        }
    </script>
@endsection
