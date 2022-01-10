M.block_user_manager_valid_fields_table = {
    init: function (Y, URL, OPTIONS, HELPERS) {
        $(document).ready(function () {
            // URL = (string)(new moodle_url('/blocks/user_manager/uploaduser/update_userfields.php'));
            // OPTIONS = STD_FIELDS_EN
            // HELPERS = STD_FIELDS_RU

            const delBtnClass = 'um-validfields-btn-del';

            const addBtnId = 'umValidFieldsBtnAdd';
            const saveBtnId = 'umValidFieldsBtnSave';

            const cellClass = 'um-table__cell';
            const rowClass = 'um-table__row';

            const textClass = 'um-validfields-text';
            const textId = 'umValidFieldsText';
            const selectClass = 'um-validfields-select';
            const selectorId = 'umValidFieldsSelector';

            const btnDelClass = 'um-validfields-btn-del';
            const btnDelId = 'umValidFieldsBtnDel';

            const options = OPTIONS;
            const helpers = HELPERS;

            function generateSelector(options, helpers = [], defaultempty = false) {
                var newSelector = $(document.createElement('select'));
                if (defaultempty) {
                    var newOption = $(document.createElement('option'));
                    newOption.attr({
                        selected: true
                    });
                    newOption.text('');
                    newSelector.append(newOption);
                }

                $.each(options, function(key, value) {
                    var helper = ([...helpers.keys()].indexOf(key) !== -1)? ' (' + helpers[key] + ')': '';
                    var newOption = $(document.createElement('option'));
                    newOption.attr({
                        value: value
                    });
                    newOption.text(value + helper);
                    newSelector.append(newOption);
                });

                return newSelector;
            }

            $('#'+addBtnId).click(function() {
                var rowEl = $(document.createElement('tr'));
                var cellSelectEl = $(document.createElement('td'));
                var cellTextEl = $(document.createElement('td'));
                var cellBtnDelEl = $(document.createElement('td'));

                var selectEl = generateSelector(options, helpers, true);
                var textareaEl = $(document.createElement('textarea'));
                var btnDelEl = $(document.createElement('button'));

                var textareaInfoEl = $(document.createElement('div'));
                textareaInfoEl.addClass('um-input-info');
                textareaInfoEl.text(M.util.get_string('inputdelimiter', 'block_user_manager'));

                var allRows = $('#umValidFieldsTable tbody tr').get();
                allRows.pop();

                var id = 0;
                if (allRows[allRows.length - 1] !== undefined) {
                    var prevSelectEl = $(allRows[allRows.length - 1]).children('.' + cellClass + ':first-child').children().get()[0] ;
                    id = +uniqId(prevSelectEl) + 1;
                }

                rowEl.addClass(rowClass);
                cellSelectEl.addClass(cellClass);
                cellTextEl.addClass(cellClass);

                selectEl.addClass(selectClass + ' custom-select');
                selectEl.attr({
                    name: selectorId + '_' + id,
                    id: selectorId + '_' + id
                });

                textareaEl.addClass(textClass);
                textareaEl.attr({
                    name: textId + '_' + id,
                    id: textId + '_' + id,
                    rows: 2,
                    cols: 60
                });

                btnDelEl.addClass(btnDelClass + ' btn btn-danger');
                btnDelEl.attr({
                    type: 'button',
                    id: btnDelId + '_' + id
                });

                btnDelEl.text('x');

                cellSelectEl.append(selectEl);
                cellTextEl.append(textareaEl);
                cellTextEl.append(textareaInfoEl);
                cellBtnDelEl.append(btnDelEl);
                rowEl.append(cellSelectEl, cellTextEl, cellBtnDelEl);

                $(rowEl).insertBefore($(this).parent().parent());

                bindDelBtns();
            });

            function uniqId(el) {
                var underpos = -1;

                if (el.id)
                    underpos = el.id.indexOf('_');

                if (underpos != -1)
                    return el.id.slice(underpos + 1);

                return underpos;
            }

            function bindDelBtns() {
                $('.'+delBtnClass).click(function() {
                    $(this).parent().parent().remove();
                });
            };

            bindDelBtns();

            $('#'+saveBtnId).click(function() {
                // соотвествие 1 к 1
                var selectedvalues = getFieldsValues('.'+selectClass);
                var textvalues = getFieldsValues('.'+textClass);
                textvalues = formatTextValues(textvalues);

                /*selectedvalues = selectedvalues.filter(val => val.length);
                textvalues = textvalues.filter(val => val.length);*/

                function getFieldsValues(identifier) {
                    return $(identifier).map(function() {
                        return $(this).val();
                    }).get();
                }

                function printMessage(message, messageClass, id) {
                    var messageEl = $('#'+id);
                    messageEl.removeClass();
                    messageEl.css({textAlign: 'center'});
                    messageEl.addClass(messageClass);
                    messageEl.text(message);
                    setTimeout(function() {
                        messageEl.empty();
                        messageEl.removeClass();
                    }, 2000)
                }

                function formatTextValues(textvalues) {
                    return textvalues.map((item, i) => {
                        return item.split(',').map((val) => val.trim().toLowerCase());
                    });
                }

                var data = {
                    'systemfields': ''
                };

                if (selectedvalues.length) {
                    data = {
                        'systemfields[]': selectedvalues,
                        'associatedfields[]': textvalues
                    }
                }

                $.post(URL, data)
            .done(function(response) {
                    console.log(response.data);
                    printMessage(response.data, 'alert alert-success', 'umValidFieldsMessage');
                })
                    .fail(function(error) {
                        if (error.responseJSON?.data) {
                            console.log(error.responseJSON.data);
                            printMessage(error.responseJSON.data, 'alert alert-danger', 'umValidFieldsMessage');
                        }
                        else console.log(error.statusText);
                    })
            });
        });
    }
}