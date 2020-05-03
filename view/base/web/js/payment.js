require(['jquery','mage/translate'], function ($) {
    function onResize() {
        if ($(window).width() <= 640) {
            $('.payMethodList').addClass('mobile');
        } else {
            $('.payMethodList').removeClass('mobile');
        }
    }

    onResize();
    $(window).resize(function () {
        onResize();
    });

    function getBankName(id) {
        return JSON.parse($('#p24bankNames').val())[parseInt(id)];
    }

    function setP24method(method) {
        method = parseInt(method);
        $('input[name="payment[method_id]"]').val(method > 0 ? method : "");
        $('input[name="payment[method_name]"]').val(method > 0 ? getBankName(method) : "");
    }

    function setP24recurringId(id, name) {
        id = parseInt(id);
        if (name == undefined) name = $('[data-cc=' + id + '] .bank-name').text().trim() + ' - ' + $('[data-cc=' + id + '] .bank-logo span').text().trim();
        $('input[name="payment[cc_id]"]').val(id > 0 ? id : "");
        $('input[name="payment[cc_name]"]').val(id > 0 ? name : "");
        if (id > 0) setP24method(0);
    }

    $('.bank-box').click(function () {
        $('.bank-box').removeClass('selected').addClass('inactive');
        $(this).addClass('selected').removeClass('inactive');
        setP24method($(this).attr('data-id'));
        setP24recurringId($(this).attr('data-cc'));
    });

    $('.bank-item input').change(function () {
        setP24method($(this).attr('data-id'));
        setP24recurringId($(this).attr('data-cc'), $(this).attr('data-text'));
    });

    $('.dialcomPrzelewyFakeMethodSubmit').on("click", function (event) {
        event.preventDefault();
        $('#dialcom_przelewy').click();
        var postfix = $(this).attr('data-postfix');
        var fakeAccept = $('#p24_accept_regulations' + postfix).prop('checked');
        var varForget = $('#p24_forget' + postfix).prop('checked');
        $('#p24_accept_regulations').prop('checked', fakeAccept);
        $('#p24_forget').prop('checked', varForget);
        $('#dialcom_przelewy_submit').click();
    });

    $(document).ready(function () {
        $('[name=payment_method_id]:checked').trigger("change");
        var more = $.mage.__('More payment methods');
        $('head').append("<style>.moreStuff:before{content: '\\f078 "+ more +" \\f078' !important;}</style>");
    });
});