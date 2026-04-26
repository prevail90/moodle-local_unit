<?php
// This file is part of Moodle - http://moodle.org/

namespace local_unit;

defined('MOODLE_INTERNAL') || die();

use local_unit\local\profile_sync;

/**
 * Event observers for user profile sync.
 */
class observer {
    /** @var array User ids already queued for end-of-request sync. */
    private static $queued = [];

    /**
     * Sync a newly created user when possible.
     *
     * @param \core\event\user_created $event User-created event.
     */
    public static function user_created(\core\event\user_created $event): void {
        self::sync_now_and_at_shutdown((int) $event->objectid);
    }

    /**
     * Sync an updated user when possible.
     *
     * @param \core\event\user_updated $event User-updated event.
     */
    public static function user_updated(\core\event\user_updated $event): void {
        self::sync_now_and_at_shutdown((int) $event->objectid);
    }

    /**
     * Reconcile the fields at login in case profile-field saving happened later.
     *
     * @param \core\event\user_loggedin $event User-login event.
     */
    public static function user_loggedin(\core\event\user_loggedin $event): void {
        profile_sync::sync_user((int) $event->objectid);
    }

    /**
     * Sync immediately and once more after the request finishes.
     *
     * Moodle profile edits may save custom profile fields after the user_updated
     * event is fired, so the shutdown sync sees the final unit_uic value.
     *
     * @param int $userid User id.
     */
    private static function sync_now_and_at_shutdown(int $userid): void {
        profile_sync::sync_user($userid);

        if (isset(self::$queued[$userid])) {
            return;
        }

        self::$queued[$userid] = true;
        register_shutdown_function(static function() use ($userid): void {
            profile_sync::sync_user($userid);
        });
    }
}
