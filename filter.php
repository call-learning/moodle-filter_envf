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
use filter_envf\output\user_profile_form;

/**
 * Class filter_envf
 *
 * @package     filter_envf
 * @copyright   CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_envf extends moodle_text_filter {

    /**
     * Tag for form filter
     */
    const USER_PROFILE_FORM_TAG_NAME = 'userprofileform';
    /**
     * Tag for course progress (the courseidnumber must also be provided).
     */
    const COURSE_PROGRESSS_TAG_NAME = 'courseprogress';

    /**
     * Setup page with filter requirements and other prepare stuff.
     *
     * @param moodle_page $page The page we are going to add requirements to.
     * @param context $context The context which contents are going to be filtered.
     */
    public function setup($page, $context) {
        // This only requires execution once per request.
        static $jsinitialised = false;
        if ($jsinitialised) {
            return;
        }
        $jsinitialised = true;
        $page->requires->js_call_amd('filter_envf/userprofileform', 'init', [[
            'classmarker' => 'user_profile_form',
        ], ]);
    }

    /**
     * This function looks for tags in Moodle text and
     * replaces them with questions from the question bank.
     * Tags have the format {{CONTENT:xxx}} where:
     *          - xxx is the user specified content
     *
     * @param string $text to be processed by the text
     * @param array $options filter options
     * @return string text after processing
     */
    public function filter($text, array $options = []) {
        global $USER;

        // Basic test to avoid unnecessary work.
        if (!is_string($text)) {
            return $text;
        }
        $userprofilemarkerabsent = stripos($text, '{' . self::USER_PROFILE_FORM_TAG_NAME . '}') === false;
        $courseprogressmarkerabsent = stripos($text, '{' . self::COURSE_PROGRESSS_TAG_NAME) === false;
        if ($userprofilemarkerabsent && $courseprogressmarkerabsent) {
            // Performance shortcut - if there is no tag, nothing can match.
            return $text;
        }
        if (!$userprofilemarkerabsent) {
            global $PAGE;
            $renderer = $PAGE->get_renderer('filter_envf');
            $element = $renderer->render(new user_profile_form($USER->id, context_user::instance($USER->id)->id));
            $text = str_replace('{' . self::USER_PROFILE_FORM_TAG_NAME . '}', $element, $text);
        }
        if (!$courseprogressmarkerabsent) {
            global $CFG;
            if ($CFG->enablecompletion) {
                $text = preg_replace_callback(
                    '/{\s*' . self::COURSE_PROGRESSS_TAG_NAME . '\s+courseidnumber="(\w+)"\s*}/',
                    function($matches) {
                        return \filter_envf\local\utils::replace_with_activity_list($matches);
                    },
                    $text);
            }
        }
        return $text;
    }
}
