(function ($) {
    'use strict';

    function formatMilesimos(digits) {
        if (!digits) {
            return '';
        }

        digits = digits.replace(/^0+(?=\d)/, '');

        while (digits.length < 4) {
            digits = '0' + digits;
        }

        var integer = digits.slice(0, -3);
        var decimal = digits.slice(-3);

        integer = integer.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

        return integer + ',' + decimal;
    }

    $.fn.moneyMask = function () {
        return this.each(function () {
            var $input = $(this);
            var apply = function () {
                var digits = String($input.val() || '').replace(/\D/g, '');

                if (digits === '') {
                    $input.val('');
                    return;
                }

                $input.val(formatMilesimos(digits));
            };

            $input.attr('inputmode', 'decimal');

            if (String($input.val() || '').trim() !== '') {
                apply();
            }

            $input.on('input blur', apply);
        });
    };

    $(function () {
        $('[data-money]').moneyMask();
    });
})(jQuery);
