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
 * View page for Auto PDF Form activity.
 *
 * @package    mod_autopdfform
 * @copyright  2025 Ivan Volosyak and Tangat Baktybergen
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

$id = required_param('id', PARAM_INT);
$download = optional_param('download', 0, PARAM_BOOL);

// Fetch CM, course, context, instance.
$cm = get_coursemodule_from_id('autopdfform', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
$autopdfform = $DB->get_record('autopdfform', ['id' => $cm->instance], '*', MUST_EXIST);

// Login + capability.
require_login($course, true, $cm);
require_capability('mod/autopdfform:view', $context);

// Page setup for GET flow.
$PAGE->set_url('/mod/autopdfform/view.php', ['id' => $id]);
$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(format_string($autopdfform->name));
$PAGE->set_heading(format_string($course->fullname));

// Fetch the uploaded PDF template (expect exactly one).
$fs = get_file_storage();
$files = $fs->get_area_files(
    $context->id,
    'mod_autopdfform',
    'template',
    0,
    'itemid, filepath, filename',
    false
);
$templatefile = $files ? reset($files) : null;

// GET: show intro + download button.
if (!$download) {
    echo $OUTPUT->header();

    // Back button.
    echo html_writer::tag('button', get_string('back'), [
        'type' => 'button',
        'onclick' => 'window.history.back();',
        'class' => 'btn btn-secondary',
        'style' => 'margin-bottom:15px;',
    ]);

    // Activity description.
    if (!empty($autopdfform->intro)) {
        echo $OUTPUT->box(
            format_module_intro('autopdfform', $autopdfform, $cm->id),
            'generalbox mod_introbox',
            'autopdfformintro'
        );
    }

    // If no template uploaded, inform user.
    if (!$templatefile) {
        echo $OUTPUT->notification(get_string('filenotfound', 'error'), 'notifyproblem');
        echo $OUTPUT->footer();
        exit;
    }

    // Simple download form (POST + sesskey).
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => new moodle_url('/mod/autopdfform/view.php'),
        'style'  => 'margin-top:12px;',
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $id]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'download', 'value' => 1]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', [
        'type'  => 'submit',
        'class' => 'btn btn-primary',
        'value' => get_string('download'),
    ]);
    echo html_writer::end_tag('form');

    echo $OUTPUT->footer();
    exit;
}

// POST: generate + stream personalized PDF.
require_sesskey();

if (!$templatefile) {
    throw new moodle_exception('filenotfound', 'error');
}

// Trigger view event + completion (standard).
$event = \mod_autopdfform\event\course_module_viewed::create([
    'objectid' => $autopdfform->id,
    'context'  => $context,
]);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('autopdfform', $autopdfform);
$event->trigger();

$completion = new \completion_info($course);
$completion->set_module_viewed($cm);

// Build user fields.
$fullname  = fullname($USER);
$email     = (string)$USER->email;
$date      = userdate(time(), '%d.%m.%Y');
$firstname = (string)$USER->firstname;
$lastname  = (string)$USER->lastname;
$username  = (string)$USER->username;

if ($username !== '' && strpos($username, '@') !== false) {
    $studentid = substr($username, 0, strpos($username, '@'));
} else {
    $studentid = $username !== '' ? $username : (string)$USER->id;
}

// Copy template to temp.
$tempfilepath = $templatefile->copy_content_to_temp();
if (!$tempfilepath || !file_exists($tempfilepath)) {
    throw new moodle_exception('errorprocessingrequest', 'error', '', null, 'Template copy failed');
}

// Replace placeholders in raw PDF bytes (works if placeholders are uncompressed text).
$pdfcontent = file_get_contents($tempfilepath);
if ($pdfcontent === false) {
    @unlink($tempfilepath);
    throw new moodle_exception('errorprocessingrequest', 'error', '', null, 'Cannot read temp PDF');
}

$replacements = [
    'n_date'        => $date,
    'full_name'     => $fullname,
    'student_id'    => $studentid,
    'email_address' => $email,
    'first_name'    => $firstname,
    'last_name'     => $lastname,
];

foreach ($replacements as $search => $replace) {
    $pdfcontent = str_replace($search, $replace, $pdfcontent);
}

// Prepare output filename.
$templatefilename = $templatefile->get_filename();
$basename = pathinfo($templatefilename, PATHINFO_FILENAME);
if (function_exists('mb_strlen') && mb_strlen($basename) > 60) {
    $basename = mb_substr($basename, 0, 60);
} else if (strlen($basename) > 60) {
    $basename = substr($basename, 0, 60);
}
$firstnamesafe = preg_replace('/[^A-Za-z0-9\-]/', '', $firstname);
$lastnamesafe  = preg_replace('/[^A-Za-z0-9\-]/', '', $lastname);
$finalname = $basename . '-' . $firstnamesafe . '-' . $lastnamesafe . '.pdf';

// Write modified bytes to temp and stream.
$finaltempdir = make_temp_directory('mod_autopdfform_' . $USER->id . '_' . time());
check_dir_exists($finaltempdir, true, true);
$finaltemp = $finaltempdir . '/out.pdf';
file_put_contents($finaltemp, $pdfcontent);

// Clean original template temp.
@unlink($tempfilepath);

// Stream (deletes temp after sending).
send_temp_file($finaltemp, $finalname, false);
exit;
