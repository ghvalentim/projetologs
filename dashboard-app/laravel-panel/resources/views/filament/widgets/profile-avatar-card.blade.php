<div class="flex flex-col items-center text-center p-4">
    <div class="w-20 h-20 bg-primary-500 rounded-full flex items-center justify-center text-white text-3xl font-bold shadow-md mb-4">
        {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
    </div>
    
    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
        {{ Auth::user()->name }}
    </h3>
    
    <span class="inline-flex items-center gap-x-1.5 rounded-md bg-primary-50 px-2 py-1 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-600/10 dark:bg-primary-400/10 dark:text-primary-400 dark:ring-primary-400/20 mt-2">
        Acesso Técnico Municipal
    </span>

    <div class="border-t border-gray-100 dark:border-gray-800 w-full my-4"></div>

    <p class="text-xs text-gray-400 dark:text-gray-500">
        Registado no ecossistema SGLS
    </p>
</div>