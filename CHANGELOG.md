# Changelog

## 1.0.0-rc.1

- Imports bundled WDARFF and ARNG organization CSV files into `local_unit_orgs`.
- Stores UIC, parent UIC, SIMPLENAME, DRRSANAME, location, path, component, type, document type, and source metadata.
- Syncs `department` and `institution` from `DRRSANAME` based on the user's custom profile field `unit_uic`.
- Normalizes stored `unit_uic` values to trimmed uppercase during user sync and scheduled sync.
- Adds CLI tools for CSV import and profile sync.
