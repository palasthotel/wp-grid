# Grid for WordPress

A containerist landing page editor: editors compose pages from containers and boxes and
publish them as drafts and revisions. This is the WordPress integration of the
[grid library](https://github.com/palasthotel/grid); the plugin is available on
[wordpress.org](https://wordpress.org/plugins/grid/).

## Installation

Install it from wordpress.org, or download `grid.zip` from the
[latest release](https://github.com/palasthotel/wp-grid/releases/latest) and upload it
under Plugins → Add New. Grid needs PHP 8.2 and WordPress 6.1 or later.

Enable Grid for a post type under Grid → Settings. Posts of that type then get an
"Edit Grid" action.

## Development

The plugin gets the grid library through Composer and builds the library's editor bundle
itself:

```sh
npm run build    # composer install in public/, then npm ci && npm run build in the grid library
```

Run that once after cloning and whenever `public/composer.lock` changes. It needs PHP 8.2,
Composer and Node.js.

To try it, mount the repository with [wp-env](https://www.npmjs.com/package/@wordpress/env):

```sh
npx @wordpress/env start      # http://localhost:8888, admin / password
```

`wordpress_plugin.php` in the repository root is a development wrapper that loads
`public/`. Only `public/` ships to wordpress.org.

`npm run pack` stages the payload in `build/grid/` and zips it to `grid.zip` - the same
payload the release deploys. It runs the shared script from
[palasthotel/github-workflows](https://github.com/palasthotel/github-workflows), which has
to be checked out next to this repository.

See [CONTRIBUTING.md](CONTRIBUTING.md) for commit messages and releases, and
[SECURITY.md](SECURITY.md) for reporting vulnerabilities.

## License

GPL-3.0-or-later, see [LICENSE](LICENSE).
