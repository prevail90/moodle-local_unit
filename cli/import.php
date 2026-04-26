<?php
// This file is part of Moodle - http://moodle.org/

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognised] = cli_get_params(
    [
        'help' => false,
        'file' => null,
    ],
    [
        'h' => 'help',
        'f' => 'file',
    ]
);

if ($unrecognised) {
    $unrecognised = implode(PHP_EOL . '  ', $unrecognised);
    cli_error("Unrecognised options: {$unrecognised}");
}

if ($options['help']) {
    $help = "Import UIC organization CSV data.

Options:
  -h, --help          Print this help.
  -f, --file=PATH    Import one CSV file. Defaults to the bundled CSV files.

Example:
  php local/unit/cli/import.php
";
    cli_writeln($help);
    exit(0);
}

if ($options['file']) {
    $stats = [basename($options['file']) => \local_unit\local\importer::import_file($options['file'])];
} else {
    $stats = \local_unit\local\importer::import_default_files();
}

foreach ($stats as $source => $counts) {
    cli_writeln(sprintf(
        '%s: inserted %d, updated %d, skipped %d',
        $source,
        $counts['inserted'],
        $counts['updated'],
        $counts['skipped']
    ));
}
