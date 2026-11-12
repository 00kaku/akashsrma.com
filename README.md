# akashsrma.com

Akash Sharma's portfolio. The repo holds two packages, so the site's design can change every year while its content stays:

| Package                                                       | Job                                                                                                                     | Lifetime                               |
| ------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------- | -------------------------------------- |
| **[Akashsrma Features](plugins/akashsrma-features)** (plugin) | What the site holds: Portfolio and Learning, their fields, the site's sections, content blocks                          | Permanent, whatever the theme          |
| **[Magazine](themes/magazine)** (theme)                       | How it looks this year: a printed magazine with turning pages on wide screens and swipeable pages on tablets and phones | One design; next year's is a new theme |

The two are independent. The plugin keeps only what any theme can use; everything about how the magazine looks lives in the theme:

| Screen                    | Owner  | Holds                                                                                                                          |
| ------------------------- | ------ | ------------------------------------------------------------------------------------------------------------------------------ |
| **Content → Sections**    | plugin | Which Pages and collections (Portfolio, Learning) make up the site, their labels and order, and which posts a collection shows |
| **Appearance → Magazine** | theme  | Magazine number, city, masthead, cover and fillers; per section: bookmark, colour, opening page, index style, back link        |

**The contract.** The theme reads content only through the `akashsrma_sections` filter. Each section is `key` (`page-{ID}` or the post type), `type` (`page` or `collection`), `label`, `url`, and either `post` or `collection` (`post_type`, `name`, `description`, `slug`, `posts`). The theme's own options are keyed by `key`. A theme still runs without the plugin (the Magazine screen says so), and a new theme needs nothing else from it.

**Post limit.** Each theme says how many posts a collection may show: `add_theme_support( 'akashsrma-features', array( 'max_posts' => 5 ) )` (Magazine: `Setup\Supports::MAX_POSTS`). Without it, the plugin uses 5.

**Blocks.** Plugin blocks are generic, with neutral styles; a theme restyles them (Magazine designs Code & live links in `_content.scss`). A block that only makes sense in one theme is gated by a theme feature in `config/blocks.php`.

## Structure

```
.
├── plugins/akashsrma-features/
│   ├── akashsrma-features.php      loads the autoloader, boots the plugin
│   ├── composer.json               PSR-4: Akashsrma\Features\ → inc/
│   ├── config/                     the content model as data: post-types, fields, taxonomies, templates,
│   │                               sections (starting sections), blocks (theme-specific blocks)
│   ├── inc/                        PHP classes, one per job
│   │   ├── Plugin.php              lists the modules and registers the ones that apply
│   │   ├── Core/                   Module (interface), Config, FieldTypes
│   │   ├── Content/                PostTypes, Meta, Taxonomies, Sections (store), SectionData (the filter), Comments (off site-wide)
│   │   ├── Admin/                  AbstractPage, SectionsPage
│   │   └── Blocks/                 Blocks (registers blocks; checks theme features)
│   ├── src/blocks/                 block sources (built into build/blocks)
│   └── assets/                     the Sections screen's script and styles
├── themes/magazine/
│   ├── style.css, theme.json       theme header; design tokens
│   ├── functions.php               loads the autoloader, boots the theme
│   ├── composer.json               PSR-4: Magazine\ → inc/
│   ├── inc/                        Theme.php (modules), Core/, Setup/, Routing/, Settings/ (magazine options),
│   │                               Admin/ (Appearance → Magazine), Layout/ (Sections merge, Magazine spreads)
│   ├── src/blocks/magazine/        the Magazine block (render, engine, swipe, styles)
│   ├── templates/, patterns/       index and 404 templates; starter patterns
│   └── assets/fonts/               self-hosted variable fonts
├── tests/
│   ├── php/                        PHPUnit integration tests (Features/, Magazine/)
│   └── js/                         Vitest unit tests (page physics)
├── .github/workflows/ci.yml        lint, tests, build; deploy staging and master
└── tooling at the root             package.json, composer.json, phpunit.xml.dist, phpcs.xml.dist,
                                    phpstan.neon.dist, eslint.config.cjs, .wp-env.json
```

### How the PHP is organised

