# unquam/nette-maker

[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-blue)](https://php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![Tests](https://github.com/unquam/nette-maker/actions/workflows/tests.yml/badge.svg)](https://github.com/unquam/nette-maker/actions/workflows/tests.yml)
[![Packagist Version](https://img.shields.io/packagist/v/unquam/nette-maker)](https://packagist.org/packages/unquam/nette-maker)
[![Packagist Downloads](https://img.shields.io/packagist/dt/unquam/nette-maker)](https://packagist.org/packages/unquam/nette-maker)

A CLI code generator for [Nette Framework](https://nette.org). Scaffold presenters, models, repositories, services, Latte templates, and database migrations with a single command — without touching boilerplate ever again.

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | `>= 7.4` |
| symfony/console | `^5.0 \|\| ^6.0 \|\| ^7.0` |
| nette/php-generator | `^3.5 \|\| ^4.0` |
| nette/neon | `^3.3 \|\| ^4.0` |
| doctrine/inflector | `^2.0` |

> **Optional:** `nette/di` is required only when integrating via `MakerExtension` in your Nette DI config.

---

## Installation

```bash
composer require unquam/nette-maker
```

Since this package is a Composer Plugin, during installation Composer will ask:

Do you trust "unquam/nette-maker" to execute code and wish to enable it now? (writes "allow-plugins" to composer.json) [y,n,d,?]

Press `y` to allow. The plugin will automatically create two files in your project root:

| File | Purpose |
|---|---|
| `nette` | Executable PHP runner — run `php nette <command>` |
| `nette-maker.neon` | Configuration file (database credentials, migrations path) |

If you pressed `n`, create the runner manually:

```bash
cp vendor/bin/nette-maker nette
chmod +x nette
php nette make:init
```

---

## Configuration

Edit `nette-maker.neon` in your project root:

```neon
# nette-maker.neon
database:
    dsn: 'mysql:host=127.0.0.1;dbname=your_database'
    user: root
    password: ''

migrations:
    directory: db/migrations
```

| Key | Type | Description |
|---|---|---|
| `database.dsn` | `string` | PDO DSN — driver is auto-detected from the prefix (`mysql`, `pgsql`, `sqlite`, `sqlsrv`) |
| `database.user` | `string` | Database username |
| `database.password` | `string` | Database password |
| `migrations.directory` | `string` | Path relative to the config file where migration files are stored *(default: `db/migrations`)* |

---

## Usage

All commands are available through the `php nette` runner or through `vendor/bin/nette-maker`.

```bash
php nette <command> [arguments] [options]
```

### Available Commands

| Command | Description |
|---|---|
| `make:init` | Create the default `nette-maker.neon` config file |
| `make:presenter <Name>` | Generate a Presenter class |
| `make:model <Name>` | Generate a Model class |
| `make:repository <Name>` | Generate a Repository class |
| `make:service <Name>` | Generate a Service class |
| `make:latte <Name>` | Generate a Latte template |
| `make:request <Module/Name>` | Generate an API Form Request validation class |
| `make:request <Module/Name> --web` | Generate a Web Frontend Form Request class |
| `make:module <Name>` | Generate a full module (all of the above) |
| `make:auth` | Scaffold full authentication system |
| `make:resource <Name>` | Generate a JSON API resource transformer |
| `make:seeder <Name>` | Generate a database seeder class |
| `make:factory <Name>` | Generate a database factory class |
| `make:migration <Name>` | Generate a migration file |
| `migrate` | Run pending migrations |
| `migrate --rollback` | Roll back all ran migrations |
| `migrate --status` | Show migration status |
| `migrate:fresh` | Drop all tables and re-run all migrations |
| `migrate:fresh --seed` | Drop all tables, re-run migrations and seed |
| `db:seed` | Run all database seeders |
| `db:seed --class=Name` | Run a specific seeder class |
| `db:wipe` | Drop all database tables |
| `clear:cache` | Clear application cache directories |

---

### `make:init`

Creates `nette-maker.neon` in the project root.

```bash
php nette make:init
```

If the file already exists the command exits with a warning and does nothing.

---

### `make:presenter`

Generates a Presenter class in `app/Presentation/<Name>/<Name>Presenter.php`.

```bash
php nette make:presenter Article
# → app/Presentation/Article/ArticlePresenter.php
```

Generated class:

```php
<?php

declare(strict_types=1);

namespace App\Presentation\Article;

use Nette\Application\UI\Presenter;

final class ArticlePresenter extends Presenter
{
    public function renderDefault(): void
    {
    }
}
```

---

### `make:model`

Generates a Model class in `app/Model/<Name>.php` that uses `Nette\Database\Explorer`.

```bash
php nette make:model Article
# → app/Model/Article.php
```

Generated class:

```php
<?php

declare(strict_types=1);

namespace App\Model;

use Nette\Database\Explorer;

final class Article
{
    public function __construct(private Explorer $explorer)
    {
    }
}
```

---

### `make:repository`

Generates a Repository class in `app/Model/Repositories/<Name>Repository.php`.

```bash
php nette make:repository Article
# → app/Model/Repositories/ArticleRepository.php
```

Generated class includes `findAll(): Selection`, `findById(int $id)`, `create(array $data)`, `update(int $id, array $data): bool` and `delete(int $id): bool` methods pre-wired to the correct database table via a private `TABLE` constant.

---

### `make:service`

Generates a Service class in `app/Model/Services/<Name>Service.php`, pre-injecting the corresponding Repository.

```bash
php nette make:service Article
# → app/Model/Services/ArticleService.php
```

---

### `make:latte`

Generates an empty Latte template at `app/Presentation/<Name>/default.latte`.

```bash
php nette make:latte Article
# → app/Presentation/Article/default.latte
```

---

### `Form Requests` (`make:request`)

Encapsulate your form input validation logic inside standalone, highly testable Request classes structured beautifully within your Feature Folders.

#### Generating Requests

By default, it generates an API-specific request class (inside the `Api/Requests/{Module}` namespace):
```bash
php nette make:request Article/Store
```

To generate a Web Frontend specific request class (inside the `Requests/{Module}` namespace):
```bash
php nette make:request User/Update --web
```

#### 1. Configuration Example (`StoreRequest.php`)
Define your validation rules using core constraints (`required`, `nullable`, `sometimes`, `string`, `integer`, `numeric`, `boolean`, `array`, `email`, `email:rfc`, `email:dns`, `email:rfc,dns`, `url`, `min:n`, `max:n`, `min_length:n`, `max_length:n`, `in:a,b`, `not_in:a,b`, `regex:/pattern/`, `confirmed`, `date`, `date_format:Y-m-d`, `before:date`, `after:date`, `alpha`, `alpha_num`, `alpha_dash`, `digits:n`, `digits_between:a,b`, `between:a,b`, `ip`, `ipv4`, `ipv6`, `uuid`, `json`, `accepted`, `declined`, `filled`, `present`, `prohibited`, `size:kb`, `mimetypes:types`).

> **Note:** Use `email:dns` option with caution in high-traffic production environments, as DNS lookups introduce synchronous network latency.

Rules can be defined as a pipe-separated string or as an array:
```php
// String format
'email' => 'required|email:rfc,dns|max_length:255',

// Array format
'email' => ['required', 'email:rfc,dns', 'max_length:255'],
```

```php
<?php

declare(strict_types=1);

namespace App\Presentation\Api\Requests\Article;

use Unquam\NetteMaker\Requests\FormRequest;

class StoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title'   => 'required|string|min_length:5',
            'content' => 'required|string',
            'email'   => ['required', 'email:rfc,dns', 'max_length:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'   => 'Název článku je povinný.',
            'title.min_length' => 'Název musí mít alespoň :min znaků.',
        ];
    }
}
```

#### 2. Multilingual Support (Czech / Multi-lang)
...
The `RuleValidator` uses dynamic placeholder tokens (`:field`, `:min`, `:max`, `:values`). You can easily return error messages in Czech (or any other language) by overriding them directly in the `messages()` method of your request class, or by passing translated strings through your architecture.

##### Example Czech Output Mapping:
If you override messages for Czech localization:
```php
public function messages(): array
{
    return [
        'email.required' => 'E-mailová adresa je povinné pole.',
        'email.email' => 'Zadejte prosím platnou e-mailovou adresu.',
        'password.min_length' => 'Heslo musí mít alespoň :min znaků.',
    ];
}
```

#### Usage in Presenters (Safe Action Flow)

##### Case A: Usage in a REST API Presenter (JSON Output)
```php
public function actionCreate(): void
{
    try {
        $request = new \App\Presentation\Api\Requests\Article\StoreRequest($this->getHttpRequest()); 
        $validatedData = $request->validate(); // Safe, explicit verification loop $this->model->create($validatedData); $this->sendJson(['status' => 'success']);
        
    } catch (\Unquam\NetteMaker\Exceptions\ValidationException $e) { 
        $this->getHttpResponse()->setCode($e->getCode());
        $this->sendJson([
            'message' => 'The given data was invalid.',
            'errors'  => $e->getErrors()
        ]);
    }
}
```
**Czech API Validation Failure Output (422 Client Error):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": "E-mailová adresa je povinné pole.",
    "password": "Heslo musí mít alespoň 6 znaků."
  }
}
```

##### Case B: Usage in a Web Frontend Presenter (HTML Forms with Redirect)
```php
public function actionSave(): void
{
    try {
        $request = new \App\Presentation\Requests\User\UpdateRequest($this->getHttpRequest());
        $validatedData = $request->validate();
        $this->model->save($validatedData);
        $this->flashMessage('Profil byl úspěšně aktualizován!', 'success');
        $this->redirect('User:default');
        
    } catch (\Unquam\NetteMaker\Exceptions\ValidationException $e) {
        // Handle failed states via standard Nette flash messages and redirect back
        foreach ($e->getErrors() as $errorText) {
            $this->flashMessage($errorText, 'danger');
        }
        $this->redirect('this');
    }
}
```

---

### `make:module`

Scaffolds a complete module in one command: Presenter, Model, Repository, Service, Migration, and Latte template.

```bash
php nette make:module Article
```

All parts are optional. Skip specific ones with `--no-*` flags:

```bash
php nette make:module Article --no-migration --no-latte
php nette make:module Article --no-service --no-repository
php nette make:module Article --no-presenter --no-model
```

Generate only specific parts with `--only`:

```bash
php nette make:module Article --only=presenter,model
php nette make:module Article --only=migration
php nette make:module Article --only=presenter,model,repository,service
```

Comma-separated values accepted: `presenter`, `model`, `repository`, `service`, `migration`, `latte`.

---

### `Authentication Scaffolding` (`make:auth`)

Scaffold a fully operational authentication, registration, and logout system out-of-the-box. It automatically generates a secure database schema migration table script, custom security Authenticator model services compliant with Nette Security standards, controller presenter forms logic handlers, and responsive front-end view templates layouts:

```bash
php nette make:auth
```

#### What gets generated:
- `db/migrations/%timestamp%_create_users_table.php` — Secure table structure handling password storage hashing hashes.
- `app/Model/Security/Authenticator.php` — DB credentials comparison core evaluating identities rules.
- `app/Presentation/Sign/SignPresenter.php` — Controller factories mapping login (`signInForm`), registration (`signUpForm`), and clean session logouts (`actionOut`).
- `app/Presentation/Sign/in.latte` & `up.latte` — Styled UI templates interfaces ready to serve layout pages.

#### Activation setup rules:
Once generated, simply wire up the fresh class service structure boundary mapping inside your core Nette application DI container tracking block configuration setup (`config.neon` layout configuration file):

```neon
services:
    - App\Model\Security\Authenticator
```

Afterward, instantly run your migrations schema setup to provision your backend database tracking table structures allocation layouts:

```bash
php nette migrate
```

---

### `API Resources & Collections` (`make:resource`)

Transform your database models into secure, structured JSON layers with native support for Nette Framework pagination.

#### Generating Resources

To generate a single item transformer resource (extends `JsonResource`):
```bash
php nette make:resource User
```

To generate a paginated resource collection transformer (extends `ResourceCollection`):
```bash
php nette make:resource UserCollection
```

#### 1. The Single Item Resource (`UserResource.php`)
Safely filter your database fields inside the `toArray()` block to prevent sensitive credentials leaks:

```php
<?php

declare(strict_types=1);

namespace App\Presentation\Api\Resources;

use Unquam\NetteMaker\Resources\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(): array
    {
        return [
            'id' => (int) $this->resource->id,
            'email' => (string) $this->resource->email,
        ];
    }
}
```

#### 2. The Resource Collection (`UserCollection.php`)
Define which single item resource class should map the nesting loop:

```php
<?php

declare(strict_types=1);

namespace App\Presentation\Api\Resources;

use Unquam\NetteMaker\Resources\ResourceCollection;

class UserCollection extends ResourceCollection
{
    protected function collectWith(): string
    {
        return UserResource::class;
    }
}
```

#### Usage in Presenters

##### Case A: Single Item Response
```php
$user = $this->explorer->table('users')->get(1);
$this->sendJson(UserResource::make($user));
```

##### Case B: Paginated Collection Response
Natively maps Nette Database query streams (`Selection`) combined with pagination settings out-of-the-box:

```php
// Select records for page 2, limiting to 15 entries per page
$users = $this->explorer->table('users')->page(2, 15);

// Automatically injects structured data and pagination meta hashes
$this->sendJson(UserCollection::make($users));
```

**JSON Output Format:**
```json
{
  "data": [
    { "id": 16, "email": "user16@test.com" },
    { "id": 17, "email": "user17@test.com" }
  ],
  "meta": {
    "current_page": 2,
    "per_page": 15,
    "last_page": 4,
    "total": 52,
    "from": 16,
    "to": 30
  }
}
```

#### 🔐 REST API Authentication Integration

If you need to protect these generated JSON endpoints with lightweight, secure bearer access/refresh tokens, use our native companion API package:

👉 **[unquam/nette-api-auth](https://packagist.org/packages/unquam/nette-api-auth)**

It provides full token life-cycle management, strict CORS controls, and built-in rate-limiting filters out-of-the-box.

---

### `make:seeder`

Generates a database seeder class stub.

```bash
php nette make:seeder UserSeeder
```

Configure the seeders directory in `nette-maker.neon`:

```neon
seeders:
    directory: db/seeders
```

---

### `Data Factories` (`make:factory`)

Generate powerful blueprint schemas for your database records to simplify seeding and testing.

```bash
php nette make:factory User
```

Configure your custom factories lookup directory path inside `nette-maker.neon`:

```neon
factories:
    directory: db/factories
```

#### Inside the Factory Class
Each generated factory is a structured PHP class extending `AbstractFactory`. You can easily map default attributes using standard PHP or external libraries like Faker:

```php
<?php

declare(strict_types=1);

use Unquam\NetteMaker\Migration\AbstractFactory;

class UserFactory extends AbstractFactory
{
    protected function defineTable(): string
    {
        return 'users';
    }

    protected function definition(): array
    {
        // $faker = \Faker\Factory::create();

        return [
            'name' => 'John Doe',
            'email' => 'user_' . uniqid() . '@example.com',
            'role' => 'user',
            'created_at' => date('Y-m-d H:i:s'),
        ];
    }
}
```

#### Usage in Seeders
Since generated blueprints are native PHP classes, you get complete IDE autocompletion. Simply instantiate the factory inside your seeders context loop using a standard object constructor:

```php
<?php

declare(strict_types=1);

return new class
{
    public function run(\PDO $pdo): void
    {
        // Require the generated factory class layout
        require_once dirname(__DIR__) . '/factories/UserFactory.php';

        $factory = new UserFactory($pdo);

        // Fluent interface: instantly seed exactly 50 default users!
        $factory->count(50)->create();

        // Or create separate entries while seamlessly overriding default values
        $factory->count(2)->create([
            'role' => 'admin',
        ]);
    }
};
```

---

### `make:migration`

Generates a timestamped migration file in the configured migrations directory.

```bash
php nette make:migration CreateArticlesTable
# → db/migrations/2026_05_18_120000_create_articles_table.php
```

Generated migration:

```php
<?php

declare(strict_types=1);

use Unquam\NetteMaker\Migration\TableBuilder;

return new class
{
    public function up(TableBuilder $builder): void
    {
        $builder->create('{{table}}', function (TableBuilder $table): void {
            $table->id();
            // Available column types:
            // $table->string('title');
            // $table->string('slug', 191);
            // $table->text('body');
            // $table->integer('views');
            // $table->bigInteger('score');
            // $table->boolean('is_active');
            // $table->float('rating');
            // $table->decimal('price', 10, 2);
            // $table->timestamp('published_at');
            // $table->timestamps();

            // Modifiers (chain after a column):
            // $table->string('email')->nullable();
            // $table->string('role')->default('user');
            // $table->string('email')->unique();
            // $table->string('name')->after('id');        // MySQL/MariaDB only

            // Indexes:
            // $table->index('user_id');
            // $table->index(['user_id', 'status']);

            // Foreign keys:
            // $table->foreign('user_id', 'users')->cascadeOnDelete();
            // $table->foreign('user_id', 'users', 'id', null, 'SET NULL', 'RESTRICT');

            // Composite primary key:
            // $table->primary(['user_id', 'role_id']);

            $table->timestamps();
        });
    }

    public function down(TableBuilder $builder): void
    {
        $builder->drop('{{table}}');

        // To alter an existing table instead of dropping:
        // $builder->table('{{table}}', function (TableBuilder $table): void {
        //     $table->dropColumn('email');
        //     $table->dropIndex('idx_{{table}}_email');
        //     $table->dropForeign('fk_{{table}}_user_id');
        //     $table->dropPrimary();
        // });
    }
};
```

#### TableBuilder API

```php
$builder->create('table_name', function (TableBuilder $table): void {
    $table->id();                          // Auto-increment primary key
    $table->string('title');               // VARCHAR(255) NOT NULL
    $table->string('slug', 191);           // VARCHAR(191) NOT NULL
    $table->text('body');                  // TEXT NOT NULL
    $table->integer('views');              // INT NOT NULL
    $table->bigInteger('score');           // BIGINT NOT NULL
    $table->boolean('is_published');       // BOOLEAN/TINYINT NOT NULL
    $table->float('rating');               // FLOAT NOT NULL
    $table->decimal('price', 10, 2);       // DECIMAL(10,2) NOT NULL
    $table->timestamp('published_at');     // TIMESTAMP NULL
    $table->timestamps();                  // created_at + updated_at

    // Modifiers (chain after a column):
    $table->string('email')->nullable();
    $table->string('role')->default('user');
    $table->string('email')->unique();
    $table->string('name')->after('id');   // MySQL/MariaDB only

    // Indexes:
    $table->index('user_id');
    $table->index(['user_id', 'status']);
    $table->index(['user_id', 'status'], 'custom_index_name');

    // Foreign keys:
    $table->foreign('user_id', 'users');
    $table->foreign('user_id', 'users')->cascadeOnDelete();
    $table->foreign('user_id', 'users', 'id', null, 'SET NULL', 'RESTRICT');

    // Composite primary key:
    $table->primary(['user_id', 'role_id']);
});

// Alter existing table:
$builder->table('table_name', function (TableBuilder $table): void {
    $table->dropColumn('email');
    $table->dropIndex('idx_table_email');
    $table->dropForeign('fk_table_user_id');
    $table->dropPrimary();
});

$builder->drop('table_name');
$builder->dropIfExists('table_name');
```

Supported database drivers: `mysql`, `mariadb`, `pgsql`/`postgres`, `sqlite`, `sqlsrv`/`mssql`.

---

### `migrate`

Run all pending migrations:

```bash
php nette migrate
```

Show migration status:

```bash
php nette migrate --status
```

Roll back all ran migrations:

```bash
php nette migrate --rollback
```

---

### `migrate:fresh`

Drops all database tables completely and re-runs all migration scripts sequentially from scratch. This provides a fresh starting state for local development:

```bash
php nette migrate:fresh
```

You can automatically run database seeders right after resetting your database schema using the `--seed` (or `-s`) shortcut flag:

```bash
php nette migrate:fresh --seed
# or shortcut notation format
php nette migrate:fresh -s
```

---

### `db:seed`

Run all available seeders alphabetically:

```bash
php nette db:seed
```

Run a specific seeder class directly using the `--class` (or `-c`) option:

```bash
php nette db:seed --class=UserSeeder
# or shortcut notation format
php nette db:seed -c UserSeeder
```

---

### `db:wipe`

Completely drops all tables from the database without running `down()` migration methods. It safely disables foreign key constraints internally during the process:

```bash
php nette db:wipe
```

---

### `clear:cache`

Safely clears application cache and maintenance directories.

```bash
php nette clear:cache
```

By default, it targets the `temp/` folder. It uses **smart isolation**: inside the standard Nette `temp/` directory, it removes only the compiled configuration and templates (`temp/cache/` and `temp/proxies/`), strictly ignoring `temp/session/` so active web users won't log out.

You can configure multiple custom directories to be fully cleared inside your `nette-maker.neon` configuration array:

```neon
cache:
    # List of directories to be cleared during clear:cache execution
    directories:
        - temp
        # - log
        # - www/assets/cache
```

---

### `Interactive Prompts`

All code generation commands support a smart interactive mode. If you forget to provide the name argument, the CLI will prompt you for it in real-time:

```bash
php nette make:module
? Enter the name of the module (e.g. Article): 
```

---

## Directory Structure Generated

Running the commands creates files in the following locations (all relative to your project root by default, or to the directory containing `nette-maker.neon` when using the `nette` runner):

```
app/
├── Model/
│   ├── Article.php                          # make:model
│   ├── Repositories/
│   │   └── ArticleRepository.php            # make:repository
│   └── Services/
│       └── ArticleService.php               # make:service
└── Presentation/
    ├── Article/
    │   ├── ArticlePresenter.php             # make:presenter
    │   └── default.latte                    # make:latte
    ├── Api/
    │   ├── Requests/
    │   │   └── Article/
    │   │       └── StoreRequest.php         # make:request Article/Store
    │   └── Resources/
    │       └── UserResource.php             # make:resource User
    ├── Requests/
    │   └── User/
    │       └── UpdateRequest.php            # make:request User/Update --web
    └── Sign/
        ├── SignPresenter.php                # make:auth
        ├── in.latte                         # make:auth
        └── up.latte                         # make:auth

db/
└── migrations/
    └── 2026_05_18_120000_create_articles_table.php  # make:migration
```

---

## `Integration with Nette DI (MakerExtension)`

If your project uses `nette/di`, you can register all commands as services and wire them into your Symfony Console application via the DI extension.

### 1. Register the extension in your `config.neon`

```neon
extensions:
    maker: Unquam\NetteMaker\DI\MakerExtension
```

### 2. Use the `Application` service in your bootstrap

```php
/** @var \Nette\DI\Container $container */
$app = $container->getByType(\Unquam\NetteMaker\Application::class);
exit($app->run());
```

The extension registers every `make:*` command and the `migrate` command as individual DI services. This allows them to participate in standard DI autowiring.

---

## Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository.
2. Create a feature branch: `git checkout -b feat/my-feature`.
3. Write tests for any new behaviour in `tests/`.
4. Ensure the test suite passes: `composer test`.
5. Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards.
6. Open a pull request against the `main` branch.

---

## License

This package is open-sourced software licensed under the [MIT licence](LICENSE).