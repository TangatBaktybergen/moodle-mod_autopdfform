<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Restore task for Auto PDF Form activity module.
 *
 * @package    mod_autopdfform
 * @copyright  2025 Ivan Volosyak and Tangat Baktybergen <Ivan.Volosyak@@@hochschule-rhein-waal.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/autopdfform/backup/moodle2/restore_autopdfform_stepslib.php');
/**
 * Restore task for the Auto PDF Form activity module.
 *
 * This class defines all the settings, steps, and decoding rules
 * required to restore an Auto PDF Form activity from a Moodle backup.
 *
 * @package    mod_autopdfform
 * @copyright  2025 Ivan Volosyak and Tangat Baktybergen
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_autopdfform_activity_task extends restore_activity_task {
    /**
     * Define (add) particular settings this activity can have.
     */
    protected function define_my_settings() {
        // No particular settings.
    }

    /**
     * Define (add) particular steps this activity can have.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_autopdfform_activity_structure_step('autopdfform_structure', 'autopdfform.xml'));
    }

    /**
     * Define the contents in the activity that must be processed by the link decoder.
     */
    public static function define_decode_contents() {
        return [
            new restore_decode_content(
                'autopdfform',
                ['intro'],
                'autopdfform',
            ),
        ];
    }

    /**
     * Define the decoding rules for links belonging to the activity.
     */
    public static function define_decode_rules() {
        return [
            new restore_decode_rule('AUTOPDFFORMVIEWBYID', '/mod/autopdfform/view.php?id=$1', 'course_module'),
            new restore_decode_rule('AUTOPDFFORMINDEX', '/mod/autopdfform/index.php?id=$1', 'course'),
        ];
    }

    /**
     * Define restore log rules that will be applied when restoring activity logs.
     */
    public static function define_restore_log_rules() {
        return [
            new restore_log_rule('autopdfform', 'add', 'view.php?id={course_module}', '{autopdfform}'),
            new restore_log_rule('autopdfform', 'update', 'view.php?id={course_module}', '{autopdfform}'),
            new restore_log_rule('autopdfform', 'view', 'view.php?id={course_module}', '{autopdfform}'),
        ];
    }

    /**
     * Define restore log rules that apply to course logs (cmid = 0).
     */
    public static function define_restore_log_rules_for_course() {
        return [
            new restore_log_rule('autopdfform', 'view all', 'index.php?id={course}', null),
        ];
    }
}
