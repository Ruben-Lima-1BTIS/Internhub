@extends('layouts.auth')

@section('content')
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-800 tracking-tight">Management Tools</h2>
        <p class="text-sm text-gray-600 mt-0.5">Create and manage system resources</p>
    </div>

    <x-tabs :tabs="[
        'tabUser' => 'Create User',
        'tabCompany' => 'Create Company',
        'tabClass' => 'Create Class',
        'tabInternship' => 'Create Internship',
        'tabAssign' => 'Assign Internship',
    ]" default="tabUser">

        <div id="tabUser" class="tab-content hidden">
            <x-ih-card title="Create User" icon="fas-user-plus" action="{{ route('hr.user.create') }}">

                <x-select name="role" label="Role" id="roleSelectUser" required :options="[
                    ['id' => App\Models\User::ROLE_COORDINATOR, 'name' => 'Coordinator'],
                    ['id' => App\Models\User::ROLE_SUPERVISOR, 'name' => 'Supervisor'],
                    ['id' => App\Models\User::ROLE_STUDENT, 'name' => 'Student'],
                ]" />

                <x-input name="name" label="Name" required />
                <x-input name="email" label="Email" type="email" required />
                <x-input name="password" label="Initial Password" type="password" required />

                <div id="classSelectWrapper" class="hidden">
                    <x-select name="class_id" label="Class" :options="$classes->map(fn($c) => ['id' => $c->id, 'name' => $c->sigla])" />
                </div>

                <div id="companySelectWrapper" class="hidden">
                    <x-select name="company_id" label="Company" :options="$companies->map(fn($c) => ['id' => $c->id, 'name' => $c->name])" />
                </div>

            </x-ih-card>
        </div>

        <div id="tabCompany" class="tab-content hidden">
            <x-ih-card title="Create Company" icon="fas-building" action="{{ route('hr.company.create') }}">

                <x-input name="name" label="Company's Name" required />
                <x-input name="address" label="Address" required />
                <x-input name="email" label="Email" type="email" required />
                <x-input name="phone" label="Phone Number" type="tel" required />

            </x-ih-card>
        </div>

        <div id="tabClass" class="tab-content hidden">
            <x-ih-card title="Create Class" icon="fas-chalkboard" action="{{ route('hr.class.create') }}">

                <x-input name="course" label="Course's Name" required />
                <x-input name="sigla" label="Acronym" required />
                <x-input name="year" label="Year" type="number" required />

                <x-select name="user_id" label="Coordinator" required :options="$coordinators->map(fn($c) => ['id' => $c->id, 'name' => $c->name])" />

            </x-ih-card>
        </div>

        <div id="tabInternship" class="tab-content hidden">
            <x-ih-card title="Create Internship" icon="fas-briefcase" action="{{ route('hr.internship.create') }}">

                <x-input name="title" label="Internship Title" required />

                <x-select name="company_id" label="Company" required :options="$companies->map(fn($c) => ['id' => $c->id, 'name' => $c->name])" />

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input name="start_date" label="Start Date" type="date" required />
                    </div>
                    <div>
                        <x-input name="end_date" label="End Date" type="date" required />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input name="total_hours_required" label="Total Hours Required" type="number" required />
                    </div>
                    <div>
                        <x-input name="min_hours_day" label="Minimum Hours Per Day" type="number" required />
                    </div>
                </div>

            </x-ih-card>
        </div>

        <div id="tabAssign" class="tab-content hidden">
            <x-ih-card title="Assign Internship" icon="fas-link" action="{{ route('hr.user.assignUser') }}">

                <x-select name="role" label="User's Role" id="roleSelect" required :options="[['id' => 'student', 'name' => 'Student'], ['id' => 'supervisor', 'name' => 'Supervisor']]" />

                <div id="studentWrapper" class="hidden">
                    <label class="block mb-2 font-semibold">Filter by Class</label>
                    <select id="assignClassFilter"
                        class="border border-gray-300 rounded-lg p-2 w-full mb-4 focus:outline-none focus:ring-2 focus:ring-slate-500">
                        <option value="">Select a class</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->sigla }} — {{ $class->course }}</option>
                        @endforeach
                    </select>

                    <label class="block mb-2 font-semibold">Student</label>
                    <select name="student_id" id="assignStudentSelect"
                        class="border border-gray-300 rounded-lg p-2 w-full mb-4 focus:outline-none focus:ring-2 focus:ring-slate-500">
                        <option value="">Select a class first</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}" data-class-id="{{ $student->userClass?->class_id }}">
                                {{ $student->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div id="supervisorWrapper" class="hidden">
                    <label class="block mb-2 font-semibold">Filter by Company</label>
                    <select id="assignCompanyFilter"
                        class="border border-gray-300 rounded-lg p-2 w-full mb-4 focus:outline-none focus:ring-2 focus:ring-slate-500">
                        <option value="">Select a company</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>

                    <label class="block mb-2 font-semibold">Supervisor</label>
                    <select name="supervisor_id" id="assignSupervisorSelect"
                        class="border border-gray-300 rounded-lg p-2 w-full mb-4 focus:outline-none focus:ring-2 focus:ring-slate-500">
                        <option value="">Select a company first</option>
                        @foreach ($supervisors as $supervisor)
                            <option value="{{ $supervisor->id }}" data-company-id="{{ $supervisor->company_id }}">
                                {{ $supervisor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <label class="block mb-2 font-semibold">Filter Internship by Company</label>
                <select id="assignInternshipCompanyFilter"
                    class="border border-gray-300 rounded-lg p-2 w-full mb-4 focus:outline-none focus:ring-2 focus:ring-slate-500">
                    <option value="">Select a company</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>

                <label class="block mb-2 font-semibold">Internship</label>
                <select name="internship_id" id="assignInternshipSelect" required
                    class="border border-gray-300 rounded-lg p-2 w-full mb-4 focus:outline-none focus:ring-2 focus:ring-slate-500">
                    <option value="">Select a company first</option>
                    @foreach ($internships as $internship)
                        <option value="{{ $internship->id }}" data-company-id="{{ $internship->company_id }}">
                            {{ $internship->title }}
                        </option>
                    @endforeach
                </select>

            </x-ih-card>
        </div>

    </x-tabs>

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            function bindRoleSelect(selectId, wrapperMap) {
                const select = document.getElementById(selectId);
                if (!select) return;

                const wrappers = Object.values(wrapperMap);

                select.addEventListener("change", function() {
                    wrappers.forEach(el => el.classList.add("hidden"));
                    wrapperMap[this.value]?.classList.remove("hidden");
                });
            }

            bindRoleSelect("roleSelectUser", {
                "{{ App\Models\User::ROLE_STUDENT }}": document.getElementById("classSelectWrapper"),
                "{{ App\Models\User::ROLE_SUPERVISOR }}": document.getElementById("companySelectWrapper"),
            });

            const roleSelect = document.getElementById("roleSelect");
            const studentWrapper = document.getElementById("studentWrapper");
            const supervisorWrapper = document.getElementById("supervisorWrapper");

            roleSelect?.addEventListener("change", function() {
                studentWrapper.classList.toggle("hidden", this.value !== "student");
                supervisorWrapper.classList.toggle("hidden", this.value !== "supervisor");
            });

            const assignStudentSelect = document.getElementById("assignStudentSelect");
            const allAssignStudentOpts = Array.from(
                assignStudentSelect.querySelectorAll("option[data-class-id]")
            );

            document.getElementById("assignClassFilter")?.addEventListener("change", function() {
                const classId = this.value;
                const placeholder = assignStudentSelect.querySelector("option[value='']");

                placeholder.textContent = classId ? "Select a student" : "Select a class first";
                assignStudentSelect.querySelectorAll("option[data-class-id]").forEach(o => o.remove());
                assignStudentSelect.value = "";

                if (!classId) return;

                allAssignStudentOpts
                    .filter(o => o.dataset.classId === classId)
                    .forEach(o => assignStudentSelect.appendChild(o.cloneNode(true)));
            });

            const assignSupervisorSelect = document.getElementById("assignSupervisorSelect");
            const allAssignSupervisorOpts = Array.from(
                assignSupervisorSelect.querySelectorAll("option[data-company-id]")
            );

            document.getElementById("assignCompanyFilter")?.addEventListener("change", function() {
                const companyId = this.value;
                const placeholder = assignSupervisorSelect.querySelector("option[value='']");

                placeholder.textContent = companyId ? "Select a supervisor" : "Select a company first";
                assignSupervisorSelect.querySelectorAll("option[data-company-id]").forEach(o => o.remove());
                assignSupervisorSelect.value = "";

                if (!companyId) return;

                allAssignSupervisorOpts
                    .filter(o => o.dataset.companyId === companyId)
                    .forEach(o => assignSupervisorSelect.appendChild(o.cloneNode(true)));
            });

            const assignInternshipSelect = document.getElementById("assignInternshipSelect");
            const allAssignInternshipOpts = Array.from(
                assignInternshipSelect.querySelectorAll("option[data-company-id]")
            );

            document.getElementById("assignInternshipCompanyFilter")?.addEventListener("change", function() {
                const companyId = this.value;
                const placeholder = assignInternshipSelect.querySelector("option[value='']");

                placeholder.textContent = companyId ? "Select an internship" : "Select a company first";
                assignInternshipSelect.querySelectorAll("option[data-company-id]").forEach(o => o.remove());
                assignInternshipSelect.value = "";

                if (!companyId) return;

                allAssignInternshipOpts
                    .filter(o => o.dataset.companyId === companyId)
                    .forEach(o => assignInternshipSelect.appendChild(o.cloneNode(true)));
            });

        });
    </script>
@endsection
