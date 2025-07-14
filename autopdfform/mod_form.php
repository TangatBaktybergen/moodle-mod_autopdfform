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
 * @copyright  2025 Ivan Volosyak and Tangat Baktybergen <Ivan.Volosyak@@@hochschule-rhein-waal.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_autopdfform_mod_form extends moodleform_mod {
    function definition() {
        $mform = $this->_form;
        // Activity name
        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        // Description (intro editor)
        $this->standard_intro_elements();
        // PDF file upload
        $mform->addElement('filepicker', 'templatefile', get_string('templatefile', 'autopdfform'), null, [
            'accepted_types' => ['.pdf'],
            'maxbytes' => 1 * 1024 * 1024,
        ]);
        $mform->addRule('templatefile', null, 'required', null, 'client');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    function data_preprocessing(&$default_values) {
        // Nothing special yet
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (trim($data['name']) === '') {
            $errors['name'] = get_string('required');
        }

        return $errors;
    }

}
