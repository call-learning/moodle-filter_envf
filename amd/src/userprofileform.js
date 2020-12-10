/**
 * Add the form to the page
 *
 * @module      filter_envf/userprofileform.js
 * @package     filter_envf
 * @copyright   CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery',
        'core/str',
        'core/fragment',
        'core/templates',
        'core/ajax'],
    function ($,
              Str,
              Fragment,
              Templates,
              Ajax,
    ) {
        var UserForm = function (formelement, contextid, userid) {
            this.formelementcontainer = formelement;
            this.contextid = contextid;
            this.userid = userid;
            formelement.on('submit', 'form', this.submitFormAjax.bind(this));
        };

        var validUserFields = [
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
        /**
         * Private method
         *
         * @method submitFormAjax
         * @private
         * @param {Event} e Form submission event.
         */
        UserForm.prototype.submitFormAjax = function (e) {
            // We don't want to do a real form submission.
            e.preventDefault();

            var changeEvent = document.createEvent('HTMLEvents');
            changeEvent.initEvent('change', true, true);

            // Prompt all inputs to run their validation functions.
            // Normally this would happen when the form is submitted, but
            // since we aren't submitting the form normally we need to run client side
            // validation.
            var formelement = $(this.formelementcontainer).find('form');
            formelement.find(':input').each(function (index, element) {
                element.dispatchEvent(changeEvent);
            });

            // Now the change events have run, see if there are any "invalid" form fields.
            var invalid = $.merge(
                formelement.find('[aria-invalid="true"]'),
                formelement.find('.error')
            );

            // If we found invalid fields, focus on the first one and do not submit via ajax.
            if (invalid.length) {
                invalid.first().focus();
                return;
            }
            var formelementcontainer = this.formelementcontainer;
            var formData = JSON.stringify(formelement.serialize());
            var params = {
                'jsonformdata': formData
            };
            var userform = this;
            // Reload the form to check for errors.
            Fragment.loadFragment('filter_envf', 'userprofile_form',
                this.contextid,
                params)
                .then(function (form, js) {
                    Templates.replaceNodeContents(formelementcontainer, form, js);
                    if (formelementcontainer.children('.upf-userformvalidated').data('validated')) {
                        var formDataCombined = $(formelementcontainer.find('form'))
                            .serializeArray()
                            .reduce(function (acc, item) {
                                if (validUserFields.includes(item.name)) {
                                    acc[item.name] = item.value;
                                }
                                return acc;
                            }, {});
                        if (typeof formDataCombined['newpassword'] !== undefined)  {
                            delete formDataCombined['newpassword'];
                        }
                        if (typeof formDataCombined['password'] !== undefined)  {
                            delete formDataCombined['password'];
                        }
                        // Now we can continue and send the data...
                        Ajax.call([{
                            methodname: 'filter_envf_update_user',
                            args: {userdata: formDataCombined},
                            done: userform.handleFormSubmissionResponse.bind(userform, formDataCombined),
                            fail: userform.handleFormSubmissionFailure.bind(userform, formDataCombined)
                        }]);
                    }
                });
            // Convert all the form elements values to a serialised string.

        };
        /**
         * Private method
         *
         * @method handleFormSubmissionResponse
         * @private
         * @param {Event} e Form submission success.
         */
        UserForm.prototype.handleFormSubmissionResponse = function () {

        };
        /**
         * Private method
         *
         * @method handleFormSubmissionFailure
         * @private
         * @param {Event} e Form submission failure.
         */
        UserForm.prototype.handleFormSubmissionFailure = function () {


        };
        return {
            init: function (args) {
                $(document).ready(function () {
                    $('.' + args.classmarker).each(function () {
                        var userid = $(this).data('userid');
                        var contextid = $(this).data('contextid');
                        var params = {};
                        var currentform = $(this);
                        Fragment.loadFragment('filter_envf', 'userprofile_form', contextid, params)
                            .then(function (form, js) {
                                Templates.replaceNodeContents(currentform, form, js);
                                new UserForm(currentform, contextid, userid); // Bind the form.
                            });
                    });
                });
            }
        };
    }
);