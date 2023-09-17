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

use completion_info;
use renderable;
use stdClass;
use templatable;
use renderer_base;

/**
 * Class user_profile_form
 *
 * @package     filter_envf
 * @copyright   CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_profile_form implements renderable, templatable {
    /**
     * @var int|null $userid related userid
     */
    protected $userid = null;
    /**
     * @var int|null $contextid related contextid
     */
    protected $contextid = null;

    /**
     * course_activity_list constructor.
     *
     * @param int $userid
     * @param int $contextid
     */
    public function __construct($userid, $contextid) {
        $this->userid = $userid;
        $this->contextid = $contextid;
    }

    /**
     * Export for template
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        return (object) ['userid' => $this->userid, 'contextid' => $this->contextid];
    }

}
