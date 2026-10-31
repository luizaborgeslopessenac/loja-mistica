# Arcana Lunar — Loja de produtos místicos

Aplicação web de comércio eletrônico (Atividade Final do 4º Bimestre — SENAC) com fluxo de venda completo:
vitrine → produto → carrinho → login/cadastro → checkout simulado → pedido gravado com baixa de estoque → painel administrativo.

**Stack:** HTML5, CSS3, Bootstrap 5, JavaScript, PHP (Apache/XAMPP), MySQL/MariaDB via PDO.

## Como rodar (XAMPP)

1. Instale o XAMPP (PHP 7.4 ou superior; recomendado 8.x) e inicie **Apache** e **MySQL**.
2. Copie esta pasta para `C:\xampp\htdocs\loja-mistica` (o nome da pasta deve bater com `APP_BASE_URL`).
3. Crie o banco: abra o phpMyAdmin → **Importar** → selecione `database/schema.sql`
   (ou no terminal: `mysql -u root -p < database/schema.sql`).
4. Crie o arquivo de configuração: copie `.env.example` para `.env` e ajuste os dados do seu MySQL.
5. Acesse <http://localhost/loja-mistica/>.

> Bootstrap e as fontes são carregados por CDN, então o computador precisa de internet.

### Contas de teste (criadas pelo schema.sql)

| Perfil | E-mail | Senha |
|---|---|---|
| Administrador | admin@arcanalunar.com | Admin@123 |
| Cliente | cliente@teste.com | Cliente@123 |

O painel administrativo fica em `/admin` (http://localhost/loja-mistica/admin/).

## Estrutura

```
├── .env.example          modelo de variáveis de ambiente (versionado)
├── .env                  credenciais reais (NÃO versionado)
├── .gitignore
├── database/schema.sql   criação completa das tabelas + carga inicial
├── config/env.php        leitor do .env
├── includes/             conexão PDO, sessão/auth, carrinho, pedidos, layout
├── admin/                painel (dashboard, produtos CRUD, pedidos)
├── assets/               css, js, imagens dos produtos
├── uploads/produtos/     imagens enviadas pelo admin
└── index.php, produto.php, carrinho.php, checkout.php, login.php, cadastro.php ...
```

## Requisitos do enunciado × onde estão atendidos

| Requisito | Onde |
|---|---|
| HTML5, CSS3, Bootstrap, JavaScript | `includes/header.php`, `assets/css/style.css`, `assets/js/app.js` |
| PHP em Apache/XAMPP; MySQL/MariaDB com PDO | `includes/db.php` |
| Credenciais fora do código (.env) | `config/env.php`, `includes/db.php`, `.env.example` |
| `.env` ignorado pelo Git + `.env.example` no repositório | `.gitignore`, `.env.example` |
| Prepared statements (sem concatenar variáveis em SQL) | todas as consultas usam `prepare()` + parâmetros |
| `password_hash()` / `password_verify()` | `cadastro.php`, `login.php` (e senhas do `schema.sql` em bcrypt) |
| Controle de acesso por sessão no servidor | `exigir_login()` / `exigir_admin()` em `includes/auth.php` |
| Vitrine dinâmica do banco | `index.php` |
| Filtro por categoria **e** busca | `index.php` |
| Página do produto (id, preço, descrição, imagem, estoque) | `produto.php` |
| Carrinho em `$_SESSION`: adicionar, alterar qtd (≤ estoque), remover, subtotal/total | `includes/cart.php`, `carrinho.php` |
| Checkout só para autenticados | `checkout.php` |
| Pagamento simulado PIX/Boleto/Cartão com confirmação imediata | `checkout.php` |
| Pedido → itens → baixa de estoque → limpar carrinho | `finalizar_pedido()` em `includes/pedidos.php` (transação) |
| Admin restrito a perfil administrativo | `admin/*` com `exigir_admin()` |
| CRUD de produtos (cadastrar, listar, editar preço/estoque, excluir/desativar) | `admin/produtos.php`, `admin/produto_form.php` |
| Pedidos com itens e valor total | `admin/pedidos.php` |
| Script .sql com PK, FK, DECIMAL, INT, e-mail único, perfis, preço congelado | `database/schema.sql` |
| `schema.sql` na raiz ou `/database` com carga inicial | `database/schema.sql` |

Extras de segurança: token CSRF em todos os formulários POST, escape de saída (`e()`), `session_regenerate_id()` no login,
cookie de sessão HttpOnly/SameSite, validação de upload (tipo real, tamanho, nome aleatório), bloqueio de execução de PHP em `uploads/`,
`.htaccess` bloqueando `.env` e pastas internas, preços sempre lidos do banco (o navegador não decide valores).

## Entrega via Git (obrigatório: repositório remoto, sem .zip, histórico com vários commits)

```bash
cd loja-mistica
git init
git branch -M main
git add .gitignore .env.example README.md
git commit -m "chore: estrutura inicial, .gitignore e .env.example"

git add database/schema.sql
git commit -m "feat(db): modelagem do banco e carga inicial de teste"

git add config includes/init.php includes/db.php includes/helpers.php
git commit -m "feat: leitor de .env, conexão PDO e funções auxiliares"

git add includes/auth.php login.php cadastro.php logout.php
git commit -m "feat(auth): cadastro, login e logout com password_hash"

git add index.php produto.php assets includes/header.php includes/footer.php
git commit -m "feat(catalogo): vitrine com busca/filtro e página do produto"

git add includes/cart.php carrinho.php
git commit -m "feat(carrinho): carrinho em sessão com validação de estoque"

git add includes/pedidos.php checkout.php pedido_confirmado.php meus_pedidos.php
git commit -m "feat(checkout): pagamento simulado, pedido e baixa de estoque"

git add admin uploads .htaccess
git commit -m "feat(admin): painel com CRUD de produtos e pedidos"

git remote add origin https://github.com/SEU-USUARIO/loja-mistica.git
git push -u origin main
```

Dica: se o trabalho for em equipe, cada pessoa faz seus próprios commits (com o próprio `git config user.name`).
Antes de enviar, confirme com `git status` que o arquivo `.env` **não** aparece na lista.
