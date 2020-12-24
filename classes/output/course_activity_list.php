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
 * Filter ENVF renderer
 *
 * @package     filter_envf
 * @copyright   CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_envf\output;
defined('MOODLE_INTERNAL') || die();

use completion_info;
use renderable;
use stdClass;
use templatable;
use renderer_base;

/**
 * Class course_activity_list
 *
 * @package     filter_envf
 * @copyright   CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_activity_list implements renderable, templatable {

    /**
     * @var stdClass|null $course related course
     */
    protected $course = null;
    /**
     * @var stdClass|null $user related user
     */
    protected $user = null;

    /**
     * course_activity_list constructor.
     *
     * @param stdClass $course
     * @param stdClass $user
     */
    public function __construct($course, $user) {
        $this->course = $course;
        $this->user = $user;
    }

    /**
     * Export for template
     *
     * @param renderer_base $output
     * @return stdClass
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function export_for_template(renderer_base $output) {
        $data = new stdClass();
        $data->activities = [];
        if (is_enrolled(\context_course::instance($this->course->id), $this->user)) {
            $completion = new completion_info($this->course);
            if ($completion->is_enabled()) {
                $activities = $completion->get_activities();
                $foundincomplete = false;
                $activitycount = count($activities);
                foreach ($activities as $cm) {
                    $activity = new stdClass();

                    if ($cm->is_visible_on_course_page()) {
                        $userstatus = $completion->get_data($cm, false, $this->user->id);
                        $available = $cm->available;
                        $completed = $userstatus->completionstate == COMPLETION_COMPLETE;
                        $activity->name = format_string($cm->name);

                        if (method_exists($cm->url, 'out')) {
                            $activity->isdisabled = !$available;
                            $activity->url = $cm->url->out();
                        }
                        if ((!$completed || $activitycount == 1) && $available && !$foundincomplete) {
                            $activity->actionbutton = $this->get_complete_or_download_button($cm);
                            $foundincomplete = true;
                        }
                        $activity->iscompleted = $completed;
                    }
                    $activitycount--;
                    $data->activities[] = $activity;

                }
            }
        } else {
            $data->notenrolled = true;
        }
        return $data;
    }

    /**
     * Get download button
     *
     * @param \cm_info $cm
     * @return mixed
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    protected function get_complete_or_download_button(\cm_info $cm) {
        $data = new stdClass();
        $data->link = $cm->url->out();
        $data->label = get_string('btncomplete', 'filter_envf');
        if ($cm->modname == 'customcert') {
            global $DB, $USER;
            // A bit of a hack here.
            $issueid = $DB->get_field('customcert_issues', 'id', array('customcertid' => $cm->instance, 'userid' => $USER->id));
            if ($issueid) {
                $data->link = new \moodle_url('/mod/customcert/view.php', array('id' => $cm->id, 'downloadissue' => $issueid));
                $data->label = get_string('download', 'filter_envf');
                $data->download = true;
            }
        }
        return $data;
    }
}
