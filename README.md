# MCP server for EspoCRM

An official extension.

With the extension installed, EspoCRM can act as an MCP server, allowing AI agents to access CRM data and perform operations.

Important: Only MCP protocol version 2026-07-28 is supported. Make sure your MCP client supports this version.

An administrator can create multiple MCP endpoints, each will function as a separate MCP server. For an MCP endpoint,
the administrator configures supported features. Each feature corresponds to an MCP tool.

To create an MCP endpoint, follow: Administration > MCP Endpoints.

Each feature type has its own set of parameters. For example, in a Find feature, you can configure what fields are exposed and
what filters are available.

The ability to configure what is exposed helps keep the context window small.
For example, if your MCP server is intended for a customer support team, you can whitelist only a small set of tools
and limit each tool to what is needed.

Currently supported feature types:

- Find – Lists and searches records.
- Read – Reads a record.
- Create – Creates a record.
- Update – Updates a record.
- Delete – Deletes a record.
- Record Stream – Lists and searches in a record's stream.

What is exposed as tools is also controlled by the user's access rights.
For example, if a user does not have permission to create Leads, the client won't see the *Create_Lead* tool.

## Authentication

## API User

To use an API user, you need to configure the MCP client to pass the `X-Api-Key` header.

## OAuth 2.0

As of EspoCRM v10.1, it will be possible to use OAuth 2.0 for authentication.

---

Below is for developers.

## Configuration

Create `config.json` file in the root directory, or copy `config-default.json`:

```
cp config-default.json config.json
```

When extension tools read config parameters, `config-default.json` is used as the fallback source. You can override default parameters in the `config.json` file.

Parameters:

* espocrm.repository – from what repository to fetch EspoCRM;
* espocrm.branch – what branch to fetch (`stable` is set by default); you can specify version number instead (e.g. `10.0.0`);
* database - credentials of the dev database;
* install.siteUrl – site url of the dev instance;
* install.defaultOwner – a webserver owner (important to be set right);
* install.defaultGroup – a webserver group (important to be set right).


## Config for EspoCRM instance

You can override EspoCRM config. Create `config.php` in the root directory of the repository. This file will be applied after EspoCRM installation (when building).

Example:

```php
<?php
return [
    'useCacheInDeveloperMode' => true,
];
```

## Building

After building, EspoCRM instance with installed extension will be available at `site` directory. You will be able to access it with credentials:

* Username: admin
* Password: 1

### Preparation

1. You need to have *node*, *npm*, *composer* installed.
2. Run `npm install` (or `npm ci` if you are not building the extension from scratch).
3. Create a database. Note that without the created database instance building will fail. The database name is set in the config file. You can change it.

### Full EspoCRM instance building

It will download EspoCRM (from the repository specified in the config), then build and install it (in the `site` directory). Then, it will install the extension in the instance.

Command:

```
npm run all
```

Note: If an error occurred, check `site/data/logs/` for details. It's often a database is not created.

The command removes the previously installed EspoCRM instance, but keep the database intact. Use this command to update the dev instance to the latest version or to any specific version (*espocrm.branch* parameter in the config).

