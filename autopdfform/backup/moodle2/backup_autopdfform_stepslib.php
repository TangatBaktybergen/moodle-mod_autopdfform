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
 * Backup structure step for the Auto PDF Form activity.
 *
 * @package    mod_autopdfform
 * @copyright  2025 Ivan Volosyak and Tangat Baktybergen <Ivan.Volosyak@@@hochschule-rhein-waal.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_autopdfform_activity_structure_step extends backup_activity_structure_step {
    /**
     * Defines the structure of the Auto PDF Form activity for backup.
     *
     * @return backup_nested_element The root element of the backup structure.
     */
    protected function define_structure() {

        // Define the root element.
        $autopdfform = new backup_nested_element('autopdfform', ['id'], [
            'name', 'intro', 'introformat',
        ]);

        // Set the source table.
        $autopdfform->set_source_table('autopdfform', ['id' => backup::VAR_ACTIVITYID]);

        // Annotate files (e.g., intro attachments).
        $autopdfform->annotate_files('mod_autopdfform', 'intro', null);

        // Return the structure wrapped in the standard activity element.
        return $this->prepare_activity_structure($autopdfform);
    }
}
