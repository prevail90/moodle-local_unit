<?php
// This file is part of Moodle - http://moodle.org/

namespace local_unit\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for local_unit.
 */
class provider implements \core_privacy\local\metadata\null_provider {
    /**
     * Explain that this plugin does not store personal data in its own tables.
     *
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
