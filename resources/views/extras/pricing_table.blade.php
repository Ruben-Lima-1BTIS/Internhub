<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InternHub — Preços</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/YOUR_KIT_ID.js" crossorigin="anonymous"></script>

    <style>
        body {
            font-family: 'Google Sans', ui-sans-serif, system-ui, sans-serif;
        }
    </style>
</head>

<body class="bg-gradient-to-b from-gray-50 to-gray-100 text-gray-900 antialiased">

    <div class="max-w-6xl mx-auto px-6 py-5">

        <div class="flex justify-between items-end mb-24 border-b pb-6">
            <div>
                <div class="text-3xl font-semibold" style="color: #286cda;">InternHub</div>
                <div class="text-xs uppercase tracking-widest text-gray-500 mt-1">
                    Gestão de Estágios
                </div>
            </div>

            <div class="text-right">
                <div class="text-xs uppercase text-gray-400">Documento Comercial</div>
                <div class="text-xl font-medium">Tabela de Preços 2026</div>
            </div>
        </div>

        <div class="text-center max-w-3xl mx-auto mb-20">
            <h1 class="text-5xl font-semibold leading-tight mb-6">
                Melhor que Excel.<br>
                <span class="bg-gradient-to-r bg-clip-text text-transparent"
                    style="background: linear-gradient(to right, #286cda, #1e40af); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                    Ganhe controlo total
                </span>
                dos estágios.
            </h1>

            <p class="text-lg text-gray-600">
                Menos trabalho manual. Menos erros. Mais controlo sobre todos os alunos.
            </p>
        </div>

        <div class="grid md:grid-cols-3 gap-8 items-stretch">

            <x-pricing-card title="Gratuito" subtitle="Comece a eliminar Excel e registos manuais em minutos."
                price="€0" period="até 250 alunos" buttonText="Criar conta gratuita">
                <x-pricing-feature>Registo digital de horas</x-pricing-feature>
                <x-pricing-feature>Submissão de relatórios</x-pricing-feature>
                <x-pricing-feature>Visão básica do progresso dos alunos</x-pricing-feature>
                <x-pricing-feature>Organização simples de turmas e estágios</x-pricing-feature>
            </x-pricing-card>

            <x-pricing-card title="Pro"
                subtitle="Automatize o acompanhamento de estágios e reduza trabalho administrativo em 80%."
                price="€999" period="por ano · até 500 alunos" badge="Plano recomendado" highlight="true"
                buttonText="Ativar Pro">
                <x-pricing-feature>Notificações em tempo real para utilizadores</x-pricing-feature>
                <x-pricing-feature>Dashboards em tempo real</x-pricing-feature>
                <x-pricing-feature>Relatórios automáticos prontos para avaliação académica</x-pricing-feature>
                <x-pricing-feature>Sistema de mensagens interno entre todos os utilizadores</x-pricing-feature>
                <x-pricing-feature>Suporte prioritário com resposta rápida</x-pricing-feature>
            </x-pricing-card>

            <x-pricing-card title="MAX" subtitle="Infraestrutura completa para escolas e redes de ensino."
                price="€3,499" period="por ano · alunos ilimitados" buttonText="Falar com a equipa"
                buttonClass="bg-gray-900 text-white hover:bg-gray-800">
                <x-pricing-feature>Gestão multi-escola centralizada</x-pricing-feature>
                <x-pricing-feature>Integração com sistemas internos da instituição</x-pricing-feature>
                <x-pricing-feature>Formação contínua para equipas educativas</x-pricing-feature>
                <x-pricing-feature>Ambiente dedicado com performance reservada para a tua instituição</x-pricing-feature>
                <x-pricing-feature>Processamento prioritário e estabilidade garantida mesmo em alta utilização</x-pricing-feature>
                <x-pricing-feature>Suporte prioritário 24/7</x-pricing-feature>
            </x-pricing-card>

        </div>
        <div
            class="mt-20 max-w-2xl mx-auto bg-white border border-gray-200 rounded-2xl p-6 shadow-sm flex gap-4 items-start">
            <i class="fa-solid fa-circle-info text-xl mt-1" style="color: #286cda;"></i>
            <div>
                <h4 class="font-semibold mb-1">Plano gratuito para sempre</h4>
                <p class="text-sm text-gray-500">
                    Sem cartão de crédito. Pode mudar de plano quando quiser, sem complicações.
                </p>
            </div>
        </div>
    </div>
</body>

</html>
