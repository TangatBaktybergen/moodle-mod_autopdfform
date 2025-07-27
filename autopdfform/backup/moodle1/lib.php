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
 * Provides support for the conversion of moodle1 backup to the moodle2 format
 *
 * @package    mod_autopdfform
 * @copyright  2025 Ivan Volosyak and Tangat Baktybergen <Ivan.Volosyak@@@hochschule-rhein-waal.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Resource conversion handler
 */
class moodle1_mod_autopdfform_handler extends moodle1_mod_handler {
    /** @var moodle1_file_manager instance */
    protected $fileman = null;

    /** @var array List of resource successor handlers */
    private $successors = [];

    /**
     * Declare the paths in moodle.xml we are able to convert
     *
     * The method returns a list of {@see convert_path} instances.
     * For each path returned, the corresponding conversion method must be
     * defined.
     *
     * Note that the paths /MOODLE_BACKUP/COURSE/MODULES/MOD/RESOURCE do not
     * actually exist in the file. The last element with the module name was
     * appended by the moodle1_converter class.
     *
     * @return array of {@see convert_path} instances
     */
    public function get_paths() {
        return [
            new convert_path(
                'resource',
                '/MOODLE_BACKUP/COURSE/MODULES/MOD/RESOURCE',
                [
                    'renamefields' => [
                        'summary' => 'intro',
                    ],
                    'newfields' => [
                        'introformat' => 0,
                    ],
                    'dropfields' => [
                        'modtype',
                    ],
                ],
            ),
        ];
    }

    /**
     * Converts /MOODLE_BACKUP/COURSE/MODULES/MOD/RESOURCE data.
     *
     * This method detects the legacy resource type and delegates processing
     * to a successor module handler if applicable, or converts the data to
     * the new mod_autopdfform format.
     *
     * @param array $data Parsed resource data from moodle.xml.
     * @param array $raw  Raw resource data before mapping/conversion.
     * @return void
     */
    public function process_resource(array $data, array $raw) {
        global $CFG;
        require_once("$CFG->libdir/resourcelib.php");

        // Replay the upgrade step 2009042001.
        if ($CFG->texteditors !== 'textarea') {
            $data['intro']       = text_to_html($data['intro'], false, false, true);
            $data['introformat'] = FORMAT_HTML;
        }

        // Fix invalid null popup and options data.
        if (!array_key_exists('popup', $data) || is_null($data['popup'])) {
            $data['popup'] = '';
        }
        if (!array_key_exists('options', $data) || is_null($data['options'])) {
            $data['options'] = '';
        }

        // Decide if the legacy resource should be handled by a successor module.
        if ($successor = $this->get_successor($data['type'], $data['reference'])) {
            // The instance id will be kept.
            $instanceid = $data['id'];

            // Move the instance from the resource's modinfo stash to the successor's.
            // Modinfo stash.
            $resourcemodinfo  = $this->converter->get_stash('modinfo_autopdfform');
            $successormodinfo = $this->converter->get_stash('modinfo_' . $successor->get_modname());
            $successormodinfo['instances'][$instanceid] = $resourcemodinfo['instances'][$instanceid];
            unset($resourcemodinfo['instances'][$instanceid]);
            $this->converter->set_stash('modinfo_resource', $resourcemodinfo);
            $this->converter->set_stash('modinfo_' . $successor->get_modname(), $successormodinfo);

            // Get the course module information for the legacy resource module.
            $cminfo = $this->get_cminfo($instanceid);

            // Use the version of the successor instead of the current mod/resource.
            // Beware - the version.php declares info via $module object, do not use.
            // A variable of such name here.
            $plugin = new stdClass();
            $plugin->version = null;
            $module = $plugin;
            include($CFG->dirroot . '/mod/' . $successor->get_modname() . '/version.php');
            $cminfo['version'] = $plugin->version;

            // Stash the new course module information for this successor.
            $cminfo['modulename'] = $successor->get_modname();
            $this->converter->set_stash('cminfo_' . $cminfo['modulename'], $cminfo, $instanceid);

            // Rewrite the coursecontents stash.
            $coursecontents = $this->converter->get_stash('coursecontents');
            $coursecontents[$cminfo['id']]['modulename'] = $successor->get_modname();
            $this->converter->set_stash('coursecontents', $coursecontents);

            // Delegate the processing to the successor handler.
            return $successor->process_legacy_resource($data, $raw);
        }

        // No successor is interested in this record, convert it to the new mod_resource (aka File module).

        $resource = [];
        $resource['id']              = $data['id'];
        $resource['name']            = $data['name'];
        $resource['intro']           = $data['intro'];
        $resource['introformat']     = $data['introformat'];
        $resource['tobemigrated']    = 0;
        $resource['legacyfiles']     = RESOURCELIB_LEGACYFILES_ACTIVE;
        $resource['legacyfileslast'] = null;
        $resource['filterfiles']     = 0;
        $resource['revision']        = 1;
        $resource['timemodified']    = $data['timemodified'];

        // Populate display and displayoptions fields.
        $options = ['printintro' => 1];
        if ($data['options'] == 'frame') {
            $resource['display'] = RESOURCELIB_DISPLAY_FRAME;
        } else if ($data['options'] == 'objectframe') {
            $resource['display'] = RESOURCELIB_DISPLAY_EMBED;
        } else if ($data['options'] == 'forcedownload') {
            $resource['display'] = RESOURCELIB_DISPLAY_DOWNLOAD;
        } else if ($data['popup']) {
            $resource['display'] = RESOURCELIB_DISPLAY_POPUP;
            $rawoptions = explode(',', $data['popup']);
            foreach ($rawoptions as $rawoption) {
                [$name, $value] = explode('=', trim($rawoption), 2);
                if ($value > 0 && ($name == 'width' || $name == 'height')) {
                    $options['popup' . $name] = $value;
                    continue;
                }
            }
        } else {
            $resource['display'] = RESOURCELIB_DISPLAY_AUTO;
        }
        $resource['displayoptions'] = serialize($options);

        // Get the course module id and context id.
        $instanceid     = $resource['id'];
        $currentcminfo  = $this->get_cminfo($instanceid);
        $moduleid       = $currentcminfo['id'];
        $contextid      = $this->converter->get_contextid(CONTEXT_MODULE, $moduleid);

        // Get a fresh new file manager for this instance.
        $this->fileman = $this->converter->get_file_manager($contextid, 'mod_resource');

        // Convert course files embedded into the intro.
        $this->fileman->filearea = 'intro';
        $this->fileman->itemid   = 0;
        $resource['intro'] = moodle1_converter::migrate_referenced_files($resource['intro'], $this->fileman);

        // Convert the referenced file itself as a main file in the content area.
        $reference = $data['reference'];
        if (strpos($reference, '$@FILEPHP@$') === 0) {
            $reference = str_replace(
                ['$@FILEPHP@$', '$@SLASH@$', '$@FORCEDOWNLOAD@$'],
                ['', '/', ''],
                $reference
            );
        }

        $this->fileman->filearea = 'content';
        $this->fileman->itemid   = 0;

        // Rebuild the file path.
        $curfilepath = '/';
        if ($reference) {
            $curfilepath = pathinfo('/' . $reference, PATHINFO_DIRNAME);
            if ($curfilepath != '/') {
                $curfilepath .= '/';
            }
        }
        try {
            $this->fileman->migrate_file('course_files/' . $reference, $curfilepath, null, 1);
        } catch (moodle1_convert_exception $e) {
            // The file probably does not exist.
            $this->log('error migrating the resource main file', backup::LOG_WARNING, 'course_files/' . $reference);
        }

        // Write resource.xml.
        $this->open_xml_writer("activities/autopdfform_{$moduleid}/autopdfform.xml");
        $this->xmlwriter->begin_tag('activity', [
            'id' => $instanceid,
            'moduleid' => $moduleid,
            'modulename' => 'resource',
            'contextid' => $contextid,
        ]);

        $this->write_xml('autopdfform', $resource, ['/resource/id']);
        $this->xmlwriter->end_tag('activity');
        $this->close_xml_writer();

        // Write inforef.xml.
        $this->open_xml_writer("activities/autopdfform_{$currentcminfo['id']}/autopdfform.xml");
        $this->xmlwriter->begin_tag('inforef');
        $this->xmlwriter->begin_tag('fileref');
        foreach ($this->fileman->get_fileids() as $fileid) {
            $this->write_xml('file', ['id' => $fileid]);
        }
        $this->xmlwriter->end_tag('fileref');
        $this->xmlwriter->end_tag('inforef');
        $this->close_xml_writer();
    }

