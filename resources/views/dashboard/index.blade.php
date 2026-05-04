-- Active: 1767625217593@@127.0.0.1@3306@internhub_nova
@extends('layouts.auth')

@section('content')
    <h1 class="text-2xl font-bold mb-6">Welcome, {{ $user->name }}</h1>

    @if ($user->isAdmin())
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-800 tracking-tight mb-1">System Overview</h2>
            <p class="text-gray-600 text-sm">Key statistics and system metrics</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <x-stat-card title="Total Users" value="{{ $stats['totalUsers'] }}" icon="fas-users" />
            <x-stat-card title="Coordinators" value="{{ $stats['totalCoordinators'] }}" icon="fas-user-tie" />
            <x-stat-card title="Supervisors" value="{{ $stats['totalSupervisors'] }}" icon="fas-user-check" />
            <x-stat-card title="Students" value="{{ $stats['totalStudents'] }}" icon="fas-user-graduate" />
            <x-stat-card title="Companies" value="{{ $stats['totalCompanies'] }}" icon="fas-building" />
            <x-stat-card title="Classes" value="{{ $stats['totalClasses'] }}" icon="fas-chalkboard" />
            <x-stat-card title="Internships" value="{{ $stats['totalInternships'] }}" icon="fas-briefcase" />
        </div>
    @elseif($user->isCoordinator())
        @php
            $classOptions = collect($stats['classes'])
                ->map(fn($c) => ['id' => $c['id'], 'name' => $c['sigla']])
                ->toArray();
        @endphp

        <script>
            window.coordinatorClasses = @json($stats['classes']);
        </script>

        <div class="space-y-8 w-full" x-data="coordinatorDashboard()" x-init="init()">

            <div class="flex flex-col sm:flex-row gap-6 w-full">
                <div class="flex-1">
                    <x-stat-card title="My Classes" value="{{ $stats['myClasses'] }}" icon="fas-chalkboard-teacher" />
                </div>
                <div class="flex-1">
                    <x-stat-card title="My Students" value="{{ $stats['myStudents'] }}" icon="fas-user-graduate" />
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">Class Overview</h3>
                        <p class="text-sm text-gray-500 mt-0.5">Click on a student to view their details</p>
                    </div>
                    <div class="sm:w-72">
                        <x-select name="class_picker" :options="$classOptions" x-model="selectedClassId" />
                    </div>
                </div>

                <template x-for="cls in classes" :key="cls.id">
                    <div x-show="selectedClassId == cls.id">

                        <p x-show="cls.students.length === 0" class="text-gray-400 text-center py-10">
                            No students in this class.
                        </p>

                        <div x-show="cls.students.length > 0" class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-700">
                                <thead class="text-xs uppercase bg-gray-50 text-gray-500 border-b">
                                    <tr>
                                        <th class="px-4 py-3">Student</th>
                                        <th class="px-4 py-3">Internship</th>
                                        <th class="px-4 py-3 text-center">Approved</th>
                                        <th class="px-4 py-3 text-center">Pending</th>
                                        <th class="px-4 py-3 text-center">Remaining</th>
                                        <th class="px-4 py-3 text-center">Reports</th>
                                        <th class="px-4 py-3">Progress</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="student in cls.students" :key="student.id">
                                        <tr class="border-b hover:bg-gray-50 cursor-pointer transition-colors"
                                            @click="openStudent(student)">
                                            <td class="px-4 py-3 font-semibold text-slate-900" x-text="student.name"></td>
                                            <td class="px-4 py-3 font-semibold text-slate-900"
                                                x-text="student.internship ?? '—'"></td>
                                            <td class="px-4 py-3 text-center font-semibold text-slate-900"
                                                x-text="student.approved_hours + 'h'"></td>
                                            <td class="px-4 py-3 text-center font-semibold text-slate-900"
                                                x-text="student.pending_hours + 'h'"></td>
                                            <td class="px-4 py-3 text-center font-semibold text-slate-900"
                                                x-text="student.remaining_hours + 'h'"></td>
                                            <td class="px-4 py-3 text-center font-semibold text-slate-900"
                                                x-text="student.reports_submitted"></td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-2 min-w-[110px]">
                                                    <div class="flex-1 bg-gray-200 rounded-full h-2 overflow-hidden">
                                                        <div class="bg-indigo-500 h-2 rounded-full transition-all duration-500"
                                                            :style="'width:' + progressPercent(student) + '%'"></div>
                                                    </div>
                                                    <span class="text-xs text-gray-500 w-8 text-right"
                                                        x-text="progressPercent(student) + '%'"></span>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </template>

            </div>

            <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-black/40" @click.self="closeModal()"
                x-cloak>
                <div x-show="open" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" @click.stop>

                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900" x-text="selectedStudent?.name"></h3>
                            <p class="text-sm text-gray-500 mt-0.5">
                                <span x-text="selectedStudent?.internship ?? 'No internship assigned'"></span>
                                <template x-if="selectedStudent?.company">
                                    <span> &mdash; <span x-text="selectedStudent.company"></span></span>
                                </template>
                            </p>
                        </div>

                        <button @click="closeModal()" class="text-gray-400 hover:text-gray-600 p-1.5">
                            ✕
                        </button>
                    </div>

                    <div class="px-6 py-6 space-y-7">

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center">
                                <p class="text-xs text-gray-500 mb-1">Approved</p>
                                <p class="text-xl font-bold text-emerald-600"
                                    x-text="(selectedStudent?.approved_hours ?? 0) + 'h'"></p>
                            </div>

                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center">
                                <p class="text-xs text-gray-500 mb-1">Pending</p>
                                <p class="text-xl font-bold text-amber-500"
                                    x-text="(selectedStudent?.pending_hours ?? 0) + 'h'"></p>
                            </div>

                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center">
                                <p class="text-xs text-gray-500 mb-1">Rejected</p>
                                <p class="text-xl font-bold text-red-500"
                                    x-text="(selectedStudent?.rejected_hours ?? 0) + 'h'"></p>
                            </div>

                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center">
                                <p class="text-xs text-gray-500 mb-1">Remaining</p>
                                <p class="text-xl font-bold text-gray-700"
                                    x-text="(selectedStudent?.remaining_hours ?? 0) + 'h'"></p>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-xs text-gray-500 mb-1.5">
                                <span>Overall progress</span>
                                <span
                                    x-text="progressPercent(selectedStudent) + '% of ' + (selectedStudent?.total_required ?? 0) + 'h'"></span>
                            </div>

                            <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                <div class="bg-indigo-500 h-2 rounded-full transition-all duration-700"
                                    :style="'width:' + progressPercent(selectedStudent) + '%'"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6 w-full">

                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide text-center mb-3">Hour Distribution</p>
                                <div class="relative w-full h-80">
                                    <canvas id="studentPieChart"></canvas>
                                </div>
                            </div>

                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide text-center mb-3">This Week</p>
                                <div class="relative w-full h-80">
                                    <canvas id="studentBarChart"></canvas>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>
            </div>

        </div>

        @push('scripts')
            @vite('resources/js/dashboard.js')
            <script>
                function coordinatorDashboard() {
                    return {
                        classes: window.coordinatorClasses ?? [],
                        selectedClassId: window.coordinatorClasses?.[0]?.id ?? null,
                        selectedStudent: null,
                        open: false,

                        init() {
                            this.$watch('selectedClassId', () => this.closeModal());
                            document.addEventListener('keydown', (e) => {
                                if (e.key === 'Escape' && this.open) this.closeModal();
                            });
                        },

                        progressPercent(student) {
                            if (!student?.total_required || student.total_required === 0) return 0;
                            const done = student.approved_hours + student.pending_hours;
                            return Math.min(Math.round((done / student.total_required) * 100), 100);
                        },

                        openStudent(student) {
                            this.selectedStudent = student;
                            this.open = true;
                            // Wait for the modal transition (200ms) to finish before
                            // Chart.js tries to measure the canvas dimensions.
                            this.$nextTick(() => setTimeout(() => window.coordinatorCharts.render(student), 250));
                        },

                        closeModal() {
                            this.open = false;
                            this.selectedStudent = null;
                            window.coordinatorCharts.destroy();
                        },
                    };
                }
            </script>
        @endpush
    @elseif($user->isSupervisor())
        <p>Supervisor dashboard content here</p>
    @elseif($user->isStudent())
        <div class="space-y-8 w-full">

            <div class="flex flex-col sm:flex-row gap-8 w-full">
                <div class="flex-1">
                    <x-stat-card title="Hours Completed" value="{{ $stats['approvedHours'] }}" icon="fas-clock" />
                </div>
                <div class="flex-1">
                    <x-stat-card title="Submitted Reports" value="{{ $stats['reportsSubmitted'] }}"
                        icon="fas-file-alt" />
                </div>
            </div>

            <div class="flex flex-col xl:flex-row gap-8 w-full">
                <div
                    class="flex-1 bg-white rounded-lg shadow-md border border-gray-200 hover:shadow-lg transition-shadow p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-6 text-center">Hour Distribution</h3>
                    <div class="relative w-full h-[320px]">
                        <canvas id="hoursPieChart" class="w-full h-full"></canvas>
                    </div>
                </div>
                <div
                    class="flex-1 bg-white rounded-lg shadow-md border border-gray-200 hover:shadow-lg transition-shadow p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-6 text-center">Weekly Progress</h3>
                    <div class="relative w-full h-[320px]">
                        <canvas id="hoursBarChart" class="w-full h-full"></canvas>
                    </div>
                </div>
            </div>

        </div>

        @php $weeklyHours = $stats['weeklyHours'] ?? [0, 0, 0, 0, 0]; @endphp
        <script>
            window.dashboardStats = {
                approvedHours: {{ (int) ($stats['approvedHours'] ?? 0) }},
                remainingHours: {{ (int) ($stats['remainingHours'] ?? 0) }},
                pendingHours: {{ (int) ($stats['pendingHours'] ?? 0) }},
                weeklyHours: @json($weeklyHours),
                minHoursDay: 6,
            };
        </script>

        @push('scripts')
            @vite('resources/js/dashboard.js')
        @endpush
    @else
        <p>Unknown role</p>
    @endif
@endsection
