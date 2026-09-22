# 🔧 Maridão de Aluguel - Marketplace de Serviços

Projeto Integrador - Tecnologia em Análise e Desenvolvimento de Sistemas
IFPR Campus Umuarama

Front-end em **Blade + Tailwind CSS** (com **Alpine.js** para interações
simples, via CDN), consumindo os *Services* e *Models* do próprio Laravel —
sem API/JS framework separado. Todo o HTML é renderizado no servidor.

## Telas implementadas

| Papel | Telas |
|---|---|
| Público | Home, Login, Cadastro de Cliente, Cadastro de Prestador |
| Cliente | Dashboard, Agendar Serviço (assistente de 4 fases), Histórico, Avaliações |
| Prestador | Dashboard, Agendamentos, Meus Serviços (CRUD), Histórico |
| Admin | Dashboard, Usuários, Categorias (CRUD), Relatórios (5 relatórios imprimíveis) |
| Todos | Chat interno por agendamento |

## Estrutura do front-end

```
resources/
  css/app.css                 → Tailwind + classes reutilizáveis (.btn-primary, .card, .badge-*, ...)
  js/app.js                   → só inicializa o Vite/axios (Alpine.js vem via CDN no layout)
  views/
    components/                → Layouts e componentes Blade reaproveitáveis
      app-layout.blade.php       (dashboards: sidebar + topbar)
      guest-layout.blade.php     (home / marketing)
      auth-layout.blade.php      (login / cadastro)
      report-layout.blade.php    (cabeçalho/rodapé padrão dos relatórios)
      flash.blade.php, status-badge.blade.php, star-rating.blade.php, rating-input.blade.php
    home.blade.php
    auth/...
    cliente/...
    prestador/...
    admin/...
    chat/index.blade.php

app/Http/Controllers/Web/       → Controllers que retornam Blade (não JSON)
app/Http/Middleware/EnsureRole.php → protege rotas por papel (redireciona, não retorna JSON)
routes/web.php                  → todas as rotas do front-end
```

As regras de negócio (RN1–RN14 do documento de requisitos) continuam nos
`app/Services/*` já existentes (`AgendamentoService`, `AvaliacaoService`,
`RecebimentoService`, `AuditLogService`) — os controllers web só chamam
esses services, igual a API já fazia.

## Como rodar localmente

Pré-requisitos: PHP 8.2+ com a extensão PDO SQLite, Composer e Node.js 18+.

```bash
composer install
npm install
cp .env.example .env   # se ainda não existir um .env
php artisan key:generate

# banco SQLite local (crie o arquivo caso ainda não exista)
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate

# opcional: recriar os dados de exemplo (categorias, serviços, usuários demo)
php artisan db:seed

npm run build      # compila o Tailwind (ou `npm run dev` para hot reload)
php artisan serve
```

Acesse `http://localhost:8000`.

O arquivo `.env`, o banco de dados local, os logs e as dependências não são
enviados ao GitHub. O `.env.example` está configurado para SQLite; os comandos
acima criam um banco novo para avaliação, sem dados pessoais do ambiente local.

### Contas de demonstração (seed)

| Papel | E-mail | Senha |
|---|---|---|
| Admin | admin@maridaodealuguel.com.br | senha123 |
| Prestador | joao.silva@email.com | password123 |
| Cliente | ana.costa@email.com | password123 |

## Observações de implementação

- O front-end antigo (Inertia + React, em `resources/js/Pages`) foi removido
  e substituído por Blade. O Vite continua no projeto só para compilar o
  Tailwind (`resources/css/app.css`).
- `Alpine.js` é carregado via CDN diretamente no `<head>` dos layouts — não
  precisa instalar nada no `npm` para isso. É usado no assistente de
  agendamento (4 fases), nas notas por estrela, no menu mobile e nos
  formulários de edição inline (categorias/serviços).
- Os relatórios (`/admin/relatorios/...`) têm um botão **Imprimir / PDF**
  que usa `window.print()` — o layout já esconde o menu lateral e os
  botões ao imprimir (`@media print`).
- A API REST original (`routes/api.php`) continua funcionando normalmente;
  o Blade é só uma segunda forma de acessar o mesmo sistema.
