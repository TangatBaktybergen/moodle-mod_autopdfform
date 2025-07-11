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

// File: mod/autopdfform/lang/en/autopdfform.php

$string['modulename'] = 'Auto-Filled PDF Form';
$string['modulenameplural'] = 'Auto-Filled PDF Forms';
 
$string['autopdfform:addinstance'] = 'Add a new auto-filled PDF form';
$string['autopdfform:view'] = 'View auto-filled PDF form';

$string['pluginname'] = 'Auto-Filled PDF Form';
$string['templatefile'] = 'Upload fillable PDF template (max 1 MB)';
$string['templatefile_help'] = 'Upload a fillable PDF form (AcroForm format). Student data will be inserted
into the appropriate fields.';
$string['pluginadministration'] = 'Auto-Filled PDF Form Administration';


$string['modulename_help'] = 'This activity allows students to download a personalized PDF form, pre-filled with their own data.

The teacher must upload a fillable PDF form created using Adobe Acrobat (or a compatible PDF editor). 
To ensure the form can be saved after being filled, please enable "Save Form Data"
functionality in Adobe Acrobat: File → Save As Other → Reader Extended PDF → Enable More Tools (includes form fill-in & save).

The following placeholder field names will be replaced automatically:

- full_name → student’s full name
- student_id → student’s ID number
- email_address → student’s email address
- n_date → current date (DD.MM.YYYY)

These placeholders must exactly match the field names used in the PDF for the replacement to work correctly.';

