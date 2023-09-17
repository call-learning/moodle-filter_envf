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
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use filter_envf\local\utils;

/**
 * Form fragment callback
 *
 * @param array $args
 * @return string|null
 */
function filter_envf_output_fragment_userprofile_form($args) {
    global $CFG;
    $context = $args['context'];

    $formdata = [];
    if (!empty($args['jsonformdata'])) {
        $formdata = json_decode($args['jsonformdata'], true);
    }
    if ($context->contextlevel != CONTEXT_USER) {
        return null;
    }

    $mform = utils::get_user_profile_form($context, $formdata);
    $mform->set_data($formdata);
    $formvalid = true;
    if (!empty($formdata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $formvalid = $mform->is_validated();
    }
    return $mform->render() . html_writer::div('', 'upf-userformvalidated', array('data-validated' => $formvalid));
}
