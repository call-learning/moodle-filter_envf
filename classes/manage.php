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
 * Manage user profile
 *
 * @package     filter_envf
 * @copyright   CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_envf;
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("$CFG->libdir/externallib.php");

use context_system;
use core_user;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use invalid_parameter_exception;

/**
 * Class manage
 *
 * @package     filter_envf
 * @copyright   CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manage extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @throws \coding_exception
     * @since Moodle 2.2
     */
    public static function update_user_parameters() {
        $userfields = [
            'id' => new external_value(core_user::get_property_type('id'), 'ID of the user'),
            // General.
            'username' => new external_value(core_user::get_property_type('username'),
                'Username policy is defined in Moodle security config.', VALUE_OPTIONAL, '', NULL_NOT_ALLOWED),
            'auth' => new external_value(core_user::get_property_type('auth'), 'Auth plugins include manual, ldap, etc',
                VALUE_OPTIONAL, '', NULL_NOT_ALLOWED),
            'suspended' => new external_value(core_user::get_property_type('suspended'),
                'Suspend user account, either false to enable user login or true to disable it', VALUE_OPTIONAL),
            'firstname' => new external_value(core_user::get_property_type('firstname'), 'The first name(s) of the user',
                VALUE_OPTIONAL, '', NULL_NOT_ALLOWED),
            'lastname' => new external_value(core_user::get_property_type('lastname'), 'The family name of the user',
                VALUE_OPTIONAL),
            'email' => new external_value(core_user::get_property_type('email'), 'A valid and unique email address', VALUE_OPTIONAL,
                '', NULL_NOT_ALLOWED),
            'maildisplay' => new external_value(core_user::get_property_type('maildisplay'), 'Email display', VALUE_OPTIONAL),
            'city' => new external_value(core_user::get_property_type('city'), 'Home city of the user', VALUE_OPTIONAL),
            'country' => new external_value(core_user::get_property_type('country'),
                'Home country code of the user, such as AU or CZ', VALUE_OPTIONAL),
            'timezone' => new external_value(core_user::get_property_type('timezone'),
                'Timezone code such as Australia/Perth, or 99 for default', VALUE_OPTIONAL),
            'description' => new external_value(core_user::get_property_type('description'), 'User profile description, no HTML',
                VALUE_OPTIONAL),
            // User picture.
            'userpicture' => new external_value(PARAM_INT,
                'The itemid where the new user picture has been uploaded to, 0 to delete', VALUE_OPTIONAL),
            // Additional names.
            'firstnamephonetic' => new external_value(core_user::get_property_type('firstnamephonetic'),
                'The first name(s) phonetically of the user', VALUE_OPTIONAL),
            'lastnamephonetic' => new external_value(core_user::get_property_type('lastnamephonetic'),
                'The family name phonetically of the user', VALUE_OPTIONAL),
            'middlename' => new external_value(core_user::get_property_type('middlename'), 'The middle name of the user',
                VALUE_OPTIONAL),
            'alternatename' => new external_value(core_user::get_property_type('alternatename'), 'The alternate name of the user',
                VALUE_OPTIONAL),
            // Interests.
            'interests' => new external_value(PARAM_TEXT, 'User interests (separated by commas)', VALUE_OPTIONAL),
            // Optional.
            'idnumber' => new external_value(core_user::get_property_type('idnumber'),
                'An arbitrary ID code number perhaps from the institution', VALUE_OPTIONAL),
            'institution' => new external_value(core_user::get_property_type('institution'), 'Institution', VALUE_OPTIONAL),
            'department' => new external_value(core_user::get_property_type('department'), 'Department', VALUE_OPTIONAL),
            'phone1' => new external_value(core_user::get_property_type('phone1'), 'Phone', VALUE_OPTIONAL),
            'phone2' => new external_value(core_user::get_property_type('phone2'), 'Mobile phone', VALUE_OPTIONAL),
            'address' => new external_value(core_user::get_property_type('address'), 'Postal address', VALUE_OPTIONAL),
            // Other user preferences stored in the user table.
            'lang' => new external_value(core_user::get_property_type('lang'), 'Language code such as "en", must exist on server',
                VALUE_OPTIONAL, '', NULL_NOT_ALLOWED),
            'calendartype' => new external_value(core_user::get_property_type('calendartype'),
                'Calendar type such as "gregorian", must exist on server', VALUE_OPTIONAL, '', NULL_NOT_ALLOWED),
            'theme' => new external_value(core_user::get_property_type('theme'),
                'Theme name such as "standard", must exist on server', VALUE_OPTIONAL),
            'mailformat' => new external_value(core_user::get_property_type('mailformat'),
                'Mail format code is 0 for plain text, 1 for HTML etc', VALUE_OPTIONAL),
            // Custom user profile fields.
            'customfields' => new external_multiple_structure(
                new external_single_structure(
                    [
                        'type' => new external_value(PARAM_ALPHANUMEXT, 'The name of the custom field'),
                        'value' => new external_value(PARAM_RAW, 'The value of the custom field'),
                    ]
                ), 'User custom fields (also known as user profil fields)', VALUE_OPTIONAL),
            // User preferences.
            'preferences' => new external_multiple_structure(
                new external_single_structure(
                    [
                        'type' => new external_value(PARAM_RAW, 'The name of the preference'),
                        'value' => new external_value(PARAM_RAW, 'The value of the preference'),
                    ]
                ), 'User preferences', VALUE_OPTIONAL),
        ];
        return new external_function_parameters(
            [
                'userdata' => new external_single_structure($userfields),
            ]
        );
    }

    /**
     * Update users
     *
     * @param \stdClass $userdata
     * @return null
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     * @throws \moodle_exception
     * @throws \required_capability_exception
     * @throws \restricted_context_exception
     * @throws invalid_parameter_exception
     * @since Moodle 2.2
     */
    public static function update_user($userdata) {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . "/user/lib.php");
        require_once($CFG->dirroot . "/user/profile/lib.php"); // Required for customfields related function.
        require_once($CFG->dirroot . '/user/editlib.php');

        self::validate_parameters(self::update_user_parameters(), compact( 'userdata'));

        // Ensure the current user is allowed to run this function.
        $context = context_system::instance();
        require_capability('moodle/user:editownprofile', $context);
        $userid = $userdata['id'];
        if ($userid != $USER->id || isguestuser($userid)) {
            throw new invalid_parameter_exception('User can only edit own profile.');
        }
        self::validate_context($context);

        $transaction = $DB->start_delegated_transaction();
        // First check the user exists.
        if (!$existinguser = core_user::get_user($userid)) {
            throw new invalid_parameter_exception('User does not exist.');
        }

        // Check duplicated emails.
        if (isset($userdata['email']) && $userdata['email'] !== $existinguser->email) {
            if (!validate_email($userdata['email'])) {
                throw new invalid_parameter_exception('Cannot validate email.');
            } else if (empty($CFG->allowaccountssameemail)) {
                // Make a case-insensitive query for the given email address and make sure to exclude the user being updated.
                $select = $DB->sql_equal('email', ':email', false) . ' AND mnethostid = :mnethostid AND id <> :userid';
                $params = [
                    'email' => $userdata['email'],
                    'mnethostid' => $CFG->mnet_localhost_id,
                    'userid' => $userdata['id'],
                ];
                // Skip if there are other user(s) that already have the same email.
                if ($DB->record_exists_select('user', $select, $params)) {
                    throw new invalid_parameter_exception('A user with this email already exists.');
                }
            }
        }

        // Make sure we do not update fields which are marked as non modifiable.
        $nonmodfieldsetting = get_config('filter_envf', 'disabled_profile_fields');
        $disabledfields = [];
        if ($nonmodfieldsetting) {
            $disabledfields = explode(',', $nonmodfieldsetting);
        }
        foreach ($disabledfields as $field) {
            if (isset($existinguser->$field)) {
                $userdata[$field] = $existinguser->$field;
            }
        }
        user_update_user($userdata, false, true);

        // Update user custom fields.
        if (!empty($userdata['customfields']) &&!empty($disabledfields['customfields'])) {

            foreach ($userdata['customfields'] as $customfield) {
                // Profile_save_data() saves profile file it's expecting a user with the correct id,
                // and custom field to be named profile_field_"shortname".
                $userdata["profile_field_" . $customfield['type']] = $customfield['value'];
            }
            profile_save_data((object) $userdata);
        }

        // Trigger event.
        \core\event\user_updated::create_from_userid($userdata['id'])->trigger();

        // Preferences.
        if (!empty($userdata['preferences']) &&!empty($disabledfields['preferences'])) {
            $userpref = clone($existinguser);
            foreach ($userdata['preferences'] as $preference) {
                $userpref->{'preference_' . $preference['type']} = $preference['value'];
            }
            useredit_update_user_preference($userpref);
        }

        $transaction->allow_commit();

        return null;
    }

    /**
     * Returns description of method result value
     *
     * @return null
     * @since Moodle 2.2
     */
    public static function update_user_returns() {
        return null;
    }

}
