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
| `make:migration <name>` | Generate a migration file |
| `make:module <Name>` | Generate a full module (all of the above) |
| `migrate` | Run pending migrations |
| `migrate --rollback` | Roll back all ran migrations |
| `migrate --status` | Show migration status |

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

Generated class includes `findAll(): Selection` and `findById(int $id): mixed` methods pre-wired to the correct database table via a private `TABLE` constant.

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
            // Modifiers: ->nullable(), ->default('value'), ->unique()
            $table->timestamps();
        });
    }

    public function down(TableBuilder $builder): void
    {
        $builder->drop('{{table}}');
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
    $table->string('email')->nullable();          // allows NULL
    $table->string('role')->default('user');      // adds DEFAULT
    $table->string('email')->unique();            // adds UNIQUE
});

$builder->drop('table_name');
$builder->dropIfExists('table_name');
```

Supported database drivers: `mysql`, `mariadb`, `pgsql`/`postgres`, `sqlite`, `sqlsrv`/`mssql`.

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
```

Generate only specific parts with `--only`:

```bash
php nette make:module Article --only=presenter,model
php nette make:module Article --only=migration
```

Comma-separated values accepted: `presenter`, `model`, `repository`, `service`, `migration`, `latte`.

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

## Integration with Nette DI (MakerExtension)

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
    └── Article/
        ├── ArticlePresenter.php             # make:presenter
        └── default.latte                    # make:latte

db/
└── migrations/
    └── 2026_05_18_120000_create_articles_table.php  # make:migration
```

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

