/*
 * CPv1
 * Handlers Form Unlimint v1
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
        coupon_of_discounts: {
            discount_action_url: '',
            payer_email: '',
            default: true,
            status: true
        },
        customer_and_card: {
            default: false,
            status: false
        },
        create_token_on: {
            event: true, //if true create token on event, if false create on click and ignore others events. eg: paste or keyup
            keyup: false,
            paste: true,
        },

        inputs_to_create_discount: [
            "couponCode",
            "applyCoupon"
        ],

        inputs_to_create_token: [
            "cardNumber",
            "cardExpirationMonth",
            "cardExpirationYear",
            "cardholderName",
            "securityCode",
            "docType",
            "docNumber"
        ],

        inputs_to_create_token_customer_and_card: [
            "paymentMethodSelector",
            "securityCode"
        ],

        selectors: {
            couponCode: "#couponCode",
            applyCoupon: "#applyCouponCard",
            mpCouponApplyed: "#mpCouponApplyed",
            mpCouponError: "#mpCouponError",

            paymentMethodSelector: "#paymentMethodSelector",
            pmCustomerAndCards: "#payment-methods-for-customer-and-cards",
            pmListOtherCards: "#payment-methods-list-other-cards",
            mpSecurityCodeCustomerAndCard: "#mp-securityCode-customer-and-card",

            cardNumber: "#cardNumber",
            cardExpirationMonth: "#cardExpirationMonth",
            cardExpirationYear: "#cardExpirationYear",
            cardholderName: "#cardholderName",
            securityCode: "#securityCode",
            cpf: "#cpf",
            cpfBoleto: "#cpf_boleto",
            docType: "#docType",
            docNumber: "#docNumber",
            issuer: "#issuer",
            installments: "#installments",

            mpDoc: ".mp-doc",
            mpIssuer: ".mp-issuer",
            mpDocType: ".mp-docType",
            mpDocNumber: ".mp-docNumber",

            paymentMethodId: "#paymentMethodId",
            amount: "#amount",
            token: "#token",
            campaign_id: "#campaign_id",
            campaign: "#campaign",
            discount: "#discount",
            cardTruncated: "#cardTruncated",
            site_id: "#site_id",
            CustomerAndCard: '#CustomerAndCard',
            MpGatewayMode: '#MpGatewayMode',

            boxInstallments: '#mp-box-installments',
            boxInstallmentsSelector: '#mp-box-installments-selector',
            taxCFT: '#mp-box-input-tax-cft',
            taxTEA: '#mp-box-input-tax-tea',
            taxTextCFT: '#mp-tax-cft-text',
            taxTextTEA: '#mp-tax-tea-text',

            box_loading: "#mp-box-loading",
            submit: "#btnSubmit",
            form: '#cardpay-form',
            formBoleto: '#cardpay-form-boleto',
            formDiv: '#cardpay-form',
            formCoupon: '#cardpay-form-coupon',
            formCustomerAndCard: '#cardpay-form-customer-and-card',
            utilities_fields: "#cardpay-utilities"
        },
        text: {
            choose: "Choose",
            other_bank: "Other Bank",
            discount_info1: "You will save",
            discount_info2: "with discount from",
            discount_info3: "Total of your purchase:",
            discount_info4: "Total of your purchase with discount:",
            discount_info5: "*Uppon payment approval",
            discount_info6: "Terms and Conditions of Use",
            coupon_empty: "Please, inform your coupon code",
            apply: "Apply",
            remove: "Remove"
        },
        paths: {
            loading: "images/loading.gif",
            check: "images/check.png",
            error: "images/error.png"
        }
    }

    CPv1.getBin = function () {
        var cardSelector = document.querySelector(CPv1.selectors.paymentMethodSelector);
        if (cardSelector && cardSelector[cardSelector.options.selectedIndex].value != "-1") {
            var first_six_digits = cardSelector[cardSelector.options.selectedIndex].getAttribute('first_six_digits');
            if (first_six_digits) {
                return first_six_digits;
            }
        }

        var ccNumber = document.querySelector(CPv1.selectors.cardNumber);
        return ccNumber.value.replace(/[ .-]/g, '').slice(0, 6);
    }

    CPv1.clearOptions = function () {
        var bin = CPv1.getBin();

        if (bin.length == 0) {
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
        if (status == 200) {
            //guessing
            document.querySelector(CPv1.selectors.paymentMethodId).value = response[0].id;
            document.querySelector(CPv1.selectors.cardNumber).style.background = "url(" + response[0].secure_thumbnail + ") 98% 50% no-repeat #fff";

            // check if the issuer is necessary to pay
            var issuerMandatory = false,
                additionalInfo = response[0].additional_info_needed;

            for (var i = 0; i < additionalInfo.length; i++) {
                if (additionalInfo[i] == "issuer_id") {
                    issuerMandatory = true;
                }
            }

            if (issuerMandatory) {
                var payment_method_id = response[0].id;
                CPv1.getIssuersPaymentMethod(payment_method_id);
            } else {
                CPv1.hideIssuer();
            }
        }
    }

    CPv1.changePaymetMethodSelector = function () {
        var payment_method_id = document.querySelector(CPv1.selectors.paymentMethodSelector).value;
        CPv1.getIssuersPaymentMethod(payment_method_id);
    }

    /*
    * Issuers
    */
    CPv1.getIssuersPaymentMethod = function (payment_method_id) {
        if (payment_method_id != -1) {
            CPv1.addListenerEvent(document.querySelector(CPv1.selectors.issuer), 'change', CPv1.setInstallmentsByIssuerId);
        }
    }

    CPv1.setInstallmentsByIssuerId = function (status, response) {
        var issuerId = document.querySelector(CPv1.selectors.issuer).value;
        var amount = CPv1.getAmount();

        if (issuerId == -1 || issuerId == "") {
            return;
        }

        var params_installments = {
            "bin": CPv1.getBin(),
            "amount": amount,
            "issuer_id": issuerId
        }

        if (CPv1.site_id == "MLM") {
            params_installments = {
                "payment_method_id": document.querySelector(CPv1.selectors.paymentMethodSelector).value,
                "amount": amount,
                "issuer_id": issuerId
            }
        }

    }

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
        var gateway_mode = CPv1.gateway_mode;

        if (response.length > 0) {
            let payerCosts = response[0].payer_costs;

            if (gateway_mode) {
                for (var x in response) {
                    var installments = response[x];
                    if (installments.processing_mode == 'gateway') {
                        payerCosts = installments.payer_costs
                        document.querySelector(CPv1.selectors.gateway_mode).value = installments.merchant_account_id;
                    }
                }
            }

            const htmlOption = getHtmlOption(payerCosts);

            // not take the user's selection if equal
            if (selectorInstallments.innerHTML != htmlOption) {
                selectorInstallments.innerHTML = htmlOption;
            }

            selectorInstallments.removeAttribute('disabled');

            CPv1.showTaxes();
        }
    }

    function getDataInput(payerCosts, i) {
        if (CPv1.site_id == 'MLA') {
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
    * Customer & Cards
    */
    CPv1.cardsHandler = function () {
        var cardSelector = document.querySelector(CPv1.selectors.paymentMethodSelector);

        if (cardSelector.options.selectedIndex < 0) {
            return;
        }

        if (CPv1.site_id == "MLM") {
            CPv1.setInstallmentsByIssuerId();
        }
    }

    /*
    * Payment Methods
    */

    CPv1.getPaymentMethods = function () {
        var fragment = document.createDocumentFragment();
        var paymentMethodsSelector = document.querySelector(CPv1.selectors.paymentMethodSelector)
        var mainPaymentMethodSelector = document.querySelector(CPv1.selectors.paymentMethodSelector)

        //set loading
        mainPaymentMethodSelector.style.background = "url(" + CPv1.paths.loading + ") 95% 50% no-repeat #fff";

        //if customer and card
        var paymentCustomerAndCard = document.querySelector(CPv1.selectors.pmCustomerAndCards)
        var customerCard = false;
        for (var x = 0; paymentMethodsSelector.length > x; x++) {
            if (paymentMethodsSelector[x].getAttribute("type_checkout") == 'customer_and_card') {
                customerCard = true;
            }
        }

        if (customerCard || CPv1.customer_and_card.status) {
            paymentMethodsSelector = document.querySelector(CPv1.selectors.pmListOtherCards)
            paymentMethodsSelector.innerHTML = "";
        } else {
            paymentMethodsSelector.innerHTML = "";

            var option = new Option(CPv1.text.choose + ELLIPSIS, '-1');
            fragment.appendChild(option);
        }
    }

    /*
    * Functions related to Create Tokens
    */
    CPv1.createTokenByEvent = function () {
        var $inputs = CPv1.getForm().querySelectorAll(DATA_CHECKOUT_SELECTOR);
        var $inputs_to_create_token = CPv1.getInputsToCreateToken();

        for (var x = 0; x < $inputs.length; x++) {
            var element = $inputs[x];

            //add events only in the required fields
            if ($inputs_to_create_token.indexOf(element.getAttribute("data-checkout")) > -1) {
                var event = "focusout";

                if (element.nodeName == "SELECT") {
                    event = "change";
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

    var doSubmit = false;

    CPv1.doPay = function (event) {
        event.preventDefault();
        if (!doSubmit) {
            CPv1.createToken();
            return false;
        }
    }

    CPv1.validateInputsCreateToken = function () {
        var valid_to_create_token = true;
        var $inputs = CPv1.getForm().querySelectorAll(DATA_CHECKOUT_SELECTOR);
        var $inputs_to_create_token = CPv1.getInputsToCreateToken();

        for (var x = 0; x < $inputs.length; x++) {
            var element = $inputs[x];

            //check is a input to create token
            if ($inputs_to_create_token.indexOf(element.getAttribute("data-checkout")) > -1) {
                if (element.value == -1 || element.value == "") {
                    valid_to_create_token = false;
                } //end if check values
            } //end if check data-checkout
        }

        if (valid_to_create_token) {
            CPv1.createToken();
        }
    }

    CPv1.createToken = function () {
        CPv1.hideErrors();

        //show loading
        document.querySelector(CPv1.selectors.box_loading).style.background = "url(" + CPv1.paths.loading + ") 0 50% no-repeat #fff";

        return false;
    }

    CPv1.sdkResponseHandler = function (status, response) {
        document.querySelector(CPv1.selectors.box_loading).style.background = "";

        if (status != 200 && status != 201) {
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
    * useful functions
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

    CPv1.truncateCard = function (response_card_token) {
        var first_six_digits;
        var last_four_digits;

        if (CPv1.customer_and_card.status) {
            var cardSelector = document.querySelector(CPv1.selectors.paymentMethodSelector);
            first_six_digits = cardSelector[cardSelector.options.selectedIndex].getAttribute("first_six_digits").match(/.{1,4}/g)
            last_four_digits = cardSelector[cardSelector.options.selectedIndex].getAttribute("last_four_digits")
        } else {
            first_six_digits = response_card_token.first_six_digits.match(/.{1,4}/g)
            last_four_digits = response_card_token.last_four_digits
        }

        return first_six_digits[0] + " " + first_six_digits[1] + "** **** " + last_four_digits;
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

        for (var x = 0; x < response.cause.length; x++) {
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

        } //end for

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
        var selectorIsntallments = document.querySelector(CPv1.selectors.installments);
        var tax = selectorIsntallments.options[selectorIsntallments.selectedIndex].getAttribute('data-tax');

        var cft = ""
        var tea = ""

        if (tax != null) {
            var tax_split = tax.split('|');
            cft = tax_split[0].replace('_', ' ');
            tea = tax_split[1].replace('_', ' ');

            if (cft == "CFT 0,00%" && tea == "TEA 0,00%") {
                cft = ""
                tea = ""
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

        if (useXDomain && options.method == "PUT") {
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

    CPv1.Initialize = function (site_id, terminal_code, coupon_mode, discount_action_url, payer_email) {
        // sets
        CPv1.site_id = site_id
        CPv1.terminal_code = terminal_code
        CPv1.coupon_of_discounts.default = coupon_mode
        CPv1.coupon_of_discounts.discount_action_url = discount_action_url
        CPv1.coupon_of_discounts.payer_email = payer_email

        // initialize events
        CPv1.InitializeEvents();

        // flow: customer & cards
        const selectorPmCustomerAndCards = document.querySelector(CPv1.selectors.pmCustomerAndCards);
        if (selectorPmCustomerAndCards && CPv1.customer_and_card.default && selectorPmCustomerAndCards.childElementCount > 0) {
            CPv1.addListenerEvent(document.querySelector(CPv1.selectors.paymentMethodSelector), 'change', CPv1.cardsHandler);
            CPv1.cardsHandler();
        } else {
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
        if (CPv1.site_id == "MLM") {
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

            CPv1.addListenerEvent(document.querySelector(CPv1.selectors.paymentMethodSelector), 'change', CPv1.changePaymetMethodSelector);
        }
    }

    function cpbAndMcoFlow() {
        if (CPv1.site_id == "CPB") {
            document.querySelector(CPv1.selectors.mpDocType).style.display = 'none';
            document.querySelector(CPv1.selectors.mpIssuer).style.display = 'none';
            // ajust css
            document.querySelector(CPv1.selectors.docNumber).classList.remove("mp-col-75");
            document.querySelector(CPv1.selectors.docNumber).classList.add("mp-col-100");
        } else if (CPv1.site_id == "MCO") {
            document.querySelector(CPv1.selectors.mpIssuer).style.display = 'none';
        } else if (CPv1.site_id == "MLA") {
            document.querySelector(CPv1.selectors.boxInstallmentsSelector).classList.remove("mp-col-100");
            document.querySelector(CPv1.selectors.boxInstallmentsSelector).classList.add("mp-col-70");
            document.querySelector(CPv1.selectors.taxCFT).style.display = 'block';
            document.querySelector(CPv1.selectors.taxTEA).style.display = 'block';

            CPv1.addListenerEvent(document.querySelector(CPv1.selectors.installments), 'change', CPv1.showTaxes);
        } else if (CPv1.site_id == "MLC") {
            document.querySelector(CPv1.selectors.mpIssuer).style.display = 'none';
        }
    }

    this.CPv1 = CPv1;

}).call();
