<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Utilities for the plugin
 *
 * @package     filter_envf
 * @copyright   CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_envf\local;
defined('MOODLE_INTERNAL') || die();

use completion_info;
use core_user;
use html_writer;

class utils {
    public static function get_user_profile_form($context, $formdata = null) {
        global $CFG;
        require_once($CFG->dirroot . '/user/editlib.php');
        require_once($CFG->dirroot . '/user/edit_form.php');
        $user = core_user::get_user($context->instanceid);
        // Load user preferences.
        useredit_load_preferences($user);

        // Load custom profile fields data.
        profile_load_data($user);

        // Prepare the editor and create form.
        $editoroptions = array(
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $CFG->maxbytes,
            'trusttext' => false,
            'forcehttps' => false,
            'context' => $context
        );

        $user = file_prepare_standard_editor($user,
            'description',
            $editoroptions,
            $context,
            'user',
            'profile',
            0);
        // Prepare filemanager draft area.
        $draftitemid = 0;
        $filemanagercontext = $editoroptions['context'];
        $filemanageroptions = array('maxbytes' => $CFG->maxbytes,
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => 'optimised_image');
        file_prepare_draft_area($draftitemid,
            $filemanagercontext->id,
            'user',
            'newicon',
            0,
            $filemanageroptions);
        $user->imagefile = $draftitemid;
        // Create form.
        if (class_exists('\local_envf\forms\user_edit_form')) {
            $userform = new \local_envf\forms\user_edit_form(null, array(
                'user' => $user,
                'allowchangepassword' => false), 'post', '', null, true, $formdata);
        } else {
            $userform = new \user_edit_form(null, array(
                'editoroptions' => $editoroptions,
                'filemanageroptions' => $filemanageroptions,
                'user' => $user), 'post', '', null, true, $formdata);
        }
        return $userform;
    }

    public static function replace_with_activity_list(array $matches) {
        global $DB;
        $activitylist = '';
        if ($matches && count($matches)) {
            $courseidnumber = $matches[1];
            $course = $DB->get_record('course', array('idnumber' => $courseidnumber));
            if ($course) {
                $completion = new completion_info($course);
                if ($completion->is_enabled()) {
                    $modinfo = get_fast_modinfo($course->id);
                    $activitylist .= html_writer::start_tag('ul', array('class' => 'coursecompletion-act-list'));
                    foreach ($modinfo->instances as $module => $instances) {
                        foreach ($instances as $index => $cm) {
                            if ($cm->completion != COMPLETION_TRACKING_NONE && $cm->is_visible_on_course_page()) {
                                $content = format_string($cm->name);
                                if (method_exists($cm->url, 'out')) {
                                    $content = html_writer::link($cm->url, $content,
                                        array('class' => $cm->available ? '' : 'disabled'));
                                }
                                $activitylist .= html_writer::tag('li', $content);
                            }
                        }
                    }
                    $activitylist .= html_writer::end_tag('ul');
                }
            }
        }
        return $activitylist;
    }
}