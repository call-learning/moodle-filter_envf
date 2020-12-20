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
use core\plugininfo\mod;
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
                'allowchangepassword' => false,
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

    public static function replace_with_activity_list(array $matches) {
        global $DB;
        $activitylist = '';
        if ($matches && count($matches)) {
            $courseidnumber = $matches[1];
            $course = $DB->get_record('course', array('idnumber' => $courseidnumber));
            if ($course) {
                if (is_enrolled(\context_course::instance($course->id))) {
                    $completion = new completion_info($course);
                    if ($completion->is_enabled()) {
                        $activitylist .= html_writer::start_tag('ul', array('class' => 'coursecompletion-act-list'));
                        $foundincomplete = false;
                        $activities = $completion->get_activities();
                        $activitycount = count($activities);
                        foreach ($activities as $moduleid => $cm) {
                            if ($cm->is_visible_on_course_page()) {
                                $content = format_string($cm->name);
                                $userstatus = $completion->get_data($cm, false);
                                $available = $cm->available;
                                $completed = $userstatus->completionstate == COMPLETION_COMPLETE;

                                $content = format_string($cm->name);
                                if (method_exists($cm->url, 'out')) {
                                    $content = html_writer::link($cm->url, $content,
                                        array('class' => $available ? '' : 'disabled'));
                                }
                                if ((!$completed || $activitycount == 1) && $available && !$foundincomplete) {
                                    $content .= static::get_complete_or_download_button($cm);
                                    $foundincomplete = true;
                                }
                                $activitylist .= html_writer::tag('li',
                                    $content,
                                    array('class' => $completed ? 'status-completed' : '')
                                );
                            }
                            $activitycount--;
                        }
                        $activitylist .= html_writer::end_tag('ul');
                    }
                } else {
                    $activitylist = html_writer::div(get_string('notenrolledincourse', 'filter_envf',
                        $course->fullname), 'alert-warning');
                }
            }
        }
        return $activitylist;
    }

    protected static function get_complete_or_download_button(\cm_info $cm) {
        $link = $cm->url;
        $label = get_string('btncomplete', 'filter_envf');
        $attributes = ['class'=> 'btn btn-outline-primary d-inline-block ml-5 mb-1'];
        if ($cm->modname == 'customcert') {
            global $DB, $USER;
            // A bit of a hack here.
            $issueid = $DB->get_field('customcert_issues', 'id', array('customcertid' => $cm->instance, 'userid' => $USER->id));
            if ($issueid) {
                $link = new \moodle_url('/mod/customcert/view.php', array('id' => $cm->id, 'downloadissue' => $issueid));
                $label = get_string('download', 'filter_envf');
                $attributes['download'] = true;
            }
        }
        return html_writer::div(
            html_writer::link($link, $label, $attributes)
        );
    }
}