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
 * Service allowing a user to change its own information.
 *
 * Some restriction are in place so user cannot change some profile fields.
 *
 * @package     filter_envf
 * @copyright   CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
$functions = [
    'filter_envf_update_user' => [
        'classname' => '\filter_envf\manage',
        'methodname' => 'update_user',
        'description' => 'User updates own information',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],
];
