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

$id = required_param('id', PARAM_INT); // Course module ID.
$cm = get_coursemodule_from_id('autopdfform', $id, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
$autopdfform = $DB->get_record('autopdfform', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
require_capability('mod/autopdfform:view', $context);

// Trigger course_module_viewed event.
$event = \mod_autopdfform\event\course_module_viewed::create([
    'objectid' => $autopdfform->id,
    'context' => $context,
]);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('autopdfform', $autopdfform);
$event->trigger();

// Get current user info.
$fullname = fullname($USER);
$email = $USER->email;
$date = date('d.m.Y');
$firstname = $USER->firstname;
$lastname = $USER->lastname;
$username = $USER->username;

if (!empty($username) && strpos($username, '@') !== false) {
    $studentid = substr($username, 0, strpos($username, '@'));
} else {
    $studentid = $username ?: $USER->id;
}

// Fetch the uploaded PDF template.
$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'mod_autopdfform', 'template', 0, '', false);
$templatefile = reset($files);
$tempfilepath = $templatefile->copy_content_to_temp();

// Load PDF and replace placeholders.
$pdfcontent = file_get_contents($tempfilepath);
$replacements = [
    'n_date' => $date,
    'full_name' => $fullname,
    'student_id' => $studentid,
    'email_address' => $email,
];
foreach ($replacements as $search => $replace) {
    $pdfcontent = str_replace($search, $replace, $pdfcontent);
}

// Output filename.
$templatefilename = $templatefile->get_filename();
$basename = pathinfo($templatefilename, PATHINFO_FILENAME);
if (mb_strlen($basename) > 60) {
    $basename = mb_substr($basename, 0, 60);
}
$firstnamesafe = preg_replace('/[^A-Za-z0-9\-]/', '', $firstname);
$lastnamesafe = preg_replace('/[^A-Za-z0-9\-]/', '', $lastname);
$filename = $basename . '-' . $firstnamesafe . '-' . $lastnamesafe . '.pdf';

// Send file to browser.
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdfcontent));
echo $pdfcontent;

@unlink($tempfilepath);
