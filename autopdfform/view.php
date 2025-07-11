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
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

$id = required_param('id', PARAM_INT); // Course module ID
$cm = get_coursemodule_from_id('autopdfform', $id, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$context = context_module::instance($cm->id);
$instance = $DB->get_record('autopdfform', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($cm->course, true, $cm);
require_capability('mod/autopdfform:view', $context);

// Get current user info
$fullname = fullname($USER);
$email = $USER->email;
$date = date('d.m.Y');
$firstname = $USER->firstname;
$lastname = $USER->lastname;
$username = $USER->username;

if (!empty($USER->idnumber) && preg_match('/^\d{5}$/', $USER->idnumber)) {
    // Use idnumber if it's a 5-digit number
    $student_id = $USER->idnumber;
} elseif (!empty($USER->username) && preg_match('/^(\d{5})@students\.hsrw/i', $USER->username, $m)) {
    // Extract the 5-digit number from username
    $student_id = $m[1];
} elseif (!empty($USER->username) && strpos($USER->username, '@') !== false) {
    // Use everything before @ as a fallback
    $student_id = substr($USER->username, 0, strpos($USER->username, '@'));
} elseif (!empty($USER->username)) {
    $student_id = $USER->username;
} else {
    $student_id = $USER->id;
}

// Fetch the uploaded PDF template
$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'mod_autopdfform', 'template', 0, '', false);
if (empty($files)) {
    print_error('No PDF template uploaded by teacher.');
}
$templatefile = reset($files);
$tempfilepath = $templatefile->copy_content_to_temp();

// Load PDF as binary and replace placeholders
$pdfcontent = file_get_contents($tempfilepath);

$replacements = [
    'n_date' => $date,
    'full_name' => $fullname,
    'student_id' => $student_id,
    'email_address' => $email,
];

foreach ($replacements as $search => $replace) {
    $pdfcontent = str_replace($search, $replace, $pdfcontent);
}

// Send the modified PDF to browser
$fullname = preg_replace('/\s+/', '_', fullname($USER));
$filename = 'LoanForm_' . $fullname . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdfcontent));
echo $pdfcontent;

@unlink($tempfilepath);