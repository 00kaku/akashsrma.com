# akashsrma.com

**The source of [akashsrma.com](https://akashsrma.com), Akash Sharma's portfolio, built as a printed magazine on WordPress.**

[![CI](https://github.com/00kaku/akashsrma.com/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/00kaku/akashsrma.com/actions/workflows/ci.yml)
![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777bb4?logo=php&logoColor=white)
![WordPress 6.8+](https://img.shields.io/badge/WordPress-6.8%2B-21759b?logo=wordpress&logoColor=white)
![PHPStan level 6](https://img.shields.io/badge/PHPStan-level%206-4f5b93)
![License GPL-2.0-or-later](https://img.shields.io/badge/license-GPL--2.0--or--later-blue)

On a desktop the site reads like a magazine: pages turn like paper. On tablets and phones it becomes a single scrolling edition. Behind it is a standard WordPress block theme and plugin, held to production standards: typed and namespaced PHP, static analysis, integration tests and automated deploys.

---

## Highlights

- **Page-turn engine.** Vanilla ES modules with no animation library. Each leaf is made of 7 hinged strips that bend as it turns, driven by a small physics model with its own unit tests.
- **Two-way responsive design.** Landscape screens 1024px and wider get the book; everything else gets a scroll-snapped mobile edition. It's a different reading experience, not a shrunken copy.
- **Content and design are separate.** The plugin owns the content and the theme owns the look, and they talk through one documented filter. Next year's redesign is a new theme; the content stays.
- **Design tokens only.** Every colour, type size and spacing value comes from `theme.json` presets. Fonts are self-hosted variable fonts (Bodoni Moda, Libre Franklin, Source Serif 4).
- **Modern WordPress.** Block theme, `block.json` blocks built with `@wordpress/scripts`, the Interactivity API for front-end state, and the Settings API for options.
- **Engineering hygiene.** PSR-4 autoloading, WordPress Coding Standards, PHPStan level 6, 32 PHPUnit integration tests, Vitest unit tests, pre-commit linting, and CI that tests, builds and deploys every push.

## Architecture

The repo holds two independent packages:

| Package | Role | Lifetime |
| --- | --- | --- |
| **[Akashsrma Features](plugins/akashsrma-features)** (plugin) | What the site holds: Portfolio and Learning collections, their fields, the site's sections and content blocks | Permanent, whatever the theme |
| **[Magazine](themes/akashsrma-magazine)** (theme) | How it looks: a printed magazine with turning pages on wide screens and swipeable pages on phones | One design; a redesign is a new theme |

```
┌──────────────────────┐   akashsrma_sections filter    ┌──────────────────────┐
│  Akashsrma Features  │ ─────────────────────────────▶ │  Magazine theme      │
│  post types, fields, │                                │  spreads, page turns │
│  sections, blocks    │ ◀───────────────────────────── │  mobile edition      │
└──────────────────────┘   add_theme_support(max_posts) └──────────────────────┘
```

**The contract.** The theme reads content only through the `akashsrma_sections` filter. Each section has a `key` (`page-{ID}` or the post type), a `type` (`page` or `collection`), a `label`, a `url`, and either a `post` or a `collection` (`post_type`, `name`, `description`, `slug`, `posts`). The theme declares how many posts a collection may show with `add_theme_support( 'akashsrma-features', array( 'max_posts' => 5 ) )`.

Either package runs without the other. Without the plugin, the theme shows its closed cover and the Magazine screen explains what's missing.

| Admin screen | Owner | Controls |
| --- | --- | --- |
| **Content → Sections** | plugin | Which Pages and collections make up the site, their labels and order, and which posts a collection shows |
| **Appearance → Magazine** | theme | Issue number, city, masthead, cover and fillers; per section: bookmark, colour, opening page, index style |

### How the PHP is organised

- **Autoloading.** Each package has its own Composer PSR-4 autoloader (`Akashsrma\Features\Content\Sections` → `inc/Content/Sections.php`). Adding a class means adding a file.
- **Modules.** Each feature is a class implementing `Core\Module`: `can_register()` decides whether it applies (admin screens only in wp-admin, for example) and `register()` adds its hooks. `Plugin::modules()` and `Theme::modules()` list them and pass in their dependencies.
- **Config as data.** The content model (post types, fields, taxonomies, templates, starting sections) lives in `plugins/akashsrma-features/config/`. A new field is an array entry, not new code.
- **Theme-gated blocks.** A plugin block that only makes sense in one theme is mapped to a theme feature in `config/blocks.php`, for example `'my-block' => 'magazine'`; `null` means any theme.

## Project structure

```
.
├── plugins/akashsrma-features/
│   ├── akashsrma-features.php      bootstrap
│   ├── config/                     content model as data
│   ├── inc/                        Core/, Content/, Admin/, Blocks/: one class per job
│   ├── src/blocks/                 block sources
│   ├── build/, vendor/             built blocks and Composer autoloader (committed; CI-verified)
│   └── assets/                     Sections screen script and styles
├── themes/akashsrma-magazine/
│   ├── theme.json, style.css       design tokens; theme header
│   ├── functions.php               bootstrap
│   ├── inc/                        Core/, Setup/, Routing/, Settings/, Admin/, Layout/
│   ├── src/blocks/magazine/        the Magazine block: render, page-turn engine, mobile edition, styles
│   ├── templates/, patterns/       block templates and starter patterns
│   └── assets/fonts/               self-hosted variable fonts
├── tests/
│   ├── php/                        PHPUnit integration tests (Features/, Magazine/)
│   └── js/                         Vitest unit tests (page physics)
└── .github/workflows/ci.yml        lint, test, build, deploy
```

## Getting started

Requirements: Node 20+ (`.nvmrc` pins 24), npm 10+, PHP 8.2+, Composer, and Docker for [`wp-env`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/).

```sh
npm install
composer install
npm run build            # PHP autoloaders + blocks
npm run env:start        # http://localhost:8890 (admin / password); tests site on :8889
```

| Command | What it does |
| --- | --- |
| `npm run build` | Generates both packages' autoloaders, then builds their blocks |
| `npm start` | Same, then rebuilds blocks on change |
| `npm run env:start` / `env:stop` / `env:reset` | Local WordPress with wp-env |
| `npm run env:cli -- <command>` | WP-CLI on the dev site, e.g. `npm run env:cli -- post list` |
| `npm run lint` | ESLint, Stylelint, PHPCS (WordPress standards) and PHPStan |
| `npm test` | Vitest, then PHPUnit (needs `env:start`) |
| `npm run format` | Prettier (WordPress style) and PHPCBF |

## Testing and quality

- **PHPUnit** (`tests/php`) runs inside wp-env's tests site against WordPress's own test library, with content made by WordPress factories.
    - `Features/` covers post types, fields, the Sections store and filter, the theme's post limit, comments and blocks.
    - `Magazine/` covers settings defaults and stored older values, the spread layout, and 404s for missing spreads.
- **Vitest** (`tests/js`) tests the page-turn physics.
- **Static analysis:** PHPStan level 6 with WordPress stubs; PHPCS with WordPress Coding Standards and PHP compatibility checks.
- **Pre-commit:** Husky and lint-staged lint every staged file.

## CI and deployment

[`ci.yml`](.github/workflows/ci.yml) runs on every push and pull request:

1. **Checks:** lint, static analysis and JS tests.
2. **PHPUnit:** starts WordPress on the runner and runs the integration tests.
3. **Build check:** the host has no build step, so each package's `build/` and `vendor/` (Composer autoloader) are committed. CI rebuilds them and fails if they differ from git.
4. **Deploy:** a push to `master` that passes all three asks the server to deploy. The server backs up the database, fast-forwards its checkout to the branch and purges the page cache. The theme and plugin folders in `wp-content` link into that checkout, so the site runs exactly what's in git. The same workflow, run by hand, rolls back to the commit that was live before the last deploy.

The deploy key can't open a shell: it's a forced-command key that can only run the deploy script for this site.

## License

[GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html), like WordPress itself.
