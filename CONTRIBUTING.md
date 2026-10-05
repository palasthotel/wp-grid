# Contributing

## Branching

`main` is the default branch. Work on a feature branch and open a pull request against
`main`.

## Commit messages

Releases and the changelog are generated from the commit history by
[release-please](https://github.com/googleapis/release-please), so commit messages follow
[Conventional Commits](https://www.conventionalcommits.org/):

| Type | Effect on the version | Appears in changelog |
|---|---|---|
| `fix:` | patch | yes, "Bug Fixes" |
| `feat:` | minor | yes, "Features" |
| `!` after the type, or a `BREAKING CHANGE:` footer | major | yes, highlighted |
| `docs:`, `refactor:`, `build:`, `chore:`, `ci:`, `test:` | none | no |

When squash-merging, make sure the squash commit message itself is a conventional commit -
that is the message release-please reads.

### Which changes get `fix:` or `feat:`

Only changes that matter to someone using the plugin: `fix:` and `feat:` decide the
version and write the line that ends up in the changelog on wordpress.org. Workflows,
repository documentation and anything outside `public/` release nothing. Updates of the
grid library or SimplePie (`public/composer.lock`) ship with the plugin, so they are
`fix(deps):`.

## Repository layout

| Path | Description |
|---|---|
| `public/` | exactly what ships to wordpress.org, plus `composer.json`/`composer.lock` |
| `public/wordpress_plugin.php` | the plugin's main file |
| `public/vendor/` | built by `npm run build`, not committed: the grid library, SimplePie and the autoloader |
| `wordpress_plugin.php` | development wrapper, loads `public/`; never deployed |

The main file `public/wordpress_plugin.php` must keep its name. WordPress identifies an
installed plugin by `<directory>/<main file>` and stores that pair in `active_plugins`;
renaming it deactivates the plugin on every site at the next update.

## Versions

Never edit version numbers by hand. `package.json`, `CHANGELOG.md`,
`public/wordpress_plugin.php` and the `Stable tag:` in `public/readme.txt` are maintained
by the release pipeline. Content changes to `public/readme.txt` (description, FAQ,
tested-up-to) are done by hand; leave `Stable tag:` and the `== Changelog ==` entries
alone.

## Checks

Every pull request builds the plugin, runs `php -l` against PHP 8.2, 8.3 and 8.4, packs
the payload and checks it - including that the grid editor bundle was built - and checks
that the version carriers agree.

## Releases

Merging the release pull request that release-please keeps open tags the version; the tag
deploys the plugin to wordpress.org through the shared workflows in
[palasthotel/github-workflows](https://github.com/palasthotel/github-workflows).
