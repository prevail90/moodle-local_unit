<?php
// This file is part of Moodle - http://moodle.org/

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognised] = cli_get_params(
    [
        'help' => false,
        'userid' => null,
        'limit' => 0,
    ],
    [
        'h' => 'help',
        'u' => 'userid',
        'l' => 'limit',
    ]
);

if ($unrecognised) {
    $unrecognised = implode(PHP_EOL . '  ', $unrecognised);
    cli_error("Unrecognised options: {$unrecognised}");
}

if ($options['help']) {
    $help = "Sync user department and institution from custom profile field unit_uic.

Options:
  -h, --help          Print this help.
  -u, --userid=ID    Sync one user.
  -l, --limit=N      Limit users when syncing all.

Example:
  php local/unit/cli/sync.php
";
    cli_writeln($help);
    exit(0);
}

if ($options['userid']) {
    $updated = \local_unit\local\profile_sync::sync_user((int) $options['userid']) ? 1 : 0;
} else {
    $updated = \local_unit\local\profile_sync::sync_all((int) $options['limit']);
}

cli_writeln("Updated {$updated} user record(s).");
