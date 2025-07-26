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
 * Version file for Auto PDF Form activity module.
 *
 * @package    mod_autopdfform
 * @copyright  2025 Ivan Volosyak and Tangat Baktybergen <Ivan.Volosyak@@@hochschule-rhein-waal.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/autopdfform/backup/moodle2/backup_autopdfform_stepslib.php');

/**
 * Provides the steps to perform one complete backup of the Auto PDF Form instance
 */
class backup_autopdfform_activity_task extends backup_activity_task {
    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the resource.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_autopdfform_activity_structure_step('autopdfform_structure', 'autopdfform.xml'));
    }

    /**
     * Encodes URLs to the autopdfform index.php and view.php scripts during backup.
     *
     * This allows links in the intro field (or other HTML content) to be restored properly
     * when the course is restored on another site or with a different course ID.
     *
     * @param string $content HTML content potentially containing URLs.
     * @return string The content with encoded URLs.
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of activities.
        $search = "/(" . $base . "\/mod\/autopdfform\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@AUTOPDFFORMINDEX*$2@$', $content);

        // Link to activity view by moduleid.
        $search = "/(" . $base . "\/mod\/autopdfform\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@AUTOPDFFORMVIEWBYID*$2@$', $content);

        return $content;
    }
}
