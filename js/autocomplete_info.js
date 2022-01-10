M.block_user_manager_autocomplete_info = {
    init: function (
        Y, URL, SELECT_FIELD_ID, DATA_FIELD_NAME = null, CONTEXT = null, DATA = null
    ) {
        $(document).ready(function () {
            const CONTEXTS = {
                SELECT_ACTION: 'select_action',
                SELECT_ACTION_FILE: 'select_action_file',
                UPLOAD_METHOD: 'upload_method',
                COHORT_SYNC: 'cohort_sync'
            };

            // Идентификатор скрытого элемента select в элементе MoodlForm - autocomplete
            var selectFieldId = SELECT_FIELD_ID;
            var selectFieldSelector = '#' + selectFieldId;

            var eduformFieldId =
                (DATA?.EDU_FORM_FIELD_ID && DATA.EDU_FORM_FIELD_ID)? DATA.EDU_FORM_FIELD_ID : '';
            var eduformFieldSelector = '#' + eduformFieldId;

            var autoCompleteClass = 'um-autocomplete';
            var autoCompleteSelector = '.' + autoCompleteClass;

            $(selectFieldSelector).parent().addClass(autoCompleteClass);

            var insertAfterElementSelector = '.form-autocomplete-selection';
            var oldVal = $(selectFieldSelector).val();

            // Добавления элемента для вывода сообщения
            var msgId = 'umAutocompleteInfo_' + selectFieldId;
            var msgSelector = '#' + msgId;

            var msgContentId = 'umMessageContent_' + selectFieldId;
            var msgContentSelector = '#' + msgContentId;

            var msgToogleId = 'umMessageToggle_' + selectFieldId;
            var msgToogleSelector = '#' + msgToogleId;

            var showClass = 'um-message-show';

            var btnText = '';
            var placeholderMsg = '';

            var groupInfo = '';
            var noGroupInfo = '';

            var cohortInfo = '';
            var noCohortInfo = '';
            var selectCohort = '';

            if (CONTEXT === CONTEXTS.SELECT_ACTION || CONTEXT === CONTEXTS.UPLOAD_METHOD) {
                groupInfo = M.util.get_string('groupinfo', 'block_user_manager');
                noGroupInfo = M.util.get_string('nogroupinfo', 'block_user_manager');
                btnText = groupInfo;
            }

            if (CONTEXT === CONTEXTS.COHORT_SYNC) {
                cohortInfo = M.util.get_string('cohortinfo', 'block_user_manager');
                noCohortInfo = M.util.get_string('nocohortinfo', 'block_user_manager');
                selectCohort = M.util.get_string('selectcohort', 'block_user_manager');
                btnText = cohortInfo;
                placeholderMsg = selectCohort;
            }

            var msgEl = $(`
                <div class="um-message um-message-toggleable" id="${msgId}" role="alert">
                    <div class="um-message__toggle">
                        <button class="btn btn-primary um-toggle-btn" type="button" id="${msgToogleId}">
                            ${btnText} <div class="um-toggle-arrow"> ▼ </div>
                        </button>
                    </div>
                    <div class="um-message__content" id="${msgContentId}"></div>
                </div>
            `);

            var msgClass = 'um-message__content alert alert-info';
            var errClass = 'um-message__content alert alert-danger';

            var firstAppear = true;

            setInterval(function () {
                if (!Array.isArray($(selectFieldSelector).val())) {
                    // Добавления элемента для вывода сообщения
                    if ($(autoCompleteSelector).length && !$(msgSelector).length) {
                        var msgParent = $(autoCompleteSelector + ' ' + insertAfterElementSelector);

                        if (msgParent.length > 1) {
                            $(msgEl).insertAfter(msgParent[0]);
                        } else {
                            $(msgEl).insertAfter(msgParent);
                        }

                        $(msgToogleSelector).on('click', function () {
                            if ($(msgSelector).hasClass(showClass)) {
                                $(msgSelector).removeClass(showClass);
                                $(msgContentSelector).css('opacity', 0);
                            }
                            else {
                                $(msgSelector).addClass(showClass);
                                $(msgContentSelector).animate({ opacity: 1 }, 250);
                            }
                        });
                    }

                    if ($(msgSelector).length) {
                        var curVal = $(selectFieldSelector).val();

                        if (!curVal) {
                            if (CONTEXT === CONTEXTS.COHORT_SYNC || CONTEXT === CONTEXTS.SELECT_ACTION_FILE) {
                                printMessage(placeholderMsg, msgClass, msgContentSelector);
                            }
                        }

                        if (curVal && firstAppear) {
                            var data = {[DATA_FIELD_NAME]: curVal};
                            postRequest(URL, data);
                            firstAppear = false;
                            oldVal = curVal;
                        }

                        if (curVal && oldVal && (curVal !== oldVal)) {
                            var data = {[DATA_FIELD_NAME]: curVal};
                            postRequest(URL, data)
                            oldVal = curVal;
                        }
                    }
                }
            }, 100);

            function printMessage(message, messageClass, selector) {
                var messageEl = $(selector);
                messageEl.removeClass();
                messageEl.addClass(messageClass);
                messageEl.html(message);
            }

            // Для формы выбора действий, где присутствует поле выбора формы обучения
            // Очищает select с формами обучения и добавляет значения получение вместе с информацией о группе
            function setEduformsOptions(groupInfo, eduformFieldSelector, eduforms) {
                if (eduforms && Object.keys(eduforms).keys()) {
                    if (({}).hasOwnProperty.call(groupInfo, 'Форма обучения')) {
                        var eduforms1c = groupInfo['Форма обучения'];
                        if (Array.isArray(eduforms1c) && eduforms1c.length) {
                            $(eduformFieldSelector).empty();
                            for (var eduform of eduforms1c) {
                                if (({}).hasOwnProperty.call(eduforms, eduform)) {
                                    $(eduformFieldSelector).append(`
                                    <option value="${eduform}">
                                       ${M.util.get_string(eduforms[eduform], 'block_user_manager')}
                                    </option>
                                `);
                                } else {
                                    $(eduformFieldSelector).append(`<option value="${eduform}">${eduform}</option>`);
                                }
                            }
                        }
                    }
                }
            }

            // Для формы выбора действий, где присутствует поле выбора формы обучения
            // Очищает select с формами обучения и добавляет значения по умолчанию
            function setEduformsDefaultsOptions(eduformFieldSelector, eduforms) {
                if (eduforms && Object.keys(eduforms).keys()) {
                    $(eduformFieldSelector).empty();
                    for (const key in eduforms) {
                        $(eduformFieldSelector).append(`
                        <option value="${key}">
                            ${M.util.get_string(eduforms[key], 'block_user_manager')}
                        </option>
                    `);
                    }
                }
            }

            function postRequest(url, data) {
                // Для формы выбора действий, где присутствует поле выбора формы обучения
                if (CONTEXT === CONTEXTS.SELECT_ACTION) {
                    if (DATA?.FROM && DATA?.UPLOAD_METHOD_FILE && DATA.FROM && DATA.UPLOAD_METHOD_FILE) {
                        if (DATA.FROM === DATA.UPLOAD_METHOD_FILE) {
                            if (DATA?.EDU_FORMS && DATA.EDU_FORMS) {
                                setEduformsDefaultsOptions(eduformFieldSelector, DATA.EDU_FORMS);
                            }
                        }
                    }
                }

                $.post(url, data)
                    .done(function(response) {
                        // ------- Обработка ответа от скрипта get_group_info.php -------
                        if (CONTEXT === CONTEXTS.SELECT_ACTION || CONTEXT === CONTEXTS.UPLOAD_METHOD ||
                            CONTEXT === CONTEXTS.SELECT_ACTION_FILE)
                        {
                            if (response?.data && Object.keys(response.data).length) {
                                if (response.data?.groupInfoStr && response.data.groupInfoStr)
                                    printMessage(response.data.groupInfoStr, msgClass, msgContentSelector);
                                else printMessage(noGroupInfo, errClass, msgContentSelector);

                                // Для формы выбора действий, где присутствует поле выбора формы обучения
                                if (CONTEXT === CONTEXTS.SELECT_ACTION || CONTEXT === CONTEXTS.SELECT_ACTION_FILE) {
                                    if (DATA?.FROM && DATA?.UPLOAD_METHOD_FILE && DATA.FROM && DATA.UPLOAD_METHOD_FILE) {
                                        if (DATA.FROM === DATA.UPLOAD_METHOD_FILE) {
                                            if (response.data?.groupInfo && Object.keys(response.data.groupInfo).length) {
                                                var groupInfo = response.data.groupInfo;
                                                if (DATA?.EDU_FORMS && DATA.EDU_FORMS) {
                                                    setEduformsOptions(groupInfo, eduformFieldSelector, DATA.EDU_FORMS);
                                                }
                                            }
                                        }
                                    }
                                }


                            } else printMessage(noGroupInfo, errClass, msgContentSelector);
                        }
                        // ------- Обработка ответа от скрипта get_group_info.php -------

                        // ------- Обработка ответа от скрипта get_cohort_info.php -------
                        if (CONTEXT === CONTEXTS.COHORT_SYNC) {
                            if (response?.data && response.data) {
                                printMessage(response.data, msgClass, msgContentSelector);
                            } else {
                                printMessage(noCohortInfo, errClass, msgContentSelector);
                            }
                        }
                        // ------- Обработка ответа от скрипта get_cohort_info.php -------
                    })
                    .fail(function(error) {
                        if (error.responseJSON?.data && error.responseJSON.data) {
                            console.log(error.responseJSON.data);
                            printMessage(error.responseJSON.data, errClass, msgContentSelector);
                        }
                        else {
                            console.log(error.statusText);
                            printMessage(error.statusText, errClass, msgContentSelector);
                        }
                    })
            }
        });
    }
}