    /**
     * Gives successor modules a chance to finish any post-processing
     * related to the legacy resource after it has been handled.
     *
     * @param array $data The resource data from the original backup.
     * @return void
     */
    public function on_resource_end(array $data) {
        if ($successor = $this->get_successor($data['type'], $data['reference'])) {
            $successor->on_legacy_resource_end($data);
        }
    }

    // Internal implementation details follow.

    /**
     * Returns the handler of the new 2.0 mod type according the given type of the legacy 1.9 resource
     *
     * @param string $type the value of the 'type' field in 1.9 resource
     * @param string $reference a file path. Necessary to differentiate files from web URLs
     * @throws moodle1_convert_exception for the unknown types
     * @return null|moodle1_mod_handler the instance of the handler, or null if the type does not have a successor
     */
    protected function get_successor($type, $reference) {

        switch ($type) {
            case 'text':
            case 'html':
                $name = 'page';
                break;
            case 'directory':
                $name = 'folder';
                break;
            case 'ims':
                $name = 'imscp';
                break;
            case 'file':
                // If starts with $@FILEPHP@$ then it is URL link to a local course file.
                // To be migrated to the new resource module.
                if (strpos($reference, '$@FILEPHP@$') === 0) {
                    $name = null;
                    break;
                }
                // If http:// https:// ftp:// OR starts with slash need to be converted to URL.
                if (strpos($reference, '://') || strpos($reference, '/') === 0) {
                    $name = 'url';
                } else {
                    $name = null;
                }
                break;
            default:
                throw new moodle1_convert_exception('unknown_resource_successor', $type);
        }

        if (is_null($name)) {
            return null;
        }

        if (!isset($this->successors[$name])) {
            $this->log('preparing resource successor handler', backup::LOG_DEBUG, $name);
            $class = 'moodle1_mod_' . $name . '_handler';
            $this->successors[$name] = new $class($this->converter, 'mod', $name);

            // Add the successor into the modlist stash.
            $modnames = $this->converter->get_stash('modnameslist');
            $modnames[] = $name;
            $modnames = array_unique($modnames); // Should not be needed but just in case.
            $this->converter->set_stash('modnameslist', $modnames);

            // Add the successor's modinfo stash.
            $modinfo = $this->converter->get_stash('modinfo_resource');
            $modinfo['name'] = $name;
            $modinfo['instances'] = [];
            $this->converter->set_stash('modinfo_' . $name, $modinfo);
        }

        return $this->successors[$name];
    }
}
