/*
 * CPv1
 * Handlers Form Unlimit v1
 */

const ELLIPSIS = '...';
const DATA_CHECKOUT_SELECTOR = '[data-checkout]';

(function () {
    var CPv1 = {
        debug: false,
        gateway_mode: false,
        add_truncated_card: true,
        site_id: '',
        terminal_code: '',
        customer_and_card: {
            default: false,
            status: false
        },
        create_token_on: {
            event: true, //if true create token on event, if false create on click and ignore others events. eg: paste or keyup
            keyup: false,
            paste: true,
        },

        inputs_to_create_token: [
            'cardNumber',
            'cardholderName',
            'cardExpirationDate',
            'securityCode',
            'docType',
            'docNumber'
        ],

        inputs_to_create_token_customer_and_card: [
            'paymentMethodSelector',
            'securityCode'
        ],

        selectors: {
            paymentMethodSelector: '#paymentMethodSelector',
            pmCustomerAndCards: '#payment-methods-for-customer-and-cards',
            pmListOtherCards: '#payment-methods-list-other-cards',
            mpSecurityCodeCustomerAndCard: '#mp-securityCode-customer-and-card',

            cardNumber: '#cardNumber',
            cardholderName: '#cardholderName',
            cardExpirationDate: '#cardExpirationDate',
            securityCode: '#securityCode',
            cpf: '#cpf',
            cpfBoleto: '#cpf_boleto',
            zipBoleto: '#zip_boleto',
            cpfPix: '#cpf_pix',
            docType: '#docType',
            docNumber: '#docNumber',
            issuer: '#issuer',
            installments: '#installments',

            mpDoc: '.mp-doc',
            mpIssuer: '.mp-issuer',
            mpDocType: '.mp-docType',
            mpDocNumber: '.mp-docNumber',

            paymentMethodId: '#paymentMethodId',
            amount: '#amount',
            token: '#token',
            campaign_id: '#campaign_id',
            campaign: '#campaign',
            discount: '#discount',
            cardTruncated: '#cardTruncated',
            site_id: '#site_id',
            CustomerAndCard: '#CustomerAndCard',
            MpGatewayMode: '#MpGatewayMode',

            boxInstallments: '#mp-box-installments',
            boxInstallmentsSelector: '#mp-box-installments-selector',
            taxCFT: '#mp-box-input-tax-cft',
            taxTEA: '#mp-box-input-tax-tea',
            taxTextCFT: '#mp-tax-cft-text',
            taxTextTEA: '#mp-tax-tea-text',

            box_loading: '#mp-box-loading',
            submit: '#btnSubmit',
            form: '#cardpay-form',
            formBoleto: '#cardpay-form-boleto',
            formPix: '#cardpay-form-pix',
            formDiv: '#cardpay-form',
            formCustomerAndCard: '#cardpay-form-customer-and-card',
            utilities_fields: '#cardpay-utilities'
        },
        text: {
            choose: 'Choose',
            other_bank: 'Other Bank',
            discount_info1: 'You will save',
            discount_info2: 'with discount from',
            discount_info3: 'Total of your purchase:',
            discount_info4: 'Total of your purchase with discount:',
            discount_info5: 'Upon payment approval',
            discount_info6: 'Terms and Conditions of Use',
            apply: 'Apply',
            remove: 'Remove'
        },
        paths: {
            loading: 'images/loading.gif',
            check: 'images/check.png',
            error: 'images/error.png'
        }
    }

    CPv1.getBin = function () {
        const cardSelector = document.querySelector(CPv1.selectors.paymentMethodSelector);
        const selectedValue = String(cardSelector[cardSelector.options.selectedIndex].value);
        if (cardSelector && selectedValue !== "-1") {
            const firstSixDigits = cardSelector[cardSelector.options.selectedIndex].getAttribute('first_six_digits');
            if (firstSixDigits) {
                return firstSixDigits;
            }
        }

        const ccNumber = document.querySelector(CPv1.selectors.cardNumber);
        return ccNumber.value.replace(/[ .-]/g, '').slice(0, 6);
    }

    CPv1.clearOptions = function () {
        const bin = CPv1.getBin();
        if (bin.length === 0) {
            CPv1.hideIssuer();

            var selectorInstallments = document.querySelector(CPv1.selectors.installments),
                fragment = document.createDocumentFragment(),
                option = new Option(CPv1.text.choose + ELLIPSIS, '-1');

            selectorInstallments.options.length = 0;
            fragment.appendChild(option);
            selectorInstallments.appendChild(fragment);
            selectorInstallments.setAttribute('disabled', 'disabled');
        }
    }

    CPv1.setPaymentMethodInfo = function (status, response) {
        if (parseInt(status) === 200) {
            document.querySelector(CPv1.selectors.paymentMethodId).value = response[0].id;
            document.querySelector(CPv1.selectors.cardNumber).style.background = "url(" + response[0].secure_thumbnail + ") 98% 50% no-repeat #fff";

            // check if the issuer is necessary to pay
            var issuerMandatory = false,
                additionalInfo = response[0].additional_info_needed;

            for (var i = 0; i < additionalInfo.length; i++) {
                if (String(additionalInfo[i]) === "issuer_id") {
                    issuerMandatory = true;
                }
            }

            if (!issuerMandatory) {
                CPv1.hideIssuer();
            }
        }
    }

    /*
    * Issuers
    */
    CPv1.hideIssuer = function () {
        var $issuer = document.querySelector(CPv1.selectors.issuer);
        var opt = document.createElement('option');
        opt.value = "-1";
        opt.innerHTML = CPv1.text.other_bank;

        $issuer.innerHTML = "";
        $issuer.appendChild(opt);
        $issuer.setAttribute('disabled', 'disabled');
    }

    /*
    * Installments
    */
    CPv1.setInstallmentInfo = function (status, response) {
        var selectorInstallments = document.querySelector(CPv1.selectors.installments);
        const gatewayMode = CPv1.gateway_mode;

        if (response.length > 0) {
            let payerCosts = response[0].payer_costs;

            if (gatewayMode) {
                for (var x in response) {
                    var installments = response[x];
                    if (String(installments.processing_mode) === 'gateway') {
                        payerCosts = installments.payer_costs
                        document.querySelector(CPv1.selectors.gateway_mode).value = installments.merchant_account_id;
                    }
                }
            }

            const htmlOption = getHtmlOption(payerCosts);

            // not take the user's selection if equal
            if (String(selectorInstallments.innerHTML) !== htmlOption) {
                selectorInstallments.innerHTML = htmlOption;
            }

            selectorInstallments.removeAttribute('disabled');

            CPv1.showTaxes();
        }
    }

    function getDataInput(payerCosts, i) {
        if (String(CPv1.site_id) === 'MLA') {
            const tax = payerCosts[i].labels;
            if (tax.length > 0) {
                for (let l = 0; l < tax.length; l++) {
                    if (tax[l].indexOf('CFT_') !== -1) {
                        return 'data-tax="' + tax[l] + '"'
                    }
                }
            }
        }

        return '';
    }

    function getHtmlOption(payerCosts) {
        let htmlOption = '<option value="-1">' + CPv1.text.choose + '...</option>';

        for (let i = 0; i < payerCosts.length; i++) {
            const dataInput = getDataInput(payerCosts, i);
            htmlOption += '<option value="' + payerCosts[i].installments + '" ' + dataInput + '>' + (payerCosts[i].recommended_message || payerCosts[i].installments) + '</option>';
        }

        return htmlOption;
    }

    /*
    * Payment Methods
    */

    CPv1.getPaymentMethods = function () {
        var fragment = document.createDocumentFragment();
        var paymentMethodsSelector = document.querySelector(CPv1.selectors.paymentMethodSelector)
        var mainPaymentMethodSelector = document.querySelector(CPv1.selectors.paymentMethodSelector)

        // set loading
        mainPaymentMethodSelector.style.background = "url(" + CPv1.paths.loading + ") 95% 50% no-repeat #fff";

        // if customer and card
        let customerCard = false;
        for (let x = 0; paymentMethodsSelector.length > x; x++) {
            const checkoutType = String(paymentMethodsSelector[x].getAttribute('type_checkout'));
            if (checkoutType === 'customer_and_card') {
                customerCard = true;
            }
        }

        if (customerCard || CPv1.customer_and_card.status) {
            paymentMethodsSelector = document.querySelector(CPv1.selectors.pmListOtherCards)
            paymentMethodsSelector.innerHTML = '';
        } else {
            paymentMethodsSelector.innerHTML = '';
            const option = new Option(CPv1.text.choose + ELLIPSIS, '-1');
            fragment.appendChild(option);
        }
    }

    /*
    * Functions related to Create Tokens
    */
    CPv1.createTokenByEvent = function () {
        var $inputs = CPv1.getForm().querySelectorAll(DATA_CHECKOUT_SELECTOR);
        var $inputsToCreateToken = CPv1.getInputsToCreateToken();

        for (var x = 0; x < $inputs.length; x++) {
            var element = $inputs[x];

            // add events only in the required fields
            if ($inputsToCreateToken.indexOf(element.getAttribute('data-checkout')) > -1) {
                let event = 'focusout';

                if (String(element.nodeName) === 'SELECT') {
                    event = 'change';
                }

                CPv1.addListenerEvent(element, event, CPv1.validateInputsCreateToken);

                //for firefox
                CPv1.addListenerEvent(element, "blur", CPv1.validateInputsCreateToken);

                if (CPv1.create_token_on.keyup) {
                    CPv1.addListenerEvent(element, "keyup", CPv1.validateInputsCreateToken);
                }

                if (CPv1.create_token_on.paste) {
                    CPv1.addListenerEvent(element, "paste", CPv1.validateInputsCreateToken);
                }
            }
        }
    }

    CPv1.createTokenBySubmit = function () {
        CPv1.addListenerEvent(document.querySelector(CPv1.selectors.form), 'submit', CPv1.doPay);
    }

    let doSubmit = false;

    CPv1.doPay = function (event) {
        event.preventDefault();
        if (!doSubmit) {
            CPv1.createToken();
            return false;
        }

        return true;
    }

    CPv1.validateInputsCreateToken = function () {
        var validToCreateToken = true;
        var $inputs = CPv1.getForm().querySelectorAll(DATA_CHECKOUT_SELECTOR);
        var $inputsToCreateToken = CPv1.getInputsToCreateToken();

        for (var x = 0; x < $inputs.length; x++) {
            var element = $inputs[x];

            // check is a input to create token
            if ($inputsToCreateToken.indexOf(element.getAttribute("data-checkout")) > -1) {
                if (parseInt(element.value) === -1 || String(element.value) === "") {
                    validToCreateToken = false;
                } // end if check values
            } // end if check data-checkout
        }

        if (validToCreateToken) {
            CPv1.createToken();
        }
    }

    CPv1.createToken = function () {
        CPv1.hideErrors();

        // show loading
        document.querySelector(CPv1.selectors.box_loading).style.background = "url(" + CPv1.paths.loading + ") 0 50% no-repeat #fff";

        return false;
    }

    CPv1.sdkResponseHandler = function (status, response) {
        document.querySelector(CPv1.selectors.box_loading).style.background = "";

        const intStatus = parseInt(status);
        if (intStatus !== 200 && intStatus !== 201) {
            CPv1.showErrors(response);
        } else {
            var token = document.querySelector(CPv1.selectors.token);
            token.value = response.id;

            if (CPv1.add_truncated_card) {
                document.querySelector(CPv1.selectors.cardTruncated).value = CPv1.truncateCard(response);
            }

            if (!CPv1.create_token_on.event) {
                doSubmit = true;
                var btn = document.querySelector(CPv1.selectors.form);
                btn.submit();
            }
        }
    }

    /*
    * Useful functions
    */
    CPv1.resetBackgroundCard = function () {
        document.querySelector(CPv1.selectors.paymentMethodSelector).style.background = "no-repeat #fff";
        document.querySelector(CPv1.selectors.cardNumber).style.background = "no-repeat #fff";
    }

    CPv1.setForm = function () {
        if (CPv1.customer_and_card.status) {
            document.querySelector(CPv1.selectors.formDiv).style.display = 'none';
            document.querySelector(CPv1.selectors.mpSecurityCodeCustomerAndCard).removeAttribute('style');
        } else {
            document.querySelector(CPv1.selectors.mpSecurityCodeCustomerAndCard).style.display = 'none';
            document.querySelector(CPv1.selectors.formDiv).removeAttribute('style');
        }

        if (CPv1.create_token_on.event) {
            CPv1.createTokenByEvent();
            CPv1.validateInputsCreateToken();
        }

        document.querySelector(CPv1.selectors.CustomerAndCard).value = CPv1.customer_and_card.status;
    }

    CPv1.getForm = function () {
        if (CPv1.customer_and_card.status) {
            return document.querySelector(CPv1.selectors.formCustomerAndCard);
        } else {
            return document.querySelector(CPv1.selectors.form);
        }
    }

    CPv1.getInputsToCreateToken = function () {
        if (CPv1.customer_and_card.status) {
            return CPv1.inputs_to_create_token_customer_and_card;
        } else {
            return CPv1.inputs_to_create_token;
        }
    }

    CPv1.truncateCard = function (responseCardToken) {
        let firstSixDigits;
        let lastFourDigits;

        if (CPv1.customer_and_card.status) {
            var cardSelector = document.querySelector(CPv1.selectors.paymentMethodSelector);
            firstSixDigits = cardSelector[cardSelector.options.selectedIndex].getAttribute("first_six_digits").match(/.{1,4}/g)
            lastFourDigits = cardSelector[cardSelector.options.selectedIndex].getAttribute("last_four_digits")
        } else {
            firstSixDigits = responseCardToken.first_six_digits.match(/.{1,4}/g)
            lastFourDigits = responseCardToken.last_four_digits
        }

        return firstSixDigits[0] + " " + firstSixDigits[1] + "** **** " + lastFourDigits;
    }

    CPv1.getAmount = function () {
        return document.querySelector(CPv1.selectors.amount).value - document.querySelector(CPv1.selectors.discount).value;
    }

    CPv1.getAmountWithoutDiscount = function () {
        return document.querySelector(CPv1.selectors.amount).value;
    }

    /*
    * Show errors
    */
    CPv1.showErrors = function (response) {
        var $form = CPv1.getForm();

        for (let x = 0; x < response.cause.length; x++) {
            var error = response.cause[x];
            var $span = $form.querySelector('#mp-error-' + error.code);
            if ($span) {
                var $input = $form.querySelector($span.getAttribute("data-main"));

                $span.style.display = 'inline-block';
                $input.classList.add("mp-error-input");
            }
        }
    }

    CPv1.hideErrors = function () {
        for (let x = 0; x < document.querySelectorAll(DATA_CHECKOUT_SELECTOR).length; x++) {
            const $field = document.querySelectorAll(DATA_CHECKOUT_SELECTOR)[x];
            $field.classList.remove("mp-error-input");
        }

        for (var errorNumber = 0; errorNumber < document.querySelectorAll('.mp-error').length; errorNumber++) {
            const $span = document.querySelectorAll('.mp-error')[errorNumber];
            $span.style.display = 'none';
        }
    }

    /*
    * Add events to guessing
    */
    CPv1.addListenerEvent = function (el, eventName, handler) {
        if (el.addEventListener) {
            el.addEventListener(eventName, handler);
        } else {
            el.attachEvent('on' + eventName, function () {
                handler.call(el);
            });
        }
    };

    CPv1.InitializeEvents = function () {
        CPv1.addListenerEvent(document.querySelector(CPv1.selectors.cardNumber), 'keyup', CPv1.clearOptions);
    }

    CPv1.showTaxes = function () {
        const selectorInstallments = document.querySelector(CPv1.selectors.installments);
        const tax = selectorInstallments.options[selectorInstallments.selectedIndex].getAttribute('data-tax');

        let cft = ''
        let tea = ''

        if (tax != null) {
            const taxSplit = tax.split('|');
            cft = String(taxSplit[0].replace('_', ' '));
            tea = taxSplit[1].replace('_', ' ');

            if (cft === 'CFT 0,00%' && tea === 'TEA 0,00%') {
                cft = ''
                tea = ''
            }
        }

        document.querySelector(CPv1.selectors.taxTextCFT).innerHTML = cft;
        document.querySelector(CPv1.selectors.taxTextTEA).innerHTML = tea;
    }

    /*
    * Utilities
    */
    CPv1.referer = (function () {
        return window.location.protocol + "//" + window.location.hostname + (window.location.port ? ':' + window.location.port : '');
    })();

    CPv1.AJAX = function (options) {
        var useXDomain = !!window.XDomainRequest;

        var req = useXDomain ? new XDomainRequest() : new XMLHttpRequest()

        options.url += (options.url.indexOf("?") >= 0 ? "&" : "?") + "referer=" + escape(CPv1.referer);

        options.requestedMethod = options.method;

        if (useXDomain && String(options.method) === "PUT") {
            options.method = "POST";
            options.url += "&_method=PUT";
        }

        req.open(options.method, options.url, true);

        req.timeout = options.timeout || 1000;

        handleOptions(req, options);

        if (options.method === 'GET' || options.data == null) {
            req.send();
        } else {
            req.send(JSON.stringify(options.data));
        }
    }

    function handleOptions(req, options) {
        if (window.XDomainRequest) {
            xDomainRequest(req, options);
        } else {
            req.setRequestHeader('Accept', 'application/json');

            if (options.contentType) {
                req.setRequestHeader('Content-Type', options.contentType);
            } else {
                req.setRequestHeader('Content-Type', 'application/json');
            }

            onReadyStateChange(req, options);
        }
    }

    function xDomainRequest(req, options) {
        req.onload = function () {
            const data = JSON.parse(req.responseText);
            if (typeof options.success === "function") {
                options.success(options.requestedMethod === 'POST' ? 201 : 200, data);
            }
        };

        req.onerror = req.ontimeout = function () {
            if (typeof options.error === "function") {
                options.error(400, {user_agent: window.navigator.userAgent, error: "bad_request", cause: []});
            }
        };
    }

    function onReadyStateChange(req, options) {
        req.onreadystatechange = function () {
            if (this.readyState !== 4) {
                return
            }

            handleReadyStateChange.call(this, options);
        };
    }

    function handleReadyStateChange(options) {
        if (this.status >= 200 && this.status < 400) {
            const data = JSON.parse(this.responseText);
            if (typeof options.success === "function") {
                options.success(this.status, data);
            }

        } else if (this.status >= 400) {
            const data = JSON.parse(this.responseText);
            if (typeof options.error === "function") {
                options.error(this.status, data);
            }

        } else if (typeof options.error === "function") {
            options.error(503, {});
        }
    }

    /*
    * Initialization function
    */

    CPv1.Initialize = function (siteId, terminalCode) {
        // sets
        CPv1.site_id = siteId
        CPv1.terminal_code = terminalCode

        // initialize events
        CPv1.InitializeEvents();

        // flow: customer & cards
        const selectorPmCustomerAndCards = document.querySelector(CPv1.selectors.pmCustomerAndCards);
        if (!selectorPmCustomerAndCards || !CPv1.customer_and_card.default || !(parseInt(selectorPmCustomerAndCards.childElementCount) > 0)) {
            // if customer & cards is disabled or customer does not have cards
            CPv1.customer_and_card.status = false;
            document.querySelector(CPv1.selectors.formCustomerAndCard).style.display = 'none';
        }

        if (CPv1.create_token_on.event) {
            CPv1.createTokenByEvent();
        } else {
            CPv1.createTokenBySubmit()
        }

        mlmFlow();

        // flow: CPB AND MCO
        cpbAndMcoFlow();

        if (CPv1.debug) {
            document.querySelector(CPv1.selectors.utilities_fields).style.display = 'inline-block';
            console.log(CPv1);
        }

        document.querySelector(CPv1.selectors.site_id).value = CPv1.site_id;
        document.querySelector(CPv1.selectors.formCustomerAndCard).style.display = 'none';
    }

    function mlmFlow() {
        if (String(CPv1.site_id) === "MLM") {
            document.querySelector(CPv1.selectors.mpDoc).style.display = 'none';

            if (!CPv1.customer_and_card.status) {
                document.querySelector(CPv1.selectors.mpSecurityCodeCustomerAndCard).style.display = 'none';
            }

            // removing not used fields for this country
            if (CPv1.inputs_to_create_token.includes("docType")) {
                CPv1.inputs_to_create_token.splice(CPv1.inputs_to_create_token.indexOf("docType"), 1);
            }
            if (CPv1.inputs_to_create_token.includes("docNumber")) {
                CPv1.inputs_to_create_token.splice(CPv1.inputs_to_create_token.indexOf("docNumber"), 1);
            }
        }
    }

    function cpbAndMcoFlow() {
        if (String(CPv1.site_id) === "CPB") {
            document.querySelector(CPv1.selectors.mpDocType).style.display = 'none';
            document.querySelector(CPv1.selectors.mpIssuer).style.display = 'none';
            document.querySelector(CPv1.selectors.docNumber).classList.remove('mp-col-75');
            document.querySelector(CPv1.selectors.docNumber).classList.add('mp-col-100');
        } else if (String(CPv1.site_id) === 'MCO') {
            document.querySelector(CPv1.selectors.mpIssuer).style.display = 'none';
        } else if (String(CPv1.site_id) === 'MLA') {
            document.querySelector(CPv1.selectors.boxInstallmentsSelector).classList.remove('mp-col-100');
            document.querySelector(CPv1.selectors.boxInstallmentsSelector).classList.add('mp-col-70');
            document.querySelector(CPv1.selectors.taxCFT).style.display = 'block';
            document.querySelector(CPv1.selectors.taxTEA).style.display = 'block';

            CPv1.addListenerEvent(document.querySelector(CPv1.selectors.installments), 'change', CPv1.showTaxes);
        } else if (String(CPv1.site_id) === 'MLC') {
            document.querySelector(CPv1.selectors.mpIssuer).style.display = 'none';
        }
    }

    this.CPv1 = CPv1;

}).call();
