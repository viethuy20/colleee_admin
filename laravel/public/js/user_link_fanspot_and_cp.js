$(document).ready(function ($) {

    $(function(){
        const btnClass = 'ui-priority-secondary';
        const btnAddArea = '.ui-datepicker-buttonpane';

        $('#start_at').datepicker({
            selectOtherMonths:true,
            showOtherMonths:true,
            showButtonPanel: true,
            dateFormat: 'yy-mm-dd',
            beforeShow : function(input) {
                setTimeout(function() {
                    var buttonPane = $(input).datepicker('widget').find(btnAddArea);
                    $('<button>', {text:'クリア',class:btnClass, click: function() {
                            $.datepicker._clearDate(input);
                        }}).appendTo(buttonPane);
                }, 1 );
            },
            onChangeMonthYear: function(year, month, instance) {
                setTimeout(function() {
                    var buttonPane = $(instance).datepicker('widget').find(btnAddArea);
                    $('<button>', {text:'クリア',class:btnClass, click: function() {
                            $.datepicker._clearDate(instance.input);
                        }}).appendTo(buttonPane);
                }, 1 );
            }
        });

        $('#end_at').datepicker({
            selectOtherMonths:true,
            showOtherMonths:true,
            showButtonPanel: true,
            dateFormat: 'yy-mm-dd',
            beforeShow : function(input) {
                setTimeout(function() {
                    var buttonPane = $(input).datepicker('widget').find(btnAddArea);
                    $('<button>', {text:'クリア',class:btnClass, click: function() {
                            $.datepicker._clearDate(input);
                        }}).appendTo(buttonPane);
                }, 1 );
            },
            onChangeMonthYear: function(year, month, instance) {
                setTimeout(function() {
                    var buttonPane = $(instance).datepicker('widget').find(btnAddArea);
                    $('<button>', {text:'クリア',class:btnClass, click: function() {
                            $.datepicker._clearDate(instance.input);
                        }}).appendTo(buttonPane);
                }, 1 );
            }
        });

    });

    $(document).on('click', "button.ui-datepicker-current", function() {
        $.datepicker._curInst.input.datepicker('setDate', new Date())
    });
    $('#openCalendarStartDate').click(function(){
        $('#start_at').datepicker('show');
    })

    $('#openCalendarEndDate').click(function(){
        $('#end_at').datepicker('show');
    })

    $('#btn_export').click(function(){
        $('.content').find('.message-box').remove();
    })
})
