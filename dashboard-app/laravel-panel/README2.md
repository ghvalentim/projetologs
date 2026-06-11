# Laravel & Filament Admin Panel

O painel de controlo principal da plataforma. Desenvolvido sobre a framework **Laravel** e utilizando o **Filament** para a construção rápida de interfaces administrativas TALL (Tailwind, Alpine.js, Laravel e Livewire).

## 🌟 Funcionalidades

* **Dashboard Interativo:** Gráficos de distribuição de logs, estatísticas do sistema e alertas de segurança.
* **Gestão de Syslogs:** Visualização detalhada de eventos, filtragem por origem, data e nível de severidade.
* **Gestão de Licenças e Departamentos:** Controlo de atribuições de licenças de software pelas diferentes áreas da autarquia.
* **Autenticação e Permissões:** Acesso restrito a administradores.

## 🛠️ Configuração Inicial

Se estiveres a configurar o painel pela primeira vez (após levantar os contentores Docker):

1. Instala as dependências do PHP e Node.js:
   ```bash
   composer install
   npm install && npm run build

2. Configura as variáveis de ambiente:

cp .env.example .env
php artisan key:generate

3. Executa as migrações e popula a base de dados:

php artisan migrate:fresh --seed