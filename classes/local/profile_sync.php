<?php
// This file is part of Moodle - http://moodle.org/

namespace local_unit\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Syncs Moodle user profile fields from imported UIC organization data.
 */
class profile_sync {
    /** @var string Custom profile field shortname used for the user's UIC. */
    private const UIC_FIELD = 'unit_uic';

    /**
     * Sync one user's department and institution fields from their unit_uic value.
     *
     * @param int $userid User id.
     * @return bool True when the user or profile data was updated.
     */
    public static function sync_user(int $userid): bool {
        global $DB;

        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], 'id,department,institution', IGNORE_MISSING);
        if (!$user) {
            return false;
        }

        self::normalise_user_uic($userid);

        $uic = self::get_custom_profile_value($userid, self::UIC_FIELD);
        if ($uic === '') {
            return self::clear_synced_profile_data($userid, $user);
        }

        $unit = importer::find_by_uic($uic);
        if (!$unit) {
            return self::clear_synced_profile_data($userid, $user);
        }

        $department = (string) $unit->drrsaname;
        $institution = '';

        if (!empty($unit->parentuic)) {
            $parent = importer::find_by_uic($unit->parentuic);
            if ($parent) {
                $institution = (string) $parent->drrsaname;
            }
        }

        if ($user->department === $department && $user->institution === $institution) {
            return false;
        }

        return self::update_user_department_and_institution($user, $department, $institution);
    }

    /**
     * Normalise one user's unit_uic custom profile value to trimmed uppercase.
     *
     * @param int $userid User id.
     * @return bool True when the profile data row was updated.
     */
    public static function normalise_user_uic(int $userid): bool {
        global $DB;

        $sql = "SELECT d.id, d.data
                  FROM {user_info_data} d
                  JOIN {user_info_field} f ON f.id = d.fieldid
                 WHERE d.userid = :userid
                   AND f.shortname = :shortname";
        $record = $DB->get_record_sql($sql, ['userid' => $userid, 'shortname' => self::UIC_FIELD], IGNORE_MISSING);
        if (!$record) {
            return false;
        }

        $normalised = importer::normalise_uic($record->data);
        if ($record->data === $normalised) {
            return false;
        }

        $DB->update_record('user_info_data', (object) [
            'id' => $record->id,
            'data' => $normalised,
        ]);

        return true;
    }

    /**
     * Normalise all stored unit_uic values to trimmed uppercase.
     *
     * @param int $limit Optional maximum number of profile rows to process.
     * @return int Number of updated profile data rows.
     */
    public static function normalise_all_uics(int $limit = 0): int {
        global $DB;

        $field = $DB->get_record('user_info_field', ['shortname' => self::UIC_FIELD], 'id', IGNORE_MISSING);
        if (!$field) {
            return 0;
        }

        $sql = "SELECT d.id, d.data
                  FROM {user_info_data} d
                  JOIN {user} u ON u.id = d.userid
                 WHERE u.deleted = 0
                   AND d.fieldid = :fieldid
                   AND " . $DB->sql_compare_text('d.data') . " <> ''";
        $recordset = $DB->get_recordset_sql($sql, ['fieldid' => $field->id], 0, $limit);

        $updated = 0;
        foreach ($recordset as $record) {
            $normalised = importer::normalise_uic($record->data);
            if ($record->data === $normalised) {
                continue;
            }

            $DB->update_record('user_info_data', (object) [
                'id' => $record->id,
                'data' => $normalised,
            ]);
            $updated++;
        }
        $recordset->close();

        return $updated;
    }

    /**
     * Sync all non-deleted users that have a unit_uic custom profile row.
     *
     * @param int $limit Optional maximum number of users to process.
     * @return int Number of updated user records.
     */
    public static function sync_all(int $limit = 0): int {
        global $DB;

        self::normalise_all_uics($limit);

        $field = $DB->get_record('user_info_field', ['shortname' => self::UIC_FIELD], 'id', IGNORE_MISSING);
        if (!$field) {
            return 0;
        }

        $sql = "SELECT u.id
                  FROM {user} u
                  JOIN {user_info_data} d ON d.userid = u.id
                 WHERE u.deleted = 0
                   AND d.fieldid = :fieldid";
        $params = ['fieldid' => $field->id];
        $recordset = $DB->get_recordset_sql($sql, $params, 0, $limit);

        $updated = 0;
        foreach ($recordset as $record) {
            if (self::sync_user((int) $record->id)) {
                $updated++;
            }
        }
        $recordset->close();

        return $updated;
    }

    /**
     * Read a custom profile field value.
     *
     * @param int $userid User id.
     * @param string $shortname Profile field shortname.
     * @return string
     */
    private static function get_custom_profile_value(int $userid, string $shortname): string {
        global $DB;

        $sql = "SELECT d.data
                  FROM {user_info_data} d
                  JOIN {user_info_field} f ON f.id = d.fieldid
                 WHERE d.userid = :userid
                   AND f.shortname = :shortname";
        $value = $DB->get_field_sql($sql, ['userid' => $userid, 'shortname' => $shortname]);

        return importer::normalise_uic($value ?: '');
    }

    /**
     * Clear a user's saved UIC profile value.
     *
     * @param int $userid User id.
     * @return bool True when the profile data row was updated.
     */
    private static function clear_user_uic(int $userid): bool {
        global $DB;

        $sql = "SELECT d.id, d.data
                  FROM {user_info_data} d
                  JOIN {user_info_field} f ON f.id = d.fieldid
                 WHERE d.userid = :userid
                   AND f.shortname = :shortname";
        $record = $DB->get_record_sql($sql, ['userid' => $userid, 'shortname' => self::UIC_FIELD], IGNORE_MISSING);
        if (!$record || $record->data === '') {
            return false;
        }

        $DB->update_record('user_info_data', (object) [
            'id' => $record->id,
            'data' => '',
        ]);

        return true;
    }

    /**
     * Clear Moodle profile fields managed by this plugin.
     *
     * @param int $userid User id.
     * @param \stdClass $user Current user record.
     * @return bool True when the user or profile data was updated.
     */
    private static function clear_synced_profile_data(int $userid, \stdClass $user): bool {
        $cleareduic = self::clear_user_uic($userid);
        $cleareduser = self::update_user_department_and_institution($user, '', '');

        return $cleareduic || $cleareduser;
    }

    /**
     * Update the Moodle fields derived from the UIC lookup.
     *
     * @param \stdClass $user Current user record.
     * @param string $department New department value.
     * @param string $institution New institution value.
     * @return bool True when the user record was updated.
     */
    private static function update_user_department_and_institution(
        \stdClass $user,
        string $department,
        string $institution
    ): bool {
        global $CFG;

        if ($user->department === $department && $user->institution === $institution) {
            return false;
        }

        require_once($CFG->dirroot . '/user/lib.php');
        user_update_user((object) [
            'id' => $user->id,
            'department' => $department,
            'institution' => $institution,
            'timemodified' => time(),
        ], false, false);

        return true;
    }
}
