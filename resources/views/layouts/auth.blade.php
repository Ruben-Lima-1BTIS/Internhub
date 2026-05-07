    @extends('layouts.app')

    @section('section')

        @php
            $options = [
                'dashboard' => [
                    [
                        'name' => 'Dashboard',
                        'route' => 'dashboard.index',
                        'icon' => 'house',
                        'roles' => ['admin', 'coordinator', 'supervisor', 'student']
                    ],
                ],

                'Funcionalidades' => [
                    [
                        'name' => 'Create Records',
                        'route' => 'hr.create-records',
                        'icon' => 'tools',
                        'roles' => ['admin']
                    ],
                    [
                        'name' => 'Users',
                        'route' => 'hr.index',
                        'icon' => 'users',
                        'roles' => ['admin']
                    ],
                    [
                        'name' => 'Manage Records',
                        'route' => 'hr.delete-records',
                        'icon' => 'tools',
                        'roles' => ['admin']
                    ],
                    [
                        'name' => 'Log Hours',
                        'route' => 'student.hours',
                        'icon' => 'clock',
                        'roles' => ['student']
                    ],
                    [
                        'name' => 'Submit Reports',
                        'route' => 'student.reports',
                        'icon' => 'file-arrow-up',
                        'roles' => ['student']
                    ],
                    [
                        'name' => 'Approve/Reject Hours',
                        'route' => 'supervisor.hour_approval',
                        'icon' => 'check',
                        'roles' => ['supervisor']
                    ],
                    [
                        'name' => 'Check Student Progress',
                        'route' => 'dashboard.index',
                        'icon' => 'users',
                        'roles' => ['supervisor']
                    ],
                    [
                        'name' => 'Check Reports',
                        'route' => 'coordinator.reports.index',
                        'icon' => 'file',
                        'roles' => ['coordinator']
                    ],
                    [
                        'name' => 'Chat Messages',
                        'route' => 'messages.index',
                        'icon' => 'message',
                        'roles' => ['student', 'supervisor', 'coordinator']
                    ],
                ],
            ];
        @endphp

        <div>
            <nav
                class="fixed top-0 left-0 right-0 z-50 h-16 flex items-center justify-between px-6 bg-white border-b border-gray-300">
                <a href="{{ route('dashboard.index') }}" class="flex items-center space-x-3">
                    <div class="bg-blue-600 text-white font-bold text-[1.1rem] px-3 py-[0.35rem] rounded-[0.6rem]">
                        IH
                    </div>
                    <span class="text-[1.15rem] font-bold text-slate-900">InternHub</span>
                </a>

                <div class="relative flex items-center">
                    <button onclick="document.getElementById('userDropdown').classList.toggle('hidden')"
                        class="flex items-center gap-2 px-[0.85rem] py-[0.45rem] rounded-[0.6rem] font-medium text-[0.9rem] text-slate-900 bg-transparent transition-colors hover:bg-gray-50 cursor-pointer">
                        <span>{{ auth()->user()->name }}</span>
                        <x-dynamic-component :component="'fas-chevron-down'" class="w-3 h-3 text-blue-950" />
                    </button>

                    <div id="userDropdown" class="absolute right-0 top-full mt-2 w-44 
                            bg-white border border-gray-300 rounded-xl 
                            shadow-[0_8px_24px_rgba(30,64,175,0.1)] 
                            overflow-hidden z-50 hidden">
                        <a href="{{ route('settings.index') }}" class="flex items-center gap-[0.6rem] w-full 
                                px-4 py-[0.65rem] text-left 
                                text-[0.85rem] text-slate-700 
                                bg-transparent cursor-pointer 
                                transition-colors 
                                hover:bg-blue-200 hover:text-slate-900">
                            <x-dynamic-component :component="'fas-gear'" class="w-4 h-4" />
                            <span>Settings</span>
                        </a>

                        <form method="POST" action="{{ route('auth.logout') }}">
                            @csrf
                            <button type="submit" class="flex items-center gap-[0.6rem] w-full 
                                px-4 py-[0.65rem] text-left 
                                text-[0.85rem] text-slate-700 
                                bg-transparent cursor-pointer 
                                transition-colors 
                                hover:bg-blue-200 hover:text-slate-900">
                                <x-dynamic-component :component="'fas-right-from-bracket'" class="w-4 h-4" />
                                <span>Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </nav>

            <aside
                class="w-64 bg-white border-r border-gray-300 px-3 py-5 fixed left-0 top-16 h-[calc(100vh-4rem)] overflow-y-auto">
                <nav class="space-y-5">
                    @foreach($options as $group => $links)
                        <div>
                            <h3 class="text-[0.80rem] font-bold text-slate-500 uppercase tracking-[0.08em] px-2 mb-[0.40rem]">
                                {{ ucfirst($group) }}</h3>

                            <div class="space-y-0.5">
                                @foreach($links as $link)
                                    @if(in_array(auth()->user()->role, $link['roles']))
                                        <a href="{{ route($link['route']) }}" class="flex items-center gap-[0.65rem] px-3 py-[0.55rem] rounded-[0.6rem] text-sm font-medium text-slate-600 transition-colors hover:bg-blue-200 hover:text-slate-900 [&.active]:bg-blue-200 [&.active]:text-slate-900
                                                    {{ request()->routeIs($link['route']) ? 'active' : '' }}">

                                            @if($link['icon'])
                                                <x-dynamic-component :component="'fas-' . $link['icon']" class="w-4 h-4 opacity-70" />
                                            @else
                                                <span class="text-gray-400">•</span>
                                            @endif

                                            {{ $link['name'] }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </nav>
            </aside>

            <div class="ml-64 pt-16 bg-slate-50">
                <main class="p-6 overflow-y-auto">
                    @yield('content')
                </main>
            </div>

        </div>

        <!-- Tive de voltar a por o javascript aqui pq no app.js nao funcionava -->
        <script>
            document.addEventListener('click', function (e) {
                const dropdown = document.getElementById('userDropdown');
                const button = dropdown.previousElementSibling;

                if (!button.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            });
        </script>

    @endsection