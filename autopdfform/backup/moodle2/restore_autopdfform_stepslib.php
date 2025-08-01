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
 * Restore structure step for Auto PDF Form activity module.
 *
 * @package    mod_autopdfform
 * @copyright  2025 Ivan Volosyak and Tangat Baktybergen <Ivan.Volosyak@@@hochschule-rhein-waal.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_autopdfform_activity_task
 */

/**
 * Structure step to restore one autopdfform activity
 */
class restore_autopdfform_activity_structure_step extends restore_activity_structure_step {
    /**
     * Defines the structure of the restore process for the Auto PDF Form activity.
     *
     * @return array List of restore_path_element objects.
     */
    protected function define_structure() {

        $paths = [];
        $paths[] = new restore_path_element('autopdfform', '/activity/autopdfform');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }
    /**
     * Processes a restored Auto PDF Form record.
     *
     * @param array $data The data for the restored activity.
     */
    protected function process_autopdfform($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
        // See MDL-9367.

        // Insert the autopdfform record.
        $newitemid = $DB->insert_record('autopdfform', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }
    /**
     * Called after all steps in the restore process have executed.
     *
     * Adds any related files (e.g., the intro attachments).
     */
    protected function after_execute() {
        // Add choice related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_autopdfform', 'intro', null);
    }
}
