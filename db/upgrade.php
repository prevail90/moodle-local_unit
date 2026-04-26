<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade steps for local_unit.
 *
 * @param int $oldversion The previously installed version.
 * @return bool
 */
function xmldb_local_unit_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026042600) {
        $table = new xmldb_table('local_unit_orgs');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('uic', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL);
            $table->add_field('parentuic', XMLDB_TYPE_CHAR, '10');
            $table->add_field('simplename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '');
            $table->add_field('drrsaname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '');
            $table->add_field('uicpath', XMLDB_TYPE_CHAR, '512', null, XMLDB_NOTNULL, null, '');
            $table->add_field('namepath', XMLDB_TYPE_CHAR, '512', null, XMLDB_NOTNULL, null, '');
            $table->add_field('locnm', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '');
            $table->add_field('city', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, '');
            $table->add_field('state', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, '');
            $table->add_field('country', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, '');
            $table->add_field('compo', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, '');
            $table->add_field('uictype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, '');
            $table->add_field('doctpe', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, '');
            $table->add_field('sourcefile', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, '');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('uic_uix', XMLDB_INDEX_UNIQUE, ['uic']);
            $table->add_index('parentuic_ix', XMLDB_INDEX_NOTUNIQUE, ['parentuic']);

            $dbman->create_table($table);
        }

        \local_unit\local\importer::import_default_files();
        upgrade_plugin_savepoint(true, 2026042600, 'local', 'unit');
    }

    if ($oldversion < 2026042601) {
        $table = new xmldb_table('local_unit_orgs');
        $fields = [
            new xmldb_field('drrsaname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, ''),
            new xmldb_field('uicpath', XMLDB_TYPE_CHAR, '512', null, XMLDB_NOTNULL, null, ''),
            new xmldb_field('namepath', XMLDB_TYPE_CHAR, '512', null, XMLDB_NOTNULL, null, ''),
            new xmldb_field('locnm', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, ''),
            new xmldb_field('city', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, ''),
            new xmldb_field('state', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, ''),
            new xmldb_field('country', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, ''),
            new xmldb_field('compo', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, ''),
            new xmldb_field('uictype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, ''),
            new xmldb_field('doctpe', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, ''),
        ];

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        \local_unit\local\importer::import_default_files();
        upgrade_plugin_savepoint(true, 2026042601, 'local', 'unit');
    }

    if ($oldversion < 2026042602) {
        $table = new xmldb_table('local_unit_orgs');
        $field = new xmldb_field('country', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, '');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        \local_unit\local\importer::import_default_files();
        upgrade_plugin_savepoint(true, 2026042602, 'local', 'unit');
    }

    if ($oldversion < 2026042603) {
        upgrade_plugin_savepoint(true, 2026042603, 'local', 'unit');
    }

    if ($oldversion < 2026042604) {
        upgrade_plugin_savepoint(true, 2026042604, 'local', 'unit');
    }

    return true;
}
