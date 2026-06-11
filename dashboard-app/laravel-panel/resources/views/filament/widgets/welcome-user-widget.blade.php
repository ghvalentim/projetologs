<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between p-2">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                    Olá, {{ Auth::user()->name }}! 👋
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Bem-vindo à Central de Operações da TI Municipal. O sistema está a monitorizar a infraestrutura e as licenças ativas.
                </p>
            </div>
            
            <div class="hidden sm:block text-right">
                <span class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">
                    Data de Acesso
                </span>
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    {{ now()->translatedFormat('d \d\e F \d\e Y') }}
                </p>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>