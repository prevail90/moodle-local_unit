<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Populate bundled UIC data after the plugin table is installed.
 */
function xmldb_local_unit_install(): void {
    \local_unit\local\importer::import_default_files();
}
