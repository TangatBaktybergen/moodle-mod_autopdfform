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
 * Library functions for Auto PDF Form activity module.
 *
 * @package    mod_autopdfform
 * @copyright  2025 Ivan Volosyak and Tangat Baktybergen <Ivan.Volosyak@@@hochschule-rhein-waal.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds a new autopdfform instance.
 *
 * @param stdClass $data
 * @param mod_form $mform
 * @return int New instance ID
 */
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

/**
 * Updates an existing autopdfform instance.
 *
 * @param stdClass $data
 * @param mod_form $mform
 * @return bool True on success
 */
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

/**
 * Deletes an autopdfform instance.
 *
 * @param int $id
 * @return bool True on success, false on failure
 */
function autopdfform_delete_instance($id) {
    global $DB;

    // Get the record.
    if (!$record = $DB->get_record('autopdfform', ['id' => $id])) {
        return false;
    }
    // Delete the main table record.
    $DB->delete_records('autopdfform', ['id' => $id]);
    // Remove any files attached to this instance.
    $context = context_module::instance($record->coursemodule);
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'mod_autopdfform');

    return true;
}

/**
 * Provides coursemodule info for the course page.
 *
 * @param stdClass $coursemodule
 * @return cached_cm_info
 */
function autopdfform_get_coursemodule_info($coursemodule) {
    global $DB;

    $info = new cached_cm_info();

    if ($record = $DB->get_record('autopdfform', ['id' => $coursemodule->instance], '*')) {
        $info->name = $record->name;

        if (!empty($record->intro)) {
            // Format intro using Moodle's core formatting system.
            $info->content = format_module_intro('autopdfform', $record, $coursemodule->id, false);
        }
    }

    return $info;
}

/**
 * Returns list of features supported by the module.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if unknown
 */
function autopdfform_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        default:
            return null;
    }
}

/**
 * Triggered when a user views the Auto PDF Form activity.
 *
 * @param stdClass $autopdfform The module instance.
 * @param stdClass $course The course object.
 * @param stdClass $cm The course module object.
 * @param context_module $context The context object.
 */
function autopdfform_view($autopdfform, $course, $cm, $context) {
    // Trigger the course_module_viewed event.
    $params = [
        'context' => $context,
        'objectid' => $autopdfform->id
    ];

    $event = \mod_autopdfform\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('autopdfform', $autopdfform);
    $event->trigger();
}

