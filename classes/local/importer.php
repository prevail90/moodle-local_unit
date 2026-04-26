<?php
// This file is part of Moodle - http://moodle.org/

namespace local_unit\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Imports UIC organization rows from bundled CSV files.
 */
class importer {
    /** @var string Moodle table name. */
    private const TABLE = 'local_unit_orgs';

    /** @var string[] CSV files bundled in this plugin root. */
    private const DEFAULT_FILES = [
        'LAU_PR_ORGS327WDARFF13.csv',
        'LAU_PR_ORGS327ARNGARNG13.csv',
    ];

    /**
     * Import all bundled CSV files.
     *
     * @return array Import stats keyed by source filename.
     */
    public static function import_default_files(): array {
        global $CFG;

        $plugindir = $CFG->dirroot . '/local/unit';
        $stats = [];

        foreach (self::DEFAULT_FILES as $filename) {
            $path = $plugindir . '/' . $filename;
            if (!is_readable($path)) {
                continue;
            }
            $stats[$filename] = self::import_file($path, $filename);
        }

        return $stats;
    }

    /**
     * Import one CSV file into the UIC lookup table.
     *
     * @param string $path Absolute CSV path.
     * @param string|null $source Source label stored with imported rows.
     * @return array Import counts.
     */
    public static function import_file(string $path, ?string $source = null): array {
        global $DB;

        if (!is_readable($path)) {
            throw new \moodle_exception('cannotreadfile', 'local_unit', '', $path);
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new \moodle_exception('cannotreadfile', 'local_unit', '', $path);
        }

        $headers = fgetcsv($handle, 0, ',', '"', '');
        if ($headers === false) {
            fclose($handle);
            return ['inserted' => 0, 'updated' => 0, 'skipped' => 0];
        }

        $columns = self::map_columns($headers);
        foreach (['UIC', 'PARENTUIC', 'SIMPLENAME'] as $required) {
            if (!array_key_exists($required, $columns)) {
                fclose($handle);
                throw new \moodle_exception('missingcolumns', 'local_unit', '', basename($path));
            }
        }

        $source = substr($source ?? basename($path), 0, 100);
        $now = time();
        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $uic = self::normalise_uic(self::cell($row, $columns['UIC']));
            if ($uic === '') {
                $skipped++;
                continue;
            }

            $record = (object) [
                'uic' => $uic,
                'parentuic' => self::normalise_uic(self::cell($row, $columns['PARENTUIC'])),
                'simplename' => substr(trim(self::cell($row, $columns['SIMPLENAME'])), 0, 255),
                'drrsaname' => self::field($row, $columns, ['DRRSANAME'], 255),
                'uicpath' => self::field($row, $columns, ['UICPATH'], 512),
                'namepath' => self::field($row, $columns, ['NAMEPATH'], 512),
                'locnm' => self::field($row, $columns, ['LOCNM'], 255),
                'city' => self::field($row, $columns, ['CITY'], 100),
                'state' => self::field($row, $columns, ['STATE'], 10),
                'country' => self::field($row, $columns, ['COUNTRY'], 100),
                'compo' => self::field($row, $columns, ['COMPO'], 10),
                'uictype' => self::field($row, $columns, ['UICTYPE'], 20),
                'doctpe' => self::field($row, $columns, ['DOCTPE', 'DOCTYPE'], 20),
                'sourcefile' => $source,
                'timemodified' => $now,
            ];

            $existing = $DB->get_record(self::TABLE, ['uic' => $record->uic], 'id', IGNORE_MISSING);
            if ($existing) {
                $record->id = $existing->id;
                $DB->update_record(self::TABLE, $record);
                $updated++;
            } else {
                $record->timecreated = $now;
                $DB->insert_record(self::TABLE, $record);
                $inserted++;
            }
        }

        fclose($handle);

        return [
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }

    /**
     * Find an imported organization by UIC.
     *
     * @param string $uic UIC value.
     * @return \stdClass|false
     */
    public static function find_by_uic(string $uic) {
        global $DB;

        return $DB->get_record(self::TABLE, ['uic' => self::normalise_uic($uic)], '*', IGNORE_MISSING);
    }

    /**
     * Normalise UICs for lookup.
     *
     * @param string|null $uic Raw UIC.
     * @return string
     */
    public static function normalise_uic(?string $uic): string {
        return strtoupper(trim((string) $uic));
    }

    /**
     * Convert CSV header row into a name-to-index map.
     *
     * @param array $headers CSV header cells.
     * @return array
     */
    private static function map_columns(array $headers): array {
        $columns = [];
        foreach ($headers as $index => $header) {
            $name = strtoupper(trim((string) $header));
            $name = preg_replace('/^\xEF\xBB\xBF/', '', $name);
            $columns[$name] = $index;
        }

        return $columns;
    }

    /**
     * Safely read a CSV cell.
     *
     * @param array $row CSV row.
     * @param int $index Column index.
     * @return string
     */
    private static function cell(array $row, int $index): string {
        return isset($row[$index]) ? (string) $row[$index] : '';
    }

    /**
     * Read and truncate an optional CSV field by one or more possible names.
     *
     * @param array $row CSV row.
     * @param array $columns Header map.
     * @param array $names Header names to try.
     * @param int $maxlength Maximum stored length.
     * @return string
     */
    private static function field(array $row, array $columns, array $names, int $maxlength): string {
        foreach ($names as $name) {
            if (array_key_exists($name, $columns)) {
                return substr(trim(self::cell($row, $columns[$name])), 0, $maxlength);
            }
        }

        return '';
    }
}
