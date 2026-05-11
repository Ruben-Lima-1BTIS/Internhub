@extends('layouts.auth')

@section('content')
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-800 tracking-tight">Management Tools</h2>
        <p class="text-sm text-gray-400 mt-0.5">View and delete system resources</p>
    </div>

    <x-tabs :tabs="[
        'tabStudents' => 'Students',
        'tabSupervisors' => 'Supervisors',
        'tabCoordinators' => 'Coordinators',
        'tabCompanies' => 'Companies',
        'tabClasses' => 'Classes',
        'tabInternships' => 'Internships',
        'tabUnassign' => 'Unassign Internship',
    ]" default="tabStudents">

        <div id="tabStudents" class="tab-content hidden">
            <x-list-card title="Students" icon="fas-user-graduate">

                <select id="studentFilterType"
                    class="border border-gray-300 rounded-lg p-2 w-full mb-2 focus:outline-none focus:ring-2 focus:ring-slate-500">
                    <option value="">Select filter type</option>
                    <option value="class">Filter by Class</option>
                    <option value="internship">Filter by Internship</option>
                </select>

                <select id="studentClassFilter"
                    class="border border-gray-300 rounded-lg p-2 w-full mb-4 focus:outline-none focus:ring-2 focus:ring-slate-500 hidden">
                    <option value="">Select a class</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}">{{ $class->sigla }} — {{ $class->course }}</option>
                    @endforeach
                </select>

                <select id="studentInternshipFilter"
                    class="border border-gray-300 rounded-lg p-2 w-full mb-4 focus:outline-none focus:ring-2 focus:ring-slate-500 hidden">
                    <option value="">Select an internship</option>
                    @foreach ($internships as $internship)
                        <option value="{{ $internship->id }}">{{ $internship->title }}</option>
                    @endforeach
                </select>

                <div id="studentList">
                    @forelse($students as $student)
                        <div class="student-row flex items-center justify-between py-3 border-b border-gray-100 last:border-0 hidden"
                            data-class-id="{{ $student->userClass?->class_id }}"
                            data-internship-ids="{{ json_encode($student->internships->pluck('id')) }}">
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ $student->name }}</p>
                                <p class="text-xs text-gray-400">{{ $student->email }}</p>
                            </div>
                            <form method="POST" action="{{ route('hr.student.delete') }}"
                                onsubmit="return confirm('Delete {{ $student->name }}?')">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $student->id }}">
                                <x-submit-button type="submit" color="bg-red-50 hover:bg-red-100">
                                    <span class="flex items-center gap-1 text-red-600">
                                        <x-fas-trash class="w-3 h-3 text-red-600" /> Delete
                                    </span>
                                </x-submit-button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 text-center py-4">No students found.</p>
                    @endforelse
                    <p id="noStudentsMsg" class="text-sm text-gray-400 text-center py-4 hidden">No students match this
                        filter.</p>
                </div>

            </x-list-card>
        </div>

        <div id="tabSupervisors" class="tab-content hidden">
            <x-list-card title="Supervisors" icon="fas-user-tie">

                <select id="supervisorCompanyFilter"
                    class="border border-gray-300 rounded-lg p-2 w-full mb-4 focus:outline-none focus:ring-2 focus:ring-slate-500">
                    <option value="">Select a company</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>

                <div id="supervisorList">
                    @forelse($supervisors as $supervisor)
                        <div class="supervisor-row flex items-center justify-between py-3 border-b border-gray-100 last:border-0 hidden"
                            data-company-id="{{ $supervisor->company_id }}">
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ $supervisor->name }}</p>
                                <p class="text-xs text-gray-400">{{ $supervisor->email }}</p>
                            </div>
                            <form method="POST" action="{{ route('hr.supervisor.delete') }}"
                                onsubmit="return confirm('Delete {{ $supervisor->name }}?')">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $supervisor->id }}">
                                <x-submit-button type="submit" color="bg-red-50 hover:bg-red-100">
                                    <span class="flex items-center gap-1 text-red-600">
                                        <x-fas-trash class="w-3 h-3 text-red-600" /> Delete
                                    </span>
                                </x-submit-button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 text-center py-4">No supervisors found.</p>
                    @endforelse
                    <p id="noSupervisorsMsg" class="text-sm text-gray-400 text-center py-4 hidden">No supervisors in this
                        company.</p>
                </div>

            </x-list-card>
        </div>

        <div id="tabCoordinators" class="tab-content hidden">
            <x-list-card title="Coordinators" icon="fas-chalkboard-user">
                @forelse($coordinators as $coordinator)
                    <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $coordinator->name }}</p>
                            <p class="text-xs text-gray-400">{{ $coordinator->email }}</p>
                        </div>
                        <form method="POST" action="{{ route('hr.coordinator.delete') }}"
                            onsubmit="return confirm('Delete {{ $coordinator->name }}?')">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $coordinator->id }}">
                            <x-submit-button type="submit" color="bg-red-50 hover:bg-red-100">
                                <span class="flex items-center gap-1 text-red-600">
                                    <x-fas-trash class="w-3 h-3 text-red-600" /> Delete
                                </span>
                            </x-submit-button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 text-center py-4">No coordinators found.</p>
                @endforelse
            </x-list-card>
        </div>

        <div id="tabCompanies" class="tab-content hidden">
            <x-list-card title="Companies" icon="fas-building">
                @forelse($companies as $company)
                    <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $company->name }}</p>
                            <p class="text-xs text-gray-400">{{ $company->email }}</p>
                        </div>
                        <form method="POST" action="{{ route('hr.company.delete') }}"
                            onsubmit="return confirm('Delete {{ $company->name }}?')">
                            @csrf
                            <input type="hidden" name="company_id" value="{{ $company->id }}">
                            <x-submit-button type="submit" color="bg-red-50 hover:bg-red-100">
                                <span class="flex items-center gap-1 text-red-600">
                                    <x-fas-trash class="w-3 h-3 text-red-600" /> Delete
                                </span>
                            </x-submit-button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 text-center py-4">No companies found.</p>
                @endforelse
            </x-list-card>
        </div>

        <div id="tabClasses" class="tab-content hidden">
            <x-list-card title="Classes" icon="fas-chalkboard">
                @forelse($classes as $class)
                    <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $class->sigla }}</p>
                            <p class="text-xs text-gray-400">{{ $class->course }} — {{ $class->year }}</p>
                        </div>
                        <form method="POST" action="{{ route('hr.class.delete') }}"
                            onsubmit="return confirm('Delete {{ $class->sigla }}?')">
                            @csrf
                            <input type="hidden" name="class_id" value="{{ $class->id }}">
                            <x-submit-button type="submit" color="bg-red-50 hover:bg-red-100">
                                <span class="flex items-center gap-1 text-red-600">
                                    <x-fas-trash class="w-3 h-3 text-red-600" /> Delete
                                </span>
                            </x-submit-button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 text-center py-4">No classes found.</p>
                @endforelse
            </x-list-card>
        </div>

        <div id="tabInternships" class="tab-content hidden">
            <x-list-card title="Internships" icon="fas-briefcase">

                <select id="internshipCompanyFilter"
                    class="border border-gray-300 rounded-lg p-2 w-full mb-4 focus:outline-none focus:ring-2 focus:ring-slate-500">
                    <option value="">Select a company</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>

                <div id="internshipList">
                    @forelse($internships as $internship)
                        <div class="internship-row flex items-center justify-between py-3 border-b border-gray-100 last:border-0 hidden"
                            data-company-id="{{ $internship->company_id }}">
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ $internship->title }}</p>
                                <p class="text-xs text-gray-400">{{ $internship->start_date }} →
                                    {{ $internship->end_date }}</p>
                            </div>
                            <form method="POST" action="{{ route('hr.internship.delete') }}"
                                onsubmit="return confirm('Delete {{ $internship->title }}?')">
                                @csrf
                                <input type="hidden" name="internship_id" value="{{ $internship->id }}">
                                <x-submit-button type="submit" color="bg-red-50 hover:bg-red-100">
                                    <span class="flex items-center gap-1 text-red-600">
                                        <x-fas-trash class="w-3 h-3 text-red-600" /> Delete
                                    </span>
                                </x-submit-button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 text-center py-4">No internships found.</p>
                    @endforelse
                    <p id="noInternshipsMsg" class="text-sm text-gray-400 text-center py-4 hidden">No internships for this
                        company.</p>
                </div>

            </x-list-card>
        </div>

        <div id="tabUnassign" class="tab-content hidden">
            <x-ih-card title="Unassign Internship" icon="fas-link-slash" action="{{ route('hr.unassign') }}">

                <x-select name="role" label="User's Role" id="roleSelectUnassign" required :options="[['id' => 'student', 'name' => 'Student'], ['id' => 'supervisor', 'name' => 'Supervisor']]" />

                <div id="unassignStudentWrapper" class="hidden">
                    <label class="block mb-2 font-semibold">Filter by Class</label>
                    <select id="unassignClassFilter"
                        class="border border-gray-300 rounded-lg p-2 w-full mb-4 focus:outline-none focus:ring-2 focus:ring-slate-500">
                        <option value="">Select a class</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->sigla }} — {{ $class->course }}</option>
                        @endforeach
                    </select>

                    <label class="block mb-2 font-semibold">Student</label>
                    <select name="student_id" id="unassignStudentSelect"
                        class="border border-gray-300 rounded-lg p-2 w-full mb-4 focus:outline-none focus:ring-2 focus:ring-slate-500">
                        <option value="">Select a class first</option>
                        @foreach ($students->filter(fn($s) => $s->internships->isNotEmpty()) as $student)
                            <option value="{{ $student->id }}" data-class-id="{{ $student->userClass?->class_id }}">
                                {{ $student->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div id="unassignSupervisorWrapper" class="hidden">
                    <label class="block mb-2 font-semibold">Filter by Company</label>
                    <select id="unassignCompanyFilter"
                        class="border border-gray-300 rounded-lg p-2 w-full mb-4 focus:outline-none focus:ring-2 focus:ring-slate-500">
                        <option value="">Select a company</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>

                    <label class="block mb-2 font-semibold">Supervisor</label>
                    <select name="supervisor_id" id="unassignSupervisorSelect"
                        class="border border-gray-300 rounded-lg p-2 w-full mb-4 focus:outline-none focus:ring-2 focus:ring-slate-500">
                        <option value="">Select a company first</option>
                        @foreach ($supervisors as $supervisor)
                            <option value="{{ $supervisor->id }}" data-company-id="{{ $supervisor->company_id }}">
                                {{ $supervisor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <x-select name="internship_id" label="Internship" required :options="$internships->map(fn($i) => ['id' => $i->id, 'name' => $i->title])" />

            </x-ih-card>
        </div>

    </x-tabs>

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            function filterRows(rows, predicate, emptyMsg) {
                let visible = 0;
                rows.forEach(row => {
                    const show = predicate(row);
                    row.classList.toggle('hidden', !show);
                    if (show) visible++;
                });
                emptyMsg.classList.toggle('hidden', visible > 0);
            }

            const studentFilterType = document.getElementById('studentFilterType');
            const studentClassFilter = document.getElementById('studentClassFilter');
            const studentInternshipFilter = document.getElementById('studentInternshipFilter');
            const studentRows = document.querySelectorAll('.student-row');
            const noStudentsMsg = document.getElementById('noStudentsMsg');

            function applyStudentFilter() {
                const type = studentFilterType.value;
                const classVal = studentClassFilter.value;
                const internshipVal = studentInternshipFilter.value;

                if (!type || (type === 'class' && !classVal) || (type === 'internship' && !internshipVal)) {
                    studentRows.forEach(r => r.classList.add('hidden'));
                    noStudentsMsg.classList.add('hidden');
                    return;
                }

                filterRows(studentRows, row => {
                    if (type === 'class') {
                        return row.dataset.classId === classVal;
                    }
                    const ids = JSON.parse(row.dataset.internshipIds || '[]').map(String);
                    return ids.includes(internshipVal);
                }, noStudentsMsg);
            }

            studentFilterType.addEventListener('change', function() {
                if (this.value === 'class') {
                    studentClassFilter.classList.remove('hidden');
                    studentInternshipFilter.classList.add('hidden');
                    studentInternshipFilter.value = '';
                } else if (this.value === 'internship') {
                    studentInternshipFilter.classList.remove('hidden');
                    studentClassFilter.classList.add('hidden');
                    studentClassFilter.value = '';
                } else {
                    studentClassFilter.classList.add('hidden');
                    studentInternshipFilter.classList.add('hidden');
                }
                applyStudentFilter();
            });

            studentClassFilter.addEventListener('change', applyStudentFilter);
            studentInternshipFilter.addEventListener('change', applyStudentFilter);

            const supervisorCompanyFilter = document.getElementById('supervisorCompanyFilter');
            const supervisorRows = document.querySelectorAll('.supervisor-row');
            const noSupervisorsMsg = document.getElementById('noSupervisorsMsg');

            supervisorCompanyFilter.addEventListener('change', function() {
                if (!this.value) {
                    supervisorRows.forEach(r => r.classList.add('hidden'));
                    noSupervisorsMsg.classList.add('hidden');
                    return;
                }
                filterRows(supervisorRows, row => row.dataset.companyId === this.value, noSupervisorsMsg);
            });

            const internshipCompanyFilter = document.getElementById('internshipCompanyFilter');
            const internshipRows = document.querySelectorAll('.internship-row');
            const noInternshipsMsg = document.getElementById('noInternshipsMsg');

            internshipCompanyFilter.addEventListener('change', function() {
                if (!this.value) {
                    internshipRows.forEach(r => r.classList.add('hidden'));
                    noInternshipsMsg.classList.add('hidden');
                    return;
                }
                filterRows(internshipRows, row => row.dataset.companyId === this.value, noInternshipsMsg);
            });

            const roleSelectUnassign = document.getElementById('roleSelectUnassign');
            const unassignStudentWrapper = document.getElementById('unassignStudentWrapper');
            const unassignSupervisorWrapper = document.getElementById('unassignSupervisorWrapper');

            roleSelectUnassign.addEventListener('change', function() {
                unassignStudentWrapper.classList.toggle('hidden', this.value !== 'student');
                unassignSupervisorWrapper.classList.toggle('hidden', this.value !== 'supervisor');
            });

            const unassignStudentSelect = document.getElementById('unassignStudentSelect');
            const allStudentOpts = Array.from(unassignStudentSelect.querySelectorAll('option[data-class-id]'));

            document.getElementById('unassignClassFilter').addEventListener('change', function() {
                const classId = this.value;
                const placeholder = unassignStudentSelect.querySelector('option[value=""]');

                placeholder.textContent = classId ? 'Select a student' : 'Select a class first';
                unassignStudentSelect.querySelectorAll('option[data-class-id]').forEach(o => o.remove());
                unassignStudentSelect.value = '';

                if (!classId) return;

                allStudentOpts
                    .filter(o => o.dataset.classId === classId)
                    .forEach(o => unassignStudentSelect.appendChild(o.cloneNode(true)));
            });

            const unassignSupervisorSelect = document.getElementById('unassignSupervisorSelect');
            const allSupervisorOpts = Array.from(unassignSupervisorSelect.querySelectorAll(
                'option[data-company-id]'));

            document.getElementById('unassignCompanyFilter').addEventListener('change', function() {
                const companyId = this.value;
                const placeholder = unassignSupervisorSelect.querySelector('option[value=""]');

                placeholder.textContent = companyId ? 'Select a supervisor' : 'Select a company first';
                unassignSupervisorSelect.querySelectorAll('option[data-company-id]').forEach(o => o
                    .remove());
                unassignSupervisorSelect.value = '';

                if (!companyId) return;

                allSupervisorOpts
                    .filter(o => o.dataset.companyId === companyId)
                    .forEach(o => unassignSupervisorSelect.appendChild(o.cloneNode(true)));
            });

        });
    </script>
@endsection
