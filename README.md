# Unit Profile Sync

Release candidate: `1.0.0-rc.1`

Moodle local plugin component: `local_unit`

This plugin imports bundled UIC organization CSV files into a lookup table and uses the custom user profile field `unit_uic` to populate Moodle's built-in user `department` and `institution` fields.

The lookup table keeps these CSV fields for later features: `UIC`, `PARENTUIC`, `SIMPLENAME`, `DRRSANAME`, `UICPATH`, `NAMEPATH`, `LOCNM`, `CITY`, `STATE`, `COUNTRY`, `COMPO`, `UICTYPE`, and `DOCTPE`. The ARNG file uses the header `DOCTYPE`; the importer stores that value in the plugin table's `doctpe` column.

For a user with `unit_uic` set:

- `department` is set to that UIC row's `DRRSANAME`.
- `institution` is set to the parent UIC row's `DRRSANAME`, using `PARENTUIC` to find the parent row.
- `unit_uic` is normalized to trimmed uppercase by the scheduled task and during per-user syncs.
- If `unit_uic` is empty or does not match an imported UIC, `unit_uic`, `department`, and `institution` are cleared.

## Installation

Place this folder at:

```text
local/unit
```

Then visit Moodle's admin notifications page or run:

```sh
php admin/cli/upgrade.php
```

The bundled CSV files are imported during installation and upgrade.

## Updating CSV Data

The lookup table is refreshed from the CSV files by the importer. Records are matched by `UIC`, so an updated CSV row updates the existing table row instead of creating a duplicate.

After pulling a new plugin version from GitHub:

```sh
php admin/cli/upgrade.php
```

If the plugin version changed, Moodle runs the upgrade steps and reimports the bundled CSV files when an upgrade step calls the importer.

To force-refresh the table at any time after replacing the CSV files:

```sh
php local/unit/cli/import.php
```

Recommended update workflow for future CSV releases:

- Replace `LAU_PR_ORGS327WDARFF13.csv` and/or `LAU_PR_ORGS327ARNGARNG13.csv`.
- Bump `$plugin->version` in `version.php`.
- Add an upgrade savepoint in `db/upgrade.php` that calls `\local_unit\local\importer::import_default_files();`.
- Update this README if the CSV source, field mapping, or profile-field behavior changes.
- Run `php admin/cli/upgrade.php`, then `php local/unit/cli/sync.php`.

## GitHub Releases

Pushing a version tag creates a GitHub Release and attaches a Moodle-installable ZIP file:

```sh
git tag v1.0.0-rc.1
git push origin main --tags
```

The release workflow builds the ZIP as `unit-<tag>.zip` with a top-level `unit/` folder, so it can be installed through Moodle's plugin installer or extracted to `local/unit`.

## Required Moodle Profile Field

Create a custom user profile field with shortname:

```text
unit_uic
```

Recommended field configuration:

- Category: `Unit`
- Type: text input
- Name: `UIC`
- Shortname: `unit_uic`
- Required: yes
- Display on signup page: yes
- Maximum length: `6`

Department and Institution are Moodle's standard user profile fields, stored on the `user` table.

## CLI

Reimport bundled CSV files:

```sh
php local/unit/cli/import.php
```

Sync all users:

```sh
php local/unit/cli/sync.php
```

Sync one user:

```sh
php local/unit/cli/sync.php --userid=123
```
