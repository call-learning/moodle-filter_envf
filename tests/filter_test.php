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
 * Plugin version and other meta-data are defined here.
 *
 * @package     filter_envf
 * @copyright   CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/filter/envf/filter.php'); // Include the code to test.

/**
 * Test case for the envf filter.
 *
 * @package     filter_envf
 * @copyright   CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_envf_filter_testcase extends advanced_testcase {

    /**
     * @var stdClass|null $course
     */
    protected $course = null;
    /**
     * @var stdClass|null $course
     */
    protected $pages = [];

    /**
     * Setup the test: a course with two pages
     */
    public function setUp() {
        $this->resetAfterTest(true);

        // Create a test course.
        $course = $this->getDataGenerator()->create_course(array('idnumber' => 'qcourse', 'enablecompletion' => 1));
        // Create two pages that will be linked to.
        $this->pages[] = $this->getDataGenerator()->create_module('page',
            ['course' => $course->id, 'name' => 'Step 1', 'completion' => 2, 'completionview' => 1]);
        $this->pages[] = $this->getDataGenerator()->create_module('page',
            ['course' => $course->id, 'name' => 'Step 2', 'completion' => 2, 'completionview' => 1]);
        $this->course = $course;
        filter_set_global_state('envf', TEXTFILTER_ON);
    }

    /**
     * Test that basic activity list is generated
     */
    public function test_activity_list_filter() {
        $context = context_course::instance($this->course->id);
        $html = "<span>{courseprogress courseidnumber=\"qcourse\"}</span>";
        $filtered = format_text($html, FORMAT_HTML, array('context' => $context));

        // There should be 0 links links.
        $this->assertNotContains('<ul class="coursecompletion-act-list">', $filtered);
        $this->assertContains('Not enrolled in course , please contact us', $filtered);

        // Now with an enrolled user.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $this->course->id);
        $filtered = format_text($html, FORMAT_HTML, array('context' => $context));

        // Find all the links in the result.
        $matches = [];
        preg_match_all('|href="[^"]*/mod/page/view.php\?id=([0-9]+)"|',
            $filtered, $matches);

        $this->assertContains('<ul class="coursecompletion-act-list">', $filtered);
        // There should be 2 links links.
        $this->assertNotNull($matches);
        $this->assertCount(2, $matches);
        $this->assertCount(2, $matches);
        $this->assertCount(3, $matches[1]);
        // Check the text of the li.
        $this->assertEquals($this->pages[0]->cmid, $matches[1][1]);
        $this->assertEquals($this->pages[1]->cmid, $matches[1][2]);
    }

    /**
     * Test that basic user filter html is generated
     */
    public function test_user_profile_filter() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $context = context_course::instance($this->course->id);
        $html = "<span>{userprofileform}<span></span>";
        $filtered = format_text($html, FORMAT_HTML, array('context' => $context));
        // There should be 1 user profile form.
        $this->assertContains('<div class="user_profile_form"', $filtered);
    }

    /**
     * Test that output is as expected. This also test file loading into the plugin.
     *
     * @dataProvider activity_list_provider
     * @param int $expectedcount
     * @param array $completions
     * @param array $expectedactivities
     * @throws moodle_exception
     */
    public function test_activity_list_renderer($expectedcount, $completions, $expectedactivities) {
        global $PAGE;
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $this->course->id);
        $completion = new completion_info($this->course);
        $modinfo = get_fast_modinfo($this->course);
        $this->setAdminUser(); // To override completions.
        foreach ($completions as $index => $status) {
            $completion->update_state($modinfo->get_cm($this->pages[$index]->cmid), $status, $user->id, true);
        }
        $renderable = new \filter_envf\output\course_activity_list($this->course, $user);
        $result = $renderable->export_for_template($PAGE->get_renderer('core'));
        $this->assertCount($expectedcount, $result->activities);
        foreach ($result->activities as $index => $activity) {
            foreach ($expectedactivities[$index] as $key => $expected) {
                if ($key === 'hasactionbutton') {
                    $this->assertEquals($expected, !empty($activity->actionbutton), 'Failed action button check:'
                        . $activity->name);
                    continue;
                }
                $this->assertEquals($expected, $activity->$key);
            }
        }
    }

    /**
     * Data provider for test_activity_list_renderer test
     *
     * @return array[]
     */
    public function activity_list_provider() {
        global $CFG;
        require_once($CFG->libdir.'/completionlib.php');
        return array(
            'No completion' => array(
                'expectedcount' => 2,
                'completions' => array(COMPLETION_NOT_VIEWED, COMPLETION_NOT_VIEWED),
                'activities' => array(
                    array('name' => 'Step 1', 'isdisabled' => false, 'iscompleted' => false, 'hasactionbutton' => true),
                    array('name' => 'Step 2', 'isdisabled' => false, 'iscompleted' => false, 'hasactionbutton' => false)
                ),
            ),
            'First activity completed' => array(
                'expectedcount' => 2,
                'completions' => array(COMPLETION_COMPLETE, COMPLETION_NOT_VIEWED),
                'activities' => array(
                    array('name' => 'Step 1', 'isdisabled' => false, 'iscompleted' => true, 'hasactionbutton' => false),
                    array('name' => 'Step 2', 'isdisabled' => false, 'iscompleted' => false, 'hasactionbutton' => true)
                )
            ),
            'Both activity completed' => array(
                'expectedcount' => 2,
                'completions' => array(COMPLETION_COMPLETE, COMPLETION_COMPLETE),
                'activities' => array(
                    array('name' => 'Step 1', 'isdisabled' => false, 'iscompleted' => true, 'hasactionbutton' => false),
                    array('name' => 'Step 2', 'isdisabled' => false, 'iscompleted' => true, 'hasactionbutton' => true)
                )
            ),
            'Only second activity completed' => array(
                'expectedcount' => 2,
                'completions' => array(COMPLETION_NOT_VIEWED, COMPLETION_COMPLETE),
                'activities' => array(
                    array('name' => 'Step 1', 'isdisabled' => false, 'iscompleted' => false, 'hasactionbutton' => true),
                    array('name' => 'Step 2', 'isdisabled' => false, 'iscompleted' => true, 'hasactionbutton' => false)
                )
            ),
        );
    }

}