After the instance is ready, if your webserver is run under another user, you might need to fix file [ownership](https://docs.espocrm.com/administration/server-configuration/#ownership) (in the `site` directory).

### Copying extension files to EspoCRM instance

You need to run this command every time you make changes in `src` directory, and you want to try these changes on Espo instance.

Command:

```
npm run sync
```

To avoid running this command manually, use a file watcher in your IDE. The configuration for PhpStorm is included in this repository and enabled by default (no need any extra configuration). See below about the file watcher.

### Running after-install script

AfterInstall.php will be applied for EspoCRM instance.

Command:

```
node build --after-install
```

### Extension package building

Command:

```
npm run extension
```

The package will be created in `build` directory.

Note: The version number is taken from `package.json`.

### Installing addition extensions

If your extension requires other extensions, there is a way to install them automatically while building the instance.

Necessary steps:

1. Add the current EspoCRM version to the `config.php`:

    ```php
    <?php
    return [
        'version' => '10.1.0',
    ];
    ```

2. Create the `extensions` directory in the root directory of your repository.
3. Put needed extensions (e.g. `my-extension-1.0.0.zip`) in this directory.

Extensions will be installed automatically after running the command `node build --all` or `node build --install`.

## Development workflow

1. Do development in `src` dir.
2. Run `npm run sync`.
3. Test changes in EspoCRM instance at `site` dir.

## Versioning

The version number is stored in `package.json` and `package-lock.json`.

Bumping version:

```
npm version patch
npm version minor
npm version major
```

## Tests

To prepare an Espo instance for tests, run:

```
npm run prepare-test
```

It downloads the Espo package, unzips it in the *site* directory, and then runs composer install. To be used for unit tests and static analysis in CI environment as it takes less time than the full installation (with database).

### Unit tests

You need to install composer dev dependencies in the root first:

```
composer install
```

This root composer serves only for unit tests static analysis.

Command to run unit tests:

```
vendor/bin/phpunit
```

or with a path:

```
vendor/bin/phpunit tests/unit/Espo/Modules/Mcp
```

or:

```
npm run unit-tests
```

Unit tests should be placed in `tests/unit/Espo/Modules/Mcp` directory and be in `tests\unit\Espo\Modules\Mcp`
namespace.

### Static analysis

You need to install composer dev dependencies in the root first:

```
composer install
```

Command to run static analysis:

```
vendor/bin/phpstan
```

or:

```
npm run sa
```

PHPStan scans sources in the *src* and *site* directories as it's configured in *phpstan.neon*.

### Integration tests

Integrations tests are run from the *site* directory.

You need to build a test instance first:

1. `npm run sync`
2. `(cd site; grunt test)`

    You need to create a config file `tests/integration/config.php`:

    ```php
    <?php

    return [
        'database' => [
            'driver' => 'pdo_mysql',
            'host' => 'localhost',
            'charset' => 'utf8mb4',
            'dbname' => 'TEST_DB_NAME',
            'user' => 'YOUR_DB_USER',
            'password' => 'YOUR_DB_PASSWORD',
        ],
    ];
    ```

Command to run integration tests:

```
(npm run sync; cd site; vendor/bin/phpunit tests/integration/Espo/Modules/Mcp)
```

or:

```
npm run integration-tests
```

Note that integration tests needs the full Espo installation.

Integration tests should be placed in `tests/integration/Espo/Modules/Mcp` directory
and be in `tests\integration\Espo\Modules\Mcp` namespace.

## Configuring IDE

You need to set the following paths to be ignored in your IDE:

* `build`
* `site/build`
* `site/custom/`
* `site/client/custom/`
* `site/tests/unit/Espo/Modules/Mcp`
* `site/tests/integration/Espo/Modules/Mcp`

### File watcher

Note: The File Watcher configuration for PhpStorm is included in this repository (no need to configure).

You can set up a file watcher in the IDE to automatically copy and transpile files upon saving.

File watcher parameters for PhpStorm:

* Program: `node`
* Arguments: `build --copy-file --file=$FilePathRelativeToProjectRoot$`
* Working Directory: `$ProjectFileDir$`

## JavaScript frontend libraries

Install *rollup*.

In `extension.json`, add a command that will bundle the needed library into an AMD module. Example:

```json
{
    "scripts": [
        "npx rollup node_modules/some-lib/build/esm/index.mjs --format amd --file build/assets/lib/some-lib.js --amd.id some-lib"
    ]
}
```

Add the library module path to `src/files/custom/Espo/Modules/Mcp/Resources/metadata/app/jsLibs.json`

```json
{
    "some-lib": {
        "path": "client/custom/modules/mcp/lib/some-lib.js"
    }
}
```

When you build, the library module will be automatically included in the needed location.

Note that you may also need to create *rollup.config.js* to set some additional Rollup parameters that are not supported via CLI usage.

## Updating tooling libraries

Update the version number of espo-extension-tools in package.json to the [latest one](https://github.com/espocrm/extension-tools/releases).

Run:

```
npm update espo-extension-tools
npm update espo-frontend-build-tools
```

Or just update everything:

```
npm update
```
