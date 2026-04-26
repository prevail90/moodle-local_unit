<?php
// This file is part of Moodle - http://moodle.org/

namespace local_unit\task;

defined('MOODLE_INTERNAL') || die();

use local_unit\local\profile_sync;

/**
 * Scheduled reconciliation for UIC-derived user profile fields.
 */
class sync_profiles_task extends \core\task\scheduled_task {
    /**
     * Task display name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('tasksyncprofiles', 'local_unit');
    }

    /**
     * Execute the sync.
     */
    public function execute(): void {
        profile_sync::sync_all();
    }
}
