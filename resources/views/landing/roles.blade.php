<div class="max-w-6xl mx-auto px-6 pb-24">
    <div class="text-center mb-16">
        <h2 class="text-[2rem] font-bold text-slate-900 tracking-[-0.03em]">Built for Every Role</h2>
        <p class="text-base text-slate-600 mt-2">
            Everything you need to manage internships in one place.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <x-role-card title="Students" :items="[
            'Log internship hours',
            'Submit weekly reports',
            'Track progress in real time'
        ]">
            <x-slot:icon>
                <x-fas-user-graduate class="w-6 h-6" />
            </x-slot:icon>
        </x-role-card>

        <x-role-card title="Supervisors" :items="[
            'Approve hours & reports',
            'Provide structured feedback',
            'Manage multiple interns'
        ]">
            <x-slot:icon>
                <x-fas-user-tie class="w-6 h-6" />
            </x-slot:icon>
        </x-role-card>

        <x-role-card title="Coordinators" :items="[
            'Monitor all student progress',
            'Identify students needing support',
            'View analytics & statistics'
        ]">
            <x-slot:icon>
                <x-fas-chalkboard-teacher class="w-6 h-6" />
            </x-slot:icon>
        </x-role-card>
    </div>
</div>
