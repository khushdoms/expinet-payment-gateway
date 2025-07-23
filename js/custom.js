var $ = jQuery;

$(document).ready(function(){
        
    $(document).on("mouseenter mouseleave", "#wc-expinet-cc-form .help_info", function(e){
        if(e.type == "mouseenter")
        {
            $( this ).find('p').fadeIn();
        }
        else
        {
            $( this ).find('p').fadeOut();
        }
    });
    jQuery(document.body).on('updated_checkout', function () {
        var cardInput = document.querySelector('#wc-expinet-cc-form input[name="expipaga_cardnumber"]');
        if (cardInput) {
            // Remove non-digit characters before reapplying mask
            VMasker(cardInput).unMask();
            VMasker(cardInput).maskPattern("9999 9999 9999 9999");

            cardInput.addEventListener('input', function () {
                // Basic logic to detect card type (VISA / MasterCard etc.)
                var number = cardInput.value.replace(/\s+/g, '');
                var type = getCardType(number);

                // Update card image UI
                jQuery('.card_thumbs img').removeClass('active');
                jQuery('.card_thumbs img[data-type="' + type + '"]').addClass('active');
            });
        }

        function getCardType(number) {
            if (/^4/.test(number)) return 'visa';
            if (/^5[1-5]/.test(number)) return 'mastercard';
            if (/^3[47]/.test(number)) return 'amex';
            if (/^6/.test(number)) return 'discover';
            return 'unknown';
        }
    });

    $(document).on("keydown", "#wc-expinet-cc-form #expipaga_expirydate", function(event){
        var code = event.keyCode;
        var allowedKeys = [8];

        if (allowedKeys.indexOf(code) !== -1) {
          return;
        }
        event.target.value = event.target.value.replace(
          /^([1-9]\/|[2-9])$/g, '0$1/' // 3 > 03/
        ).replace(
          /^(0[1-9]|1[0-2])$/g, '$1/' // 11 > 11/
        ).replace(
          /^([0-1])([3-9])$/g, '0$1/$2' // 13 > 01/3
        ).replace(
          /^(0?[1-9]|1[0-2])([0-9]{2})$/g, '$1/$2' // 141 > 01/41
        ).replace(
          /^([0]+)\/|[0]+$/g, '0' // 0/ > 0 and 00 > 0
        ).replace(
          /[^\d\/]|^[\/]*$/g, '' // To allow only digits and `/`
        ).replace(
          /\/\//g, '/' // Prevent entering more than 1 `/`
        );
    });
    
    $(document).on("input", "#wc-expinet-cc-form input[name=ccv]", function(event){
        var code = event.keyCode;
        var allowedKeys = [8];
        if (allowedKeys.indexOf(code) !== -1) {
          return;
        }
        event.target.value = event.target.value.replace(
            /[^\d\/]|^[\/]*$/g, '' // To allow only digits and `/`
        );
    });

   
});