- **Autoloading:** each package has a Composer **PSR-4 autoloader**: a class's namespace says where its file is (`Akashsrma\Features\Content\Sections` → `inc/Content/Sections.php`). There's no `require_once` list; adding a class is adding a file.
- **Modules:** each feature is a **module**, a class implementing `Core\Module`:
    - `can_register()` says whether it applies (admin screens only in wp-admin, for example);
    - `register()` adds its own hooks.
- **Bootstrap:** `Plugin::modules()` and `Theme::modules()` list the modules and give each what it needs (config, sections) through its constructor.
- **New features:** a REST endpoint, a block, an integration and so on is a new class plus one line in that list.
- **Config:** the content model is **config as data** in `config/`. Adding a section post type or a field is an entry there, not new code. The theme's magazine options are listed in `Settings::fields()`.
- **Theme-specific blocks:** a plugin block can be limited to themes that declare a feature. Map it in `config/blocks.php`, for example `'turn-page-hint' => 'magazine'`. The Magazine theme declares `add_theme_support( 'magazine' )`.

## Requirements

- Node 20+ (`.nvmrc` pins 24), npm 10+
- PHP 8.2+ and Composer
- Docker, for `wp-env`

## Getting started

```sh
npm install
composer install
npm run build            # PHP autoloaders + blocks
npm run env:start        # http://localhost:8890 (admin / password); tests site on :8889
```

## Scripts

| Command                                        | What it does                                                             |
| ---------------------------------------------- | ------------------------------------------------------------------------ |
| `npm run build`                                | Generates both packages' PHP autoloaders, then builds their blocks       |
| `npm start`                                    | Generates the autoloaders, then rebuilds both packages' blocks on change |
| `npm run env:start` / `env:stop` / `env:reset` | Local WordPress with wp-env (dev site and tests site)                    |
| `npm run env:cli -- <command>`                 | WP-CLI on the dev site, e.g. `npm run env:cli -- post list`              |
| `npm run lint`                                 | ESLint, Stylelint, PHPCS (WordPress standards) and PHPStan (level 6)     |
| `npm test`                                     | Vitest unit tests, then PHPUnit integration tests (needs `env:start`)    |
| `npm run format`                               | Prettier (WordPress style) and PHPCBF                                    |

A pre-commit hook (Husky + lint-staged) lints staged files.

## Tests

- **PHPUnit** (`tests/php`) runs inside wp-env's tests site, against WordPress's own test library. The bootstrap loads the plugin and switches on the Magazine theme. Tests make their own content with WordPress's factories.
    - `Features/`: post types, fields, Sections (store, theme limit, old contents), SectionData (the filter), Comments, Blocks.
    - `Magazine/`: Settings (defaults, section options, old values), the spread layout, and 404s for missing spreads.
- **Vitest** (`tests/js`): the page-turn physics.

## Branches, CI and deployment

| Branch    | Deploys to                                      |
| --------- | ----------------------------------------------- |
| `master`  | https://akashsrma.com (production)              |
| `staging` | https://staging.akashsrma.com (password-protected) |

New work goes to `staging` first; once it checks out there, it's merged into `master`.

`.github/workflows/ci.yml` runs on every push and pull request:

1. **Checks:** lint, static analysis and JS tests.
2. **PHPUnit:** builds, starts wp-env on the runner and runs the integration tests.
3. **Build:** builds the theme and plugin and keeps the release (no block sources) as an artifact.
4. **Deploy:** on a push to `staging` or `master` that passed all three, the release is copied to that branch's site with rsync over SSH. It uses that branch's GitHub environment (`staging` for `staging`, `production` for `master`): `SSH_HOST`, `SSH_PORT` (Hostinger uses 65002), `SSH_USER` and `SSH_PRIVATE_KEY` as repository secrets, and `WP_CONTENT_PATH` per environment. Without them the job skips itself.

Only the theme and plugin are deployed; WordPress core, uploads and the database live on the server. Nothing is built there. Purge the LiteSpeed cache after a deploy if it serves stale pages.

## Local-only files

The seed scripts (`bin/`) and working notes are gitignored. Your `.wp-env.override.json` is also local; it maps the seed content and runs it on `env:start`.
