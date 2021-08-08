require(['jquery', 'mage/translate'], function ($, mage) {
    function getBanksList() {
        var banksList = [];
        $('#przelewy_settings_paymethods_paymethods_all option').each(function () {
            if (parseInt($(this).val()) > 0) {
                banksList.push({id: $(this).val(), name: $(this).text()});
            }
        });
        if (banksList.length == 0) {
            banksList.push({id: 25, name: ""});
            banksList.push({id: 31, name: ""});
            banksList.push({id: 112, name: ""});
            banksList.push({id: 20, name: ""});
            banksList.push({id: 65, name: ""});
        }
        return banksList;
    }

    function getBankBox(id, name) {
        if (name == undefined) name = '';
        return '<a class="bank-box" data-id="' + id + '"><div class="bank-logo bank-logo-' + id + '"></div><div class="bank-name">' + name + '</div></a>';
    }

    function toggleSomething(toggle, selector) {
        if (toggle) {
            $(selector).show();
        } else {
            $(selector).hide();
        }
    }

    function updatePaymethods() {
        $('.bank-box').removeClass('ui-helper-unrotate');
        var maxNo = parseInt($('.paymethod .selected').attr('data-max'));
        if (maxNo > 0) {
            if ($('.paymethod .selected a[data-id]').length > maxNo) {
                var i = 0;
                $('.paymethod .selected a[data-id]').each(function () {
                    i++;
                    if (i > maxNo) {
                        $('.paymethod .available')
                            .prepend($(this))
                            .append($('#clear'));
                    }
                });
            }
        }
        $('#przelewy_settings_paymethods_paymethod_first').val('');
        $('.paymethod .selected a[data-id]').each(function () {
            $('#przelewy_settings_paymethods_paymethod_first').val(
                $('#przelewy_settings_paymethods_paymethod_first').val() +
                ($('#przelewy_settings_paymethods_paymethod_first').val().length ? ',' : '') +
                $(this).attr('data-id')
            );
        });
        $('#przelewy_settings_paymethods_paymethod_second').val('');
        $('.paymethod .available a[data-id]').each(function () {
            $('#przelewy_settings_paymethods_paymethod_second').val(
                $('#przelewy_settings_paymethods_paymethod_second').val() +
                ($('#przelewy_settings_paymethods_paymethod_second').val().length ? ',' : '') +
                $(this).attr('data-id')
            );
        });
    }

    function updatePaymethodPromoted() {
        var paymethod_promoted = [];
        $('.paylistprom:checked').each(function () {
            paymethod_promoted.push($(this).attr('data-val'));
        });
        $('#przelewy_settings_promoted_paymethod_promoted').val(paymethod_promoted.join(','));
    }

    function multicurrReadField(name) {

        var val = $('#przelewy_settings_multicurr_multicurr_' + name).val();
        if (val.length) {
            var vals = val.split(',');
            $.each(vals, function(index, item) {
                var props = item.split(':');
                if (props.length == 2) {
                    $('#' + props[0] + '_' + name).val(props[1]);
                }
            });
        }
    }

    function multicurrRead() {
        multicurrReadField('merchantid');
        multicurrReadField('shopid');
        multicurrReadField('salt');
        multicurrReadField('api');
    }

    function multicurrWriteField(name) {
        var newValArr = [];
        $('select#przelewy_settings_multicurr_multicurr_list option').each(function(){
            var key = $(this).val();
            newValArr.push(key + ':' + $('#' + key + '_' + name).val());
        });
        $('#przelewy_settings_multicurr_multicurr_' + name).val(newValArr.join(','));
    }

    function multicurrWrite() {
        multicurrWriteField('merchantid');
        multicurrWriteField('shopid');
        multicurrWriteField('salt');
        multicurrWriteField('api');
    }

    $(document).ready(function () {
        if ($('fieldset#przelewy_settings_paymethods').length) {

            $( "#przelewy_settings_paymethods_paymethod_first_inherit" ).on( "click", function() {
                $('#przelewy_settings_paymethods_paymethod_second_inherit').click();
            });
            // kolejność metod płatności
            $('tr#row_przelewy_settings_paymethods_paymethods_all').hide();
            $('#przelewy_settings_paymethods').append(
                '<style>' +
                '.bank-placeholder { opacity: 0.6; } ' +
                '.sortable.available .bank-box:last-child { clear: both; } ' +
                '.sortable.selected::before { content: "' + $.mage.__('Drop here max 5 payment methods') + '"; color: gray; position: absolute; text-align: center; vertical-align: middle; margin: 3em 0 0 22em; } ' +
                '.sortable.selected .bank-box { position: relative; z-index: 2; } ' +

                '.bank-box.ui-sortable-helper { transform: rotate(10deg); box-shadow: 10px 10px 10px lightgray; } ' +
                '.ui-helper-unrotate { transform: rotate(0deg) !important; box-shadow: 0 0 0 lightgray !important; } ' +
                '.bank-box { transition: transform 0.2s ease, box-shadow 0.2s ease; } ' +

                'a.bank-box { text-decoration: none; } ' +
                '.paylistprom_item { cursor: pointer; display: block !important; background: white; background: rgba(255,255,255,0.8); font-weight: normal !important; } ' +
                '</style>' +

                '<div class="paymethod">' +
                $.mage.__('Payment methods visible on the first view:')+'<br>' +
                '<div class="sortable selected" data-max="5" style="width: 730px; border: 5px dashed lightgray; height: 80px; padding: 0.5em; overflow: hidden;"></div>' +
                '<div style="clear:both"></div> '+$.mage.__('Payment methods visible after click the button (more...):')+' <br>' +
                '<div class="sortable available"></div>' +
                '</div>' +
                ''
            );
            $('#przelewy_settings_paymethods_paymethod_first, #przelewy_settings_paymethods_paymethod_second, #przelewy_settings_promoted_paymethod_promoted').removeAttr('disabled');

            var disabled = '';
            if($('#przelewy_settings_promoted_paymethod_promoted_inherit').is(':checked'))
            {
                disabled = 'disabled';
            }
            $('#przelewy_settings_promoted_paymethod_promoted, #przelewy_settings_paymethods_paymethod_first, #przelewy_settings_paymethods_paymethod_second').prop('type', 'hidden');
            $('#row_przelewy_settings_promoted_paymethod_promoted .label label, #row_przelewy_settings_paymethods_paymethod_first .label label, #row_przelewy_settings_paymethods_paymethod_second .label label, #row_przelewy_settings_paymethods_paymethod_second .use-default').css("display", "none");

            $('#row_przelewy_settings_promoted_paymethod_promoted .value').append('<div class="promoted " id="paymethod_promote_list"></div>');
            $.each(getBanksList(), function () {
                $('#paymethod_promote_list').append(
                    '<label class="paylistprom_item paylistprom_item_' + this.id + '" ' +
                    'for="paylistprom_' + this.id + '"><span ' +
                    'style="cursor: ns-resize; display: inline-block" class="ui-icon ui-icon-grip-dotted-vertical"></span><input ' + disabled + ' ' +
                    'class="paylistprom" id="paylistprom_' + this.id + '" type="checkbox" data-val="' + this.id + '" style="position:relative; top: -4px"> <span ' +
                    'style="position:relative; top: -2px">' + this.name + '</span></label>' +
                    ''
                );
            });
            require(['jquery', 'jquery/ui'], function ($) {
                $('#paymethod_promote_list')
                    .sortable({
                        stop: function () {
                            updatePaymethodPromoted();
                        }, axis: 'y',
                    })
                    .disableSelection()
            });
            $('.paylistprom').change(function () {
                updatePaymethodPromoted();
            });

            $.each($('#przelewy_settings_promoted_paymethod_promoted').val().split(',').reverse(), function () {
                $('.paylistprom_item_' + this.toString()).prependTo('#paymethod_promote_list').find('input').attr('checked', true);
            });

            $('#row_payment_us_dialcom_przelewy_active').change(function () {
                toggleSomething($('#przelewy_settings_promoted_show_promoted').val() == '1', '.promoted');
                toggleSomething($('#przelewy_settings_promoted_show_promoted').val() == '1', '#row_przelewy_settings_promoted_paymethod_promoted .use-default');
            });


            $('#przelewy_settings_paymethods_showpaymethods,#przelewy_settings_promoted_show_promoted').trigger('change');


            toggleSomething($('#przelewy_settings_paymethods_showpaymethods').val() == '1', '.paymethod');
            toggleSomething($('#przelewy_settings_paymethods_showpaymethods').val() == '1', '#row_przelewy_settings_paymethods_paymethod_first .use-default');

            $('#przelewy_settings_paymethods_showpaymethods').change(function () {
                toggleSomething($('#przelewy_settings_paymethods_showpaymethods').val() == '1', '.paymethod');
                toggleSomething($('#przelewy_settings_paymethods_showpaymethods').val() == '1', '#row_przelewy_settings_paymethods_paymethod_first .use-default');
            });

            $.each(getBanksList(), function () {
                $('.sortable.available').append(getBankBox(this.id, this.name));
            });

            $('.sortable.available').append('<div style="clear:both" id="clear"></div>');

            if ($('#przelewy_settings_paymethods_paymethod_first').val().length > 0) {
                $.each($('#przelewy_settings_paymethods_paymethod_first').val().split(','), function (i, v) {
                    $('.bank-box[data-id=' + v + ']').appendTo('.paymethod .selected');
                });
            }
            if ($('#przelewy_settings_paymethods_paymethod_second').val().length > 0) {
                $.each($('#przelewy_settings_paymethods_paymethod_second').val().split(',').reverse(), function (i, v) {
                    $('.bank-box[data-id=' + v + ']').prependTo('.paymethod .available');
                });
            }
            updatePaymethods();

            require(['jquery', 'jquery/ui'], function ($) {
                $(".sortable.selected,.sortable.available").sortable({
                    connectWith: ".sortable.selected,.sortable.available",
                    placeholder: "bank-box bank-placeholder",
                    stop: function () {
                        updatePaymethods();
                    },
                    revert: true,
                    start: function (e, ui) {
                        window.setTimeout(function () {
                            $('.bank-box.ui-sortable-helper').on('mouseup', function () {
                                $(this).addClass('ui-helper-unrotate');
                            });
                        }, 100);
                    },
                }).disableSelection();
            });
        }

        if ($('fieldset#przelewy_settings_multicurr').length) {
            // subkonta walutowe
            if ($('select#przelewy_settings_multicurr_multicurr_list option').length == 0) {
                $('fieldset#przelewy_settings_multicurr').closest('.section-config').hide();
            } else {
                $('fieldset#przelewy_settings_multicurr > table').hide();
                $('select#przelewy_settings_multicurr_multicurr_list option').each(function(){
                    var key = $(this).val();
                    var name = $(this).text();
                    $('fieldset#przelewy_settings_multicurr').append('<div><h3>'+key+'</h3><table class="form-list multicurr" data-curr="'+key+'">'+
                        '<tr><td class="label">' + $.mage.__('Merchant ID:') + '</td><td class="value"><input type="text" class="input-text" id="'+key+'_merchantid" onkeyup="multicurrWrite();"></td><td></td></tr>'+
                        '<tr><td class="label">' + $.mage.__('Shop ID:') + '</td><td class="value"><input type="text" class="input-text" id="'+key+'_shopid" onkeyup="multicurrWrite();"></td><td></td></tr>'+
                        '<tr><td class="label">' + $.mage.__('CRC key:') + '</td><td class="value"><input type="text" class="input-text" id="'+key+'_salt" onkeyup="multicurrWrite();"></td><td></td></tr>'+
                        '<tr><td class="label">' + $.mage.__('API key:') + '</td><td class="value"><input type="text" class="input-text" id="'+key+'_api" onkeyup="multicurrWrite();"></td><td></td></tr>'+
                        '</table></div>');
                });
                multicurrRead();
                $('table[data-curr] input')
                    .on('keyup', function() { multicurrWrite(); })
                    .on('change', function() { multicurrWrite(); })
                ;
                $('form#config-edit-form').on('submit', function() { multicurrWrite(); });
            }
        }

        if(window.location.pathname.indexOf("checkout/onepage/success/ga_order_id") > 0){
            $('.checkout-success > p:nth-child(1)').toggle();
            var split = window.location.pathname.split('/');
            var orderId = split[split.length - 2];
            var action = split[split.length - 3];
            if($.isNumeric(orderId)) {
                if ($('.checkout-success > p:nth-child(1) > span:nth-child(1)').length) {
                    var orderNumber = $('.checkout-success > p:nth-child(1) > span:nth-child(1)').text();
                    if (action == 'ga_order_id' && orderNumber.substr(orderNumber.length - orderId.length) != orderId && orderId > 1) {
                        var newText = orderNumber.substring(0, orderNumber.length - orderId.length);
                        newText += orderId;
                        $('.checkout-success > p:nth-child(1) > span:nth-child(1)').text(newText);
                    }
                } else if ($('.order-number').length) {
                    var orderNumber = $('.order-number').text();
                    if (action == 'ga_order_id' && orderNumber.substr(orderNumber.length - orderId.length) != orderId && orderId > 1) {
                        var newText = orderNumber.substring(0, orderNumber.length - orderId.length);
                        newText += orderId;
                        $('.order-number').text(newText);
                        var oldUrl = $('.order-number').attr('href');
                        var splitedOldUrl = oldUrl.split('/');
                        splitedOldUrl[splitedOldUrl.length - 2] = orderId;
                        var newUrl = splitedOldUrl.join('/');
                        $('.order-number').attr('href', newUrl);
                    }
                }
            }
            $('.checkout-success > p:nth-child(1)').toggle();
        }
    });
});
