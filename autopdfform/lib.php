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
 * Main library for Auto PDF Form plugin
 *
 * @package    mod_autopdfform
 * @copyright  2025 Ivan Volosyak and Tangat Baktybergen <Ivan.Volosyak@hochschule-rhein-waal.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

function autopdfform_add_instance($data, $mform) {
    global $DB, $COURSE;

    $data->timecreated = time();
    $data->course = $COURSE->id;  

    $id = $DB->insert_record('autopdfform', $data);

    $context = context_module::instance($data->coursemodule);
    file_save_draft_area_files(
        $data->templatefile,
        $context->id,
        'mod_autopdfform',
        'template',
        0,
        ['subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1, 'accepted_types' => ['.pdf']]
    );

    return $id;
}

function autopdfform_update_instance($data, $mform) {
    global $DB, $COURSE;

    $data->timemodified = time();
    $data->course = $COURSE->id;  
    $data->id = $data->instance;

    $DB->update_record('autopdfform', $data);

    $context = context_module::instance($data->coursemodule);
    file_save_draft_area_files(
        $data->templatefile,
        $context->id,
        'mod_autopdfform',
        'template',
        0,
        ['subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1, 'accepted_types' => ['.pdf']]
    );

    return true;
}
 
function autopdfform_get_coursemodule_info($coursemodule) {
    global $DB;

    $info = new cached_cm_info();

    if ($record = $DB->get_record('autopdfform', ['id' => $coursemodule->instance], '*')) {
        $info->name = $record->name;

        if (!empty($record->intro)) {
            // Format intro using Moodle's core formatting system
            $info->content = format_module_intro('autopdfform', $record, $coursemodule->id, false);
        }
    }

    return $info;
}

function autopdfform_delete_instance($id) {
    global $DB;

    // Get the record
    if (!$record = $DB->get_record('autopdfform', ['id' => $id])) {
        return false;
    }
    // Delete the main table record
    $DB->delete_records('autopdfform', ['id' => $id]);
    // Remove any files attached to this instance
    $context = context_module::instance($record->coursemodule);
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'mod_autopdfform');

    return true;
}
 
function autopdfform_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO: return true;
        default: return null;
    }
}
