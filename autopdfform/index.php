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
 * Index page for Auto PDF Form activity.
 *
 * @package    mod_autopdfform
 * @copyright  2025 Ivan Volosyak and Tangat Baktybergen
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->libdir . '/modinfolib.php');

$id = required_param('id', PARAM_INT); // Course ID.

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_course_login($course);

$context = context_course::instance($course->id);
require_capability('mod/autopdfform:view', $context);

$PAGE->set_url('/mod/autopdfform/index.php', ['id' => $id]);
$PAGE->set_title(get_string('modulenameplural', 'mod_autopdfform'));
$PAGE->set_heading($course->fullname);

// Trigger course_module_instance_list_viewed event.
$event = \mod_autopdfform\event\course_module_instance_list_viewed::create([
    'context' => $context,
]);
$event->add_record_snapshot('course', $course);
$event->trigger();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'mod_autopdfform'));

$modinfo = get_fast_modinfo($course);
$instances = $modinfo->get_instances_of('autopdfform');

if (empty($instances)) {
    notice(get_string('noautopdfforms', 'mod_autopdfform'), new moodle_url('/course/view.php', ['id' => $course->id]));
    echo $OUTPUT->footer();
    exit;
}

// Build table of visible instances.
$table = new html_table();
$table->head = [get_string('name'), get_string('intro', 'mod_autopdfform')];
$table->align = ['left', 'left'];

foreach ($instances as $cm) {
    if (!$cm->uservisible) {
        continue;
    }

    $name = html_writer::link(
        new moodle_url('/mod/autopdfform/view.php', ['id' => $cm->id]),
        format_string($cm->name)
    );

    $intro = format_module_intro('autopdfform', $cm->get_custom_data(), $cm->id);
    $table->data[] = [$name, $intro];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
