M.block_user_manager_group_info = {
    init: function (Y, URL) {
        $(document).ready(function () {
            var groupSelector = '#id_group';
            var autoCompleteGroupSelector = '.um-autocomplete-group';
            var insertAfterElementId = '.form-autocomplete-selection';
            var oldVal = $(groupSelector).val();

            // Добавления элемента для вывода сообщения
            var msgId = 'umGroupInfo';
            var msgSelector = '#' + msgId;

            var msgContentId = 'umMessageContent';
            var msgContentSelector = '#' + msgContentId;

            var msgToogleId = 'umMessageToggle';
            var msgToogleSelector = '#' + msgToogleId;

            var showClass = 'um-message-show';

            var btnText = M.util.get_string('groupinfo', 'block_user_manager');

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

            var firstAppear = true;

            setInterval(function () {
                if (!Array.isArray($(groupSelector).val())) {
                    // Добавления элемента для вывода сообщения
                    if ($(autoCompleteGroupSelector).length && !$(msgSelector).length) {
                        var msgParent = $(autoCompleteGroupSelector + ' ' + insertAfterElementId);

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
                        var curVal = $(groupSelector).val();

                        if (curVal && oldVal) {
                            if (firstAppear) {
                                var data = {'group': curVal};
                                postRequest(URL, data);
                                firstAppear = false;
                            }

                            if (curVal !== oldVal) {
                                var data = {'group': curVal};
                                postRequest(URL, data)
                                oldVal = curVal;
                            }
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

            function postRequest(url, data) {
                $.post(url, data)
                    .done(function(response) {
                        printMessage(response.data, 'alert alert-info um-message__content', msgContentSelector);
                    })
                    .fail(function(error) {
                        if (error.responseJSON?.data) {
                            console.log(error.responseJSON.data);
                            printMessage(error.responseJSON.data, 'alert alert-danger um-message__content', msgContentSelector);
                        }
                        else {
                            console.log(error.statusText);
                            printMessage(error.statusText, 'alert alert-danger um-message__content', msgContentSelector);
                        }
                    })
            }
        });
    }
}