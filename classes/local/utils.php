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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Utilities for the plugin
 *
 * @package     filter_envf
 * @copyright   CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace filter_envf\local;
use core_user;
use filter_envf\output\course_activity_list;
use theme_envf\form\user_edit_form;

/**
 * Class utils
 *
 * @package     filter_envf
 * @copyright   CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {
    /**
     * Get the profile form
     * @param \context $context
     * @param object $formdata
     * @return user_edit_form
     * @throws \coding_exception
     * @throws \dml_exception
     */
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
        // Load user preferences.
        useredit_load_preferences($user);
        // Create form.
        if (class_exists('\theme_envf\form\user_edit_form')) {
            $userform = new user_edit_form(null, array(
                'user' => $user,
                'allowchangepassword' => false,
                'allowchangeemail' => false,
                'displayemail' => true,
                'btnclassoverride' => 'btn btn-outline-primary',
                'hascancelbutton' => false),
                'post', '', null, true, $formdata);
        } else {
            $userform = new \user_edit_form(null, array(
                'editoroptions' => $editoroptions,
                'filemanageroptions' => $filemanageroptions,
                'user' => $user), 'post', '', null, true, $formdata);
        }
        return $userform;
    }

    /**
     * Replace with activity list
     *
     * @param array $matches
     * @return bool|string
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function replace_with_activity_list(array $matches) {
        global $DB, $PAGE;
        $activitylist = '';
        if ($matches && count($matches)) {
            $courseidnumber = $matches[1];
            $course = $DB->get_record('course', array('idnumber' => $courseidnumber));
            if ($course) {
                global $USER;
                $renderer = $PAGE->get_renderer('filter_envf');
                $activitylist = $renderer->render(new course_activity_list($course, $USER));
            }

        }
        return $activitylist;
    }

}