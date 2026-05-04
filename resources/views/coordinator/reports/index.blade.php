@extends('layouts.auth')

@section('content')
<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">Report Approval</h1>
            <div class="bg-white rounded-lg shadow p-6">
                <form method="GET" action="{{ route('coordinator.reports.index') }}" id="studentForm">

                    @if($coordinatorClasses->count() > 1)
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Class</label>
                            <select id="classFilter"
                                class="border border-gray-300 rounded-lg p-2 w-full focus:outline-none focus:ring-2 focus:ring-slate-500">
                                <option value="">All classes</option>
                                @foreach($coordinatorClasses as $class)
                                    <option value="{{ $class->id }}">{{ $class->sigla }} — {{ $class->course }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Student</label>
                        <select name="student_id" id="studentSelect"
                            class="border border-gray-300 rounded-lg p-2 w-full focus:outline-none focus:ring-2 focus:ring-slate-500"
                            onchange="document.getElementById('studentForm').submit()">
                            <option value="">— Select a student —</option>
                            @foreach($cleanedStudents as $student)
                                <option
                                    value="{{ $student['id'] }}"
                                    data-class-id="{{ $student['class_id'] }}"
                                    {{ $selectedStudentId == $student['id'] ? 'selected' : '' }}>
                                    {{ $student['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </form>
            </div>
        </div>

        @if($stats)
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-8">
                <x-stat-card title="Student Name"     :value="$stats['student']->name" color="gray"   />
                <x-stat-card title="Pending Reports"  :value="$stats['totalPending']"  color="yellow" />
                <x-stat-card title="Approved Reports" :value="$stats['totalApproved']" color="green"  />
            </div>

            <x-pending-reports-table :reports="$pendingReports" />
            <x-reviewed-reports-table :reports="$approvedReports" label="Approved Reports" />

            @if($pendingReports->count() === 0 && $approvedReports->count() === 0 && $rejectedReports->count() === 0)
                <x-empty-state message="No reports found for this student" />
            @endif
        @else
            <x-empty-state message="Please select a student to view their reports" />
        @endif

    </div>
</div>

<x-report-review-modal
    approve-url="{{ route('report.approve', ['id' => '__ID__']) }}"
    reject-url="{{ route('report.reject', ['id' => '__ID__']) }}"
/>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const classFilter   = document.getElementById('classFilter');
        const studentSelect = document.getElementById('studentSelect');

        if (!classFilter || !studentSelect) return;

        // Snapshot all student options (excluding the placeholder)
        const allStudentOpts = Array.from(studentSelect.querySelectorAll('option[data-class-id]'));

        classFilter.addEventListener('change', function () {
            const classId = this.value;

            // Remove current student options, keep placeholder
            studentSelect.querySelectorAll('option[data-class-id]').forEach(o => o.remove());
            studentSelect.value = '';

            // Re-insert matching options (or all if no filter)
            allStudentOpts
                .filter(o => !classId || o.dataset.classId === classId)
                .forEach(o => studentSelect.appendChild(o.cloneNode(true)));
        });
    });
</script>
@endsection