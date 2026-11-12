# Atividade prática: autenticação e área administrativa

Projeto Laravel com autenticação por sessão, cadastro público e proteção das rotas administrativas por middleware.

## Como executar

```bash
composer install
npm install
```

Crie o arquivo `.env` (ele não é versionado) com `APP_KEY` vazio e os dados do MySQL do `database/docker-compose.yml`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mysqlDB
DB_USERNAME=root
DB_PASSWORD=mysqlPW
```

```bash
php artisan key:generate
docker compose -f database/docker-compose.yml up -d
```

A imagem do MySQL só aceita `root` de dentro do container. Para o Laravel conectar de fora, libere o usuário uma vez:

```bash
docker exec my-mysql mysql -uroot -pmysqlPW -e "CREATE USER 'root'@'%' IDENTIFIED BY 'mysqlPW'; GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' WITH GRANT OPTION; FLUSH PRIVILEGES;"
```

```bash
php artisan migrate
php artisan db:seed --class=AdminSeeder
php artisan serve
npm run dev
```

Testes (usam SQLite em memória, sem tocar no MySQL): `php artisan test`

## Contas de demonstração (somente ambiente local)

| Perfil | E-mail | Senha |
|---|---|---|
| Administrador | `admin@local.test` | `admin-local-123` |
| Comum | cadastre em `/register` | a que você escolher (mínimo 8 caracteres) |

O administrador é criado pelo `AdminSeeder`, que só executa quando `APP_ENV=local`. Não existe formulário público que aceite `type = admin`.

## Rotas

| Método e caminho | Acesso | Resultado |
|---|---|---|
| `GET /` | Autenticado | Página inicial |
| `GET /login` | Público | Formulário de login |
| `POST /login` | Público, com limite de tentativas | Valida e autentica |
| `POST /logout` | Autenticado | Encerra a sessão |
| `GET /register` | Público | Formulário de cadastro |
| `POST /store` | Público | Cria conta comum |
| `/admin/*` | Perfil `admin` | Funcionalidades do painel |

## Como funciona

**Login.** O `AuthController` valida os campos e tenta `Auth::attempt`. Em caso de falha, devolve uma mensagem genérica ("Credenciais inválidas."), mantendo o e-mail digitado e nunca repopulando a senha. Em caso de sucesso, regenera o ID da sessão. Administradores vão para o destino pedido antes do login (ou `/admin/users`); usuários comuns vão sempre para `/`.

**Logout.** `POST /logout` invalida a sessão, regenera o token CSRF e redireciona ao login. O botão **Sair** do header é um formulário POST com `@csrf`; `GET /logout` retorna 405.

**Middleware `admin` (`EnsureAdmin`).** Aplicado ao grupo `/admin`:
- visitante: redireciona para `/login`;
- autenticado sem `profile.type = admin`: responde **403**;
- admin: a requisição prossegue.

**Cadastro.** Valida nome, e-mail único e senha com no mínimo 8 caracteres. Cria apenas usuários comuns (sem registro de perfil). Visitante vai ao login com mensagem de sucesso; administrador autenticado volta à lista do painel.

**Schema.** A tabela `profile` tinha a coluna `description`, mas o model usa `address`. Uma nova migration renomeia a coluna (sem editar a migration antiga) e a torna opcional.

### Limite de tentativas de login

O limite é de **5 tentativas falhas por combinação e-mail + IP**, em uma janela de 60 segundos. Ao atingir o limite, o login fica bloqueado e a tela informa quantos segundos faltam. Um login bem-sucedido zera o contador. O controle usa o `RateLimiter` do Laravel, com o cache configurado no projeto.

## Demonstração

### Visitante
| `/` | `/admin/users` |
|---|---|
| ![](docs/capturas/01-visitante-home.png) | ![](docs/capturas/02-visitante-admin.png) |

### Cadastro de conta comum
![](docs/capturas/03-cadastro.png)
![](docs/capturas/04-cadastro-erros.png)
![](docs/capturas/05-cadastro-sucesso.png)

### Login e logout da conta comum
![](docs/capturas/06-login-erro.png)
![](docs/capturas/07-login-comum.png)
![](docs/capturas/09-logout.png)

### Acesso administrativo negado (403)
![](docs/capturas/08-comum-403.png)

### Administrador no painel
![](docs/capturas/10-login-admin.png)

### Limite de tentativas
![](docs/capturas/11-limite.png)

## Perguntas para reflexão

**Por que um usuário autenticado ainda pode receber 403?**
Autenticação responde "quem é você?"; autorização responde "o que você pode fazer?". Uma conta comum está autenticada, mas o perfil dela não é `admin`, então o middleware nega o acesso.

**Qual o risco de confiar apenas em um botão escondido na interface?**
Esconder um botão só muda o que o usuário vê. Qualquer pessoa pode digitar a URL ou montar a requisição manualmente. A proteção real precisa estar no servidor (rota + middleware).

**Por que logout deve ser POST protegido por CSRF?**
Um GET pode ser disparado sem intenção do usuário (uma imagem ou link em outro site) e deslogá-lo. POST com token CSRF garante que a ação partiu do próprio formulário da aplicação.

**Como o limite de tentativas ajuda e quais são suas limitações?**
Ele dificulta ataques de força bruta e de adivinhação de senhas. Limitações: o atacante pode trocar de IP ou de e-mail; uma vítima pode ser bloqueada de propósito por tentativas alheias; e em produção o contador precisa de um cache compartilhado entre servidores. Ele reduz o risco, mas não substitui senhas fortes e outros controles (como 2FA).

**Como provisionar o primeiro administrador sem expor essa capacidade ao público?**
Por um canal que não é público: um seeder executado só em ambiente local (como o `AdminSeeder`) ou um comando Artisan rodado por quem administra o servidor. O cadastro público nunca cria perfil admin e o formulário de perfil só aceita `PF` ou `PJ`.