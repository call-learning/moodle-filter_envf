/**
 * Add the form to the page
 *
 * @module      filter_envf/userprofileform.js
 * @package     filter_envf
 * @copyright   CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {get_string as getString} from 'core/str';
import Fragment from 'core/fragment';
import Templates from 'core/templates';
import Ajax from 'core/ajax';
import Notification from 'core/notification';

const validUserFields = [
    'id',
    'username',
    'auth',
    'suspended',
    'firstname',
    'lastname',
    'email',
    'maildisplay',
    'city',
    'country',
    'timezone',
    'description',
    'userpicture',
    'firstnamephonetic',
    'lastnamephonetic',
    'middlename',
    'alternatename',
    'interests',
    'url',
    'icq',
    'skype',
    'aim',
    'yahoo',
    'msn',
    'idnumber',
    'institution',
    'department',
    'phone1',
    'phone2',
    'address',
    'lang',
    'calendartype',
    'theme',
    'mailformat',
    'customfields',
    'preferences',
];

class UserForm {
    constructor(formelement, contextid, userid) {
        this.formelementcontainer = formelement;
        this.contextid = contextid;
        this.userid = userid;
        formelement.addEventListener('submit', this.submitFormAjax.bind(this));
    }

    submitFormAjax(e) {
        // We don't want to do a real form submission.
        e.preventDefault();

        let changeEvent = document.createEvent('HTMLEvents');
        changeEvent.initEvent('change', true, true);

        // Prompt all inputs to run their validation functions.
        // Normally this would happen when the form is submitted, but
        // since we aren't submitting the form normally we need to run client side
        // validation.
        let formelement = this.formelementcontainer.querySelector('form');
        formelement.querySelectorAll('input, textarea, select, button')
            .forEach(element => {
                element.dispatchEvent(changeEvent);
                }
            );

        // Now the change events have run, see if there are any "invalid" form fields.
        const invalidElementsAria = [...formelement.querySelectorAll('[aria-invalid="true"]')];
        const invalidElementsError = [...formelement.querySelectorAll('.error')];
        const invalid = [...invalidElementsAria, ...invalidElementsError];

        // If we found invalid fields, focus on the first one and do not submit via ajax.
        if (invalid.length) {
            invalid.first().focus();
            return;
        }
        let formelementcontainer = this.formelementcontainer;
        const formDataObj = new FormData(formelement);
        let formDataAsObject = {};
        for (let [key, value] of formDataObj.entries()) {
            formDataAsObject[key] = value;
        }
        let formData = JSON.stringify(formDataAsObject);
        let params = {
            'jsonformdata': formData
        };
        let userform = this;
        // Reload the form to check for errors.
        Fragment.loadFragment('filter_envf',
            'userprofile_form',
            this.contextid,
            params)
            .then((form, js) => {
                Templates.replaceNodeContents(formelementcontainer, form, js);
                const childElement = formelementcontainer.querySelector('.upf-userformvalidated');
                let validatedData = childElement ? childElement.dataset.validated : false;
                if (validatedData) {
                    const formData = new FormData(formelementcontainer.querySelector('form'));
                    let formDataCombined = {};

                    for (let [key, value] of formData.entries()) {
                        if (validUserFields.includes(key)) {
                            formDataCombined[key] = value;
                        }
                    }
                    if (typeof formDataCombined.newpassword !== undefined) {
                        delete formDataCombined.newpassword;
                    }
                    if (typeof formDataCombined.password !== undefined) {
                        delete formDataCombined.password;
                    }
                    // Now we can continue and send the data...
                    Ajax.call([{
                        methodname: 'filter_envf_update_user',
                        args: {userdata: formDataCombined},
                        done: userform.handleFormSubmissionResponse.bind(userform, formDataCombined),
                        fail: userform.handleFormSubmissionFailure.bind(userform, formDataCombined)
                    }]);
                }
                return validatedData;
            }).catch(Notification.exception);
    }

    handleFormSubmissionResponse(data) {
        getString('userupdated', 'filter_envf', data).done(
            (str) => {
                Notification.addNotification({
                    message: str,
                    type: "info"
                });
            }
        );
    }

    handleFormSubmissionFailure(data) {
        getString('userupdated', 'filter_envf', data).done(
            (str) => {
                Notification.addNotification({
                    message: str,
                    type: "warning"
                });
            }
        );
    }
}

export const init = (args) => {
    const elements = document.querySelectorAll('.' + args.classmarker);

    elements.forEach(element => {
        const userid = element.dataset.userid;
        const contextid = element.dataset.contextid;
        const params = {};

        Fragment.loadFragment('filter_envf', 'userprofile_form', contextid, params)
            .then((form, js) => {
                Templates.replaceNodeContents(element, form, js);
                new UserForm(element, contextid, userid); // Bind the form.
                return true;
            })
            .catch(Notification.exception);
    });
};