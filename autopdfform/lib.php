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
    $data->timemodified = $data->timecreated;
    $data->course = $COURSE->id;

    // Ensure introformat default if form didn't provide it.
    if (!isset($data->introformat)) {
        $data->introformat = FORMAT_HTML;
    }

    $id = $DB->insert_record('autopdfform', $data);

    // Save uploaded template file (one PDF) into filearea 'template'.
    if (!empty($data->coursemodule)) {
        $context = context_module::instance($data->coursemodule);
        file_save_draft_area_files(
            $data->templatefile,
            $context->id,
            'mod_autopdfform',
            'template',
            0,
            ['subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1, 'accepted_types' => ['.pdf']]
        );
    }

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

    if (!isset($data->introformat)) {
        $data->introformat = FORMAT_HTML;
    }

    $DB->update_record('autopdfform', $data);

    // Save/replace the template file.
    if (!empty($data->coursemodule)) {
        $context = context_module::instance($data->coursemodule);
        file_save_draft_area_files(
            $data->templatefile,
            $context->id,
            'mod_autopdfform',
            'template',
            0,
            ['subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1, 'accepted_types' => ['.pdf']]
        );
    }

    return true;
}

/**
 * Deletes an autopdfform instance.
 *
 * @param int $id The module instance ID.
 * @return bool True on success, false on failure
 */
function autopdfform_delete_instance($id) {
    global $DB;

    // Obtain the main record.
    if (!$record = $DB->get_record('autopdfform', ['id' => (int)$id])) {
        return false;
    }

    // Find the associated course module (if any) to get the context.
    $cm = get_coursemodule_from_instance('autopdfform', $id, $record->course, false, IGNORE_MISSING);

    // Delete DB record first.
    $DB->delete_records('autopdfform', ['id' => $id]);

    // Remove any stored files under this component/context.
    if ($cm) {
        $context = context_module::instance($cm->id);
        $fs = get_file_storage();
        // Delete all fileareas for this component in this context.
        $fs->delete_area_files($context->id, 'mod_autopdfform');
    }

    return true;
}

/**
 * Provides coursemodule info for the course page.
 *
 * Called on course view to build cached_cm_info for this module.
 *
 * @param stdClass $cm The course_module record
 * @return cached_cm_info|null
 */
function autopdfform_get_coursemodule_info($cm) {
    global $DB;

    // Fetch minimal fields safely (avoid MUST_EXIST to prevent restore-time fatals).
    $record = $DB->get_record(
        'autopdfform',
        ['id' => $cm->instance],
        'id, name, intro, introformat',
        IGNORE_MISSING
    );

    if (!$record) {
        // Let core handle defaults; prevents course page crashes on partial data.
        return null;
    }

    $info = new cached_cm_info();

    if (!empty($record->name)) {
        $info->name = $record->name;
    }

    // Only show description on course page if the setting is enabled on the CM.
    if (!empty($cm->showdescription) && isset($record->intro)) {
        if (!isset($record->introformat)) {
            $record->introformat = FORMAT_HTML; // Sensible fallback.
        }
        $info->content = format_module_intro('autopdfform', $record, $cm->id, false);
    }

    return $info;
}

/**
 * Returns list of features supported by the module.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True/false if module supports feature, null if unknown
 */
function autopdfform_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_BACKUP_MOODLE2:
            return true;

        // Common sensible defaults (uncomment if needed by your module).
        // Case FEATURE_COMPLETION_TRACKS_VIEWS.

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
        'objectid' => $autopdfform->id,
    ];

    $event = \mod_autopdfform\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('autopdfform', $autopdfform);
    $event->trigger();

    // Mark completion (if enabled).
    $completion = new \completion_info($course);
    if ($completion->is_enabled($cm) == COMPLETION_TRACKING_AUTOMATIC) {
        $completion->set_module_viewed($cm);
    } else {
        // Still mark as viewed to be safe; Moodle ignores it if not tracked.
        $completion->set_module_viewed($cm);
    }
}
