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
 * @copyright  2025 Ivan Volosyak and Tangat Baktybergen <Ivan.Volosyak@@@hochschule-rhein-waal.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

$id = required_param('id', PARAM_INT); // Course module ID.
$cm = get_coursemodule_from_id('autopdfform', $id, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
$instance = $DB->get_record('autopdfform', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($cm->course, true, $cm);
require_capability('mod/autopdfform:view', $context);

// Get current user info.
$fullname = fullname($USER);
$email = $USER->email;
$date = date('d.m.Y');
$firstname = $USER->firstname;
$lastname = $USER->lastname;
$username = $USER->username;

// Use everything before the first @ in username, or fallback to username/id.
if (!empty($USER->username) && strpos($USER->username, '@') !== false) {
    $studentid = substr($USER->username, 0, strpos($USER->username, '@'));
} else if (!empty($USER->username)) {
    $studentid = $USER->username;
} else {
    $studentid = $USER->id;
}

// Fetch the uploaded PDF template.
$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'mod_autopdfform', 'template', 0, '', false);
$templatefile = reset($files);
$tempfilepath = $templatefile->copy_content_to_temp();

// Load PDF as binary and replace placeholders.
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

// Generate output filename: original base name (max 60 chars) + firstname + lastname (cleaned), with dashes.
$templatefilename = $templatefile->get_filename();
$basename = pathinfo($templatefilename, PATHINFO_FILENAME);
if (mb_strlen($basename) > 60) {
    $basename = mb_substr($basename, 0, 60);
}
$firstnamesafe = preg_replace('/[^A-Za-z0-9\-]/', '', $firstname);
$lastnamesafe  = preg_replace('/[^A-Za-z0-9\-]/', '', $lastname);
$filename = $basename . '-' . $firstnamesafe . '-' . $lastnamesafe . '.pdf';

// Output the PDF to the browser.
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdfcontent));
echo $pdfcontent;

@unlink($tempfilepath);
