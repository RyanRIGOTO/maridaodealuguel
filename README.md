# Maridão de Aluguel — Marketplace de Serviços

TCC de Tecnologia em Análise e Desenvolvimento de Sistemas — IFPR Campus Umuarama.

O sistema conecta clientes e prestadores de serviços e possui uma área administrativa.
As páginas são feitas em **PHP/Laravel e Blade**, o visual usa **Tailwind CSS** e as
interações usam **JavaScript**.


## Tecnologias e suas funções

| Tecnologia | Para que serve aqui |
|---|---|
| PHP + Laravel | Rotas, autenticação, validação, regras de negócio e acesso ao banco |
| Blade | Gera o HTML das páginas com os dados recebidos dos controllers |
| JavaScript | Menus, etapas do agendamento, estrelas, máscaras e consulta de CEP |
| Tailwind CSS | Estilos das telas; classes comuns ficam em `resources/css/app.css` |
| Vite + Node.js | Compilam CSS e JavaScript durante o desenvolvimento |
| SQLite ou MySQL | Armazenam usuários, serviços, agendamentos e demais registros |
| Sanctum | Autentica as rotas da API com tokens |

O site usa formulários e rotas web do Laravel. A API em `/api` também está disponível,
com funcionalidades próprias, como atualização de perfil e moderação de avaliações.
Ambas reutilizam os serviços em `app/Services`.

## Como executar

Pré-requisitos: PHP 8.2+ com PDO SQLite (ou PDO MySQL), Composer e Node.js 18+.

Em uma instalação nova:

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
npm run build
php artisan serve
```

Acesse `http://localhost:8000`. Em uma instalação existente, mantenha seu `.env`
e seu banco; basta instalar as dependências, compilar e iniciar o servidor.

O `.env.example` usa SQLite, sessão em arquivo e cache em arquivo. Para MySQL,
configure `DB_CONNECTION=mysql` e os dados de conexão no `.env`. O `compose.yaml`
é uma opção para iniciar um MySQL local com Docker; não é necessário para SQLite.

Durante o desenvolvimento, execute `php artisan serve` (ou `composer dev`) em
um terminal e `npm run dev` em outro. Depois de editar CSS ou JavaScript, use
`npm run build` se não estiver com o Vite em execução.

## Telas

| Papel | Telas |
|---|---|
| Público | Home, login, cadastro de cliente e de prestador |
| Cliente | Painel, agendamento em 4 etapas, histórico e avaliações |
| Prestador | Painel, agendamentos, serviços, histórico e relatório de serviços |
| Administrador | Painel, usuários, categorias e relatórios |
| Usuários autenticados | Chat por agendamento |

Os relatórios possuem impressão/PDF pelo navegador. O chat envia mensagens por
formulário; a atualização das mensagens depende do carregamento da página.

## Dados de demonstração

Em um banco destinado à demonstração, execute `php artisan db:seed` para criar
os exemplos do `DatabaseSeeder`.

| Papel | E-mail | Senha |
|---|---|---|
| Administrador | admin@maridaodealuguel.com.br | senha123 |
| Prestador | joao.silva@email.com | senha123 |
| Cliente | ana.costa@email.com | senha123 |

## Verificação

```bash
php artisan test
npm run build
```

Os testes PHP usam SQLite em memória, configurado em `phpunit.xml`, sem acessar
os dados do banco usado pelo site. As factories em `database/factories` criam
os registros temporários dos testes.

O `.env`, bancos locais, dependências, logs e arquivos compilados não são enviados
ao Git. `composer.lock` e `package-lock.json` registram as versões das dependências.
