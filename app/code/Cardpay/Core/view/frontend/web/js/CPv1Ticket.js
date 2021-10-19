// CPv1
// Handlers Form Unlimint v1

const INLINE_BLOCK = 'inline-block';

(function () {
    var CPv1Ticket = {
        site_id: "",
        coupon_of_discounts: {
            discount_action_url: "",
            payer_email: "",
            default: true,
            status: true
        },
        validate_on: {
            event: false,
            keyup: false,
            paste: true,
        },
        inputs_to_create_discount: [
            "couponCodeTicket",
            "applyCouponTicket"
        ],
        inputs_to_validate_ticket: [
            "firstname",
            "lastname",
            "docNumber",
            "address",
            "number",
            "city",
            "state",
            "zipcode"
        ],
        selectors: {
            // coupom
            couponCode: "#couponCodeTicket",
            applyCoupon: "#applyCouponTicket",
            mpCouponApplyed: "#mpCouponApplyedTicket",
            mpCouponError: "#mpCouponErrorTicket",
            campaign_id: "#campaign_idTicket",
            campaign: "#campaignTicket",
            discount: "#discountTicket",

            //
            firstName: "#CPv1-firstname",
            lastName: "#CPv1-lastname",
            docTypeFisica: "#CPv1-docType-fisica",
            docTypeJuridica: "#CPv1-docType-juridica",
            docNumber: "#CPv1-docNumber",
            address: "#CPv1-address",
            number: "#CPv1-number",
            city: "#CPv1-city",
            state: "#CPv1-state",
            zipcode: "#CPv1-zipcode",

            // payment method and checkout
            paymentMethodId: "#paymentMethodId",
            amount: "#amountTicket",

            //other rules
            boxFirstName: "#box-firstname",
            boxLastName: "#box-lastname",
            boxDocNumber: "#box-docnumber",
            titleFirstName: ".title-name",
            titleFirstNameRazaoSocial: ".title-razao-social",
            titleDocNumber: ".title-cpf",
            titleDocNumberCNPJ: ".title-cnpj",

            // form
            radioTypeFisica: '#CPv1-docType-fisica',
            radioTypeJuridica: '#CPv1-docType-juridica',
            formCoupon: '#cardpay-form-coupon-ticket',
            formTicket: '#form-ticket',
            box_loading: "#mp-box-loading",
            submit: "#btnSubmit",
            form: "#cardpay-ticket-form-general"
        },
        febraban: {
            // febraban
            firstname: "#febrabanFirstname",
            lastname: "#febrabanLastname",
            docNumber: "#febrabanDocNumber",
            address: "#febrabanAddress",
            number: "#febrabanNumber",
            city: "#febrabanCity",
            state: "#febrabanState",
            zipcode: "#febrabanZipcode"
        },
        text: {
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

    // === Initialization function

    CPv1Ticket.addListenerEvent = function (el, eventName, handler) {
        if (el.addEventListener) {
            el.addEventListener(eventName, handler);
        } else {
            el.attachEvent("on" + eventName, function () {
                handler.call(el);
            });
        }

    };

    /*
    * Utilities
    */

    CPv1Ticket.referer = (function () {
        return window.location.protocol + "//" +
            window.location.hostname + (window.location.port ? ":" + window.location.port : "");
    })();

    CPv1Ticket.AJAX = function (options) {
        const useXDomain = !!window.XDomainRequest;
        const req = useXDomain ? new XDomainRequest() : new XMLHttpRequest()

        options.url += (options.url.indexOf("?") >= 0 ? "&" : "?") + "referer=" + escape(CPv1Ticket.referer);
        options.requestedMethod = options.method;
        if (useXDomain && options.method == "PUT") {
            options.method = "POST";
            options.url += "&_method=PUT";
        }

        req.open(options.method, options.url, true);
        req.timeout = options.timeout || 1000;

        handleOptions(req, options);

        if (options.method === "GET" || options.data == null) {
            req.send();
        } else {
            req.send(JSON.stringify(options.data));
        }
    }

    function handleOptions(req, options) {
        if (window.XDomainRequest) {
            xDomainRequest(req, options);
        } else {
            req.setRequestHeader("Accept", "application/json");
            if (options.contentType) {
                req.setRequestHeader("Content-Type", options.contentType);
            } else {
                req.setRequestHeader("Content-Type", "application/json");
            }

            onReadyStateChange(req, options);
        }
    }

    function xDomainRequest(req, options) {
        req.onload = function () {
            const data = JSON.parse(req.responseText);
            if (typeof options.success === "function") {
                options.success(options.requestedMethod === "POST" ? 201 : 200, data);
            }
        };

        req.onerror = req.ontimeout = function () {
            if (typeof options.error === "function") {
                options.error(400, {
                    user_agent: window.navigator.userAgent, error: "bad_request", cause: []
                });
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

    // Form validation

    var doSubmitTicket = false;

    CPv1Ticket.doPay = function (febraban) {
        document.querySelector(CPv1Ticket.febraban.firstname).value = febraban['firstname'];
        document.querySelector(CPv1Ticket.febraban.lastname).value = febraban['lastname'];
        document.querySelector(CPv1Ticket.febraban.docNumber).value = febraban['docNumber'];
        document.querySelector(CPv1Ticket.febraban.address).value = febraban['address'];
        document.querySelector(CPv1Ticket.febraban.number).value = febraban['number'];
        document.querySelector(CPv1Ticket.febraban.city).value = febraban['city'];
        document.querySelector(CPv1Ticket.febraban.state).value = febraban['state'];
        document.querySelector(CPv1Ticket.febraban.zipcode).value = febraban['zipcode'];
        if (!doSubmitTicket) {
            document.querySelector(CPv1Ticket.selectors.box_loading).style.background = "url(" + CPv1Ticket.paths.loading + ") 0 50% no-repeat #fff";
            const btn = document.querySelector(CPv1Ticket.selectors.form);
            btn.submit();
        }
    }

    CPv1Ticket.getForm = function () {
        return document.querySelector(CPv1Ticket.selectors.form);
    }

    CPv1Ticket.addListenerEvent = function (el, eventName, handler) {
        if (el.addEventListener) {
            el.addEventListener(eventName, handler);
        } else {
            el.attachEvent('on' + eventName, function () {
                handler.call(el);
            });
        }

    };

    // Show/hide errors.

    CPv1Ticket.showErrors = function (fields) {
        var $form = CPv1Ticket.getForm();
        for (var x = 0; x < fields.length; x++) {
            var f = fields[x];
            var $span = $form.querySelector('#error_' + f);
            var $input = $form.querySelector($span.getAttribute("data-main"));
            $span.style.display = INLINE_BLOCK;
            $input.classList.add("mp-error-input");
        }
    }

    CPv1Ticket.hideErrors = function () {
        for (var x = 0; x < document.querySelectorAll('[data-checkout]').length; x++) {
            var $field = document.querySelectorAll('[data-checkout]')[x];
            $field.classList.remove("mp-error-input");
        }

        for (var x = 0; x < document.querySelectorAll('.erro_febraban').length; x++) {
            var $span = document.querySelectorAll('.erro_febraban')[x];
            $span.style.display = 'none';
        }
    }

    CPv1Ticket.actionsMLB = function () {
        CPv1Ticket.addListenerEvent(document.querySelector(CPv1Ticket.selectors.docNumber), 'keyup', CPv1Ticket.execFormatDocument);
        CPv1Ticket.addListenerEvent(document.querySelector(CPv1Ticket.selectors.radioTypeFisica), "change", CPv1Ticket.initializeDocumentPessoaFisica);
        CPv1Ticket.addListenerEvent(document.querySelector(CPv1Ticket.selectors.radioTypeJuridica), "change", CPv1Ticket.initializeDocumentPessoaJuridica);
    }

    CPv1Ticket.initializeDocumentPessoaFisica = function () {

        // show elements
        document.querySelector(CPv1Ticket.selectors.boxLastName).style.display = INLINE_BLOCK;
        document.querySelector(CPv1Ticket.selectors.titleFirstName).style.display = INLINE_BLOCK;
        document.querySelector(CPv1Ticket.selectors.titleDocNumber).style.display = INLINE_BLOCK;

        // adjustment css
        document.querySelector(CPv1Ticket.selectors.boxFirstName).classList.remove("form-col-12");
        document.querySelector(CPv1Ticket.selectors.boxFirstName).classList.add("form-col-6");

        // hide elements
        document.querySelector(CPv1Ticket.selectors.titleFirstNameRazaoSocial).style.display = 'none';
        document.querySelector(CPv1Ticket.selectors.titleDocNumberCNPJ).style.display = 'none';

        // force max length CPF
        document.querySelector(CPv1Ticket.selectors.docNumber).maxLength = 14;
    }

    CPv1Ticket.initializeDocumentPessoaJuridica = function () {

        // show elements
        document.querySelector(CPv1Ticket.selectors.titleFirstNameRazaoSocial).style.display = INLINE_BLOCK;
        document.querySelector(CPv1Ticket.selectors.titleDocNumberCNPJ).style.display = INLINE_BLOCK;

        // adjustment css
        document.querySelector(CPv1Ticket.selectors.boxFirstName).classList.remove("form-col-6");
        document.querySelector(CPv1Ticket.selectors.boxFirstName).classList.add("form-col-12");

        // Hide Elements
        document.querySelector(CPv1Ticket.selectors.boxLastName).style.display = 'none';
        document.querySelector(CPv1Ticket.selectors.titleFirstName).style.display = 'none';
        document.querySelector(CPv1Ticket.selectors.titleDocNumber).style.display = 'none';

        // force max length CNPJ
        document.querySelector(CPv1Ticket.selectors.docNumber).maxLength = 18;
    }

    CPv1Ticket.validaCPF = function (strCPF) {
        var Soma;
        var Resto;
        strCPF = strCPF.replace(/[.-\s]/g, '')
        Soma = 0;

        if (strCPF == "00000000000") {
            return false;
        }

        for (let i = 1; i <= 9; i++) {
            Soma = Soma + parseInt(strCPF.substring(i - 1, i)) * (11 - i);
        }

        Resto = (Soma * 10) % 11;

        if ((Resto == 10) || (Resto == 11)) {
            Resto = 0;
        }

        if (Resto != parseInt(strCPF.substring(9, 10))) {
            return false;
        }

        Soma = 0;
        for (let i = 1; i <= 10; i++) {
            Soma = Soma + parseInt(strCPF.substring(i - 1, i)) * (12 - i);
        }

        Resto = (Soma * 10) % 11;

        if ((Resto == 10) || (Resto == 11)) {
            Resto = 0;
        }

        return Resto == parseInt(strCPF.substring(10, 11));
    }

    CPv1Ticket.validaCNPJ = function (strCNPJ) {
        strCNPJ = strCNPJ.replace('.', '');
        strCNPJ = strCNPJ.replace('.', '');
        strCNPJ = strCNPJ.replace('.', '');
        strCNPJ = strCNPJ.replace('-', '');
        strCNPJ = strCNPJ.replace('/', '');

        if (strCNPJ.length < 14 && strCNPJ.length < 15) {
            return false;
        }

        let digitosIguais;
        digitosIguais = 1;

        for (let i = 0; i < strCNPJ.length - 1; i++) {
            if (strCNPJ.charAt(i) != strCNPJ.charAt(i + 1)) {
                digitosIguais = 0;
                break;
            }
        }

        if (digitosIguais) {
            return false;
        }

        return isValidCNPJ(strCNPJ);
    }

    function isValidCNPJ(strCNPJ) {
        let numeros, soma, i, resultado, pos, tamanho;

        tamanho = strCNPJ.length - 2
        numeros = strCNPJ.substring(0, tamanho);
        const digitos = strCNPJ.substring(tamanho);
        soma = 0;
        pos = tamanho - 7;
        for (i = tamanho; i >= 1; i--) {
            soma += numeros.charAt(tamanho - i) * pos--;
            if (pos < 2) {
                pos = 9;
            }
        }

        resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
        if (resultado != digitos.charAt(0)) {
            return false;
        }

        tamanho = tamanho + 1;
        numeros = strCNPJ.substring(0, tamanho);
        soma = 0;
        pos = tamanho - 7;
        for (i = tamanho; i >= 1; i--) {
            soma += numeros.charAt(tamanho - i) * pos--;
            if (pos < 2) {
                pos = 9;
            }
        }

        resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
        return resultado == digitos.charAt(1);
    }

    CPv1Ticket.execFormatDocument = function () {
        var v_obj = this;

        setTimeout(function () {
            v_obj.value = CPv1Ticket.formatDocument(v_obj.value);
        }, 1);

    }

    CPv1Ticket.formatDocument = function (v) {
        //Remove tudo o que não é dígito
        v = v.replace(/\D/g, "")

        if (document.querySelector(CPv1Ticket.selectors.radioTypeFisica).checked) { //CPF

            //Coloca um ponto entre o terceiro e o quarto dígitos
            v = v.replace(/(\d{3})(\d)/, "$1.$2");

            //Coloca um ponto entre o terceiro e o quarto dígitos
            //de novo (para o segundo bloco de números)
            v = v.replace(/(\d{3})(\d)/, "$1.$2");

            //Coloca um hífen entre o terceiro e o quarto dígitos
            v = v.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
        } else { //CNPJ

            //Coloca ponto entre o segundo e o terceiro dígitos
            v = v.replace(/^(\d{2})(\d)/, "$1.$2");

            //Coloca ponto entre o quinto e o sexto dígitos
            v = v.replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3");

            //Coloca uma barra entre o oitavo e o nono dígitos
            v = v.replace(/\.(\d{3})(\d)/, ".$1/$2");

            //Coloca um hífen depois do bloco de quatro dígitos
            v = v.replace(/(\d{4})(\d)/, "$1-$2");
        }

        return v
    }

    CPv1Ticket.Initialize = function (site_id, coupon_mode, discount_action_url, payer_email) {
        // Sets.
        CPv1Ticket.site_id = site_id;
        CPv1Ticket.coupon_of_discounts.default = coupon_mode;
        CPv1Ticket.coupon_of_discounts.discount_action_url = discount_action_url;
        CPv1Ticket.coupon_of_discounts.payer_email = payer_email;

        // flow: CPB
        if (CPv1Ticket.site_id == "CPB") {
            CPv1Ticket.actionsMLB();
        }
    }

    CPv1Ticket.getAmountWithoutDiscount = function () {
        return document.querySelector(CPv1Ticket.selectors.amount).value;
    }

    CPv1Ticket.getAmount = function () {
        return document.querySelector(CPv1Ticket.selectors.amount).value - document.querySelector(CPv1Ticket.selectors.discount).value;
    }

    CPv1Ticket.getDocTypeSelected = function () {
        var docType = document.querySelector(CPv1Ticket.selectors.docTypeFisica).value;
        if (document.querySelector(CPv1Ticket.selectors.docTypeJuridica).checked) {
            docType = document.querySelector(CPv1Ticket.selectors.docTypeJuridica).value;
        }

        return docType;
    }

    this.CPv1Ticket = CPv1Ticket;

}).call();
