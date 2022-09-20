// CPv1
// Handlers Form Unlimint v1

const INLINE_BLOCK_FOR_PIX = 'inline-block';

(function () {
    var CPv1Pix = {
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
            "couponCodePix",
            "applyCouponPix"
        ],
        inputs_to_validate_pix: [
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
            couponCode: "#couponCodePix",
            applyCoupon: "#applyCouponPix",
            mpCouponApplyed: "#mpCouponApplyedPix",
            mpCouponError: "#mpCouponErrorPix",
            campaign_id: "#campaign_idPix",
            campaign: "#campaignPix",
            discount: "#discountPix",

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
            amount: "#amountPix",

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
            formCoupon: '#cardpay-form-coupon-Pix',
            box_loading: "#mp-box-loading",
            submit: "#btnSubmit",
            form: "#cardpay-Pix-form-general"
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

    CPv1Pix.addListenerEvent = function (el, eventName, handler) {
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

    CPv1Pix.referer = (function () {
        return window.location.protocol + "//" +
            window.location.hostname + (window.location.port ? ":" + window.location.port : "");
    })();

    CPv1Pix.AJAX = function (options) {
        const useXDomain = !!window.XDomainRequest;
        const req = useXDomain ? new XDomainRequest() : new XMLHttpRequest()

        options.url += (options.url.indexOf("?") >= 0 ? "&" : "?") + "referer=" + escape(CPv1Pix.referer);
        options.requestedMethod = options.method;
        if (useXDomain && String(options.method) === "PUT") {
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

    var doSubmitPix = false;

    CPv1Pix.doPay = function (febraban) {
        document.querySelector(CPv1Pix.febraban.firstname).value = febraban['firstname'];
        document.querySelector(CPv1Pix.febraban.lastname).value = febraban['lastname'];
        document.querySelector(CPv1Pix.febraban.docNumber).value = febraban['docNumber'];
        document.querySelector(CPv1Pix.febraban.address).value = febraban['address'];
        document.querySelector(CPv1Pix.febraban.number).value = febraban['number'];
        document.querySelector(CPv1Pix.febraban.city).value = febraban['city'];
        document.querySelector(CPv1Pix.febraban.state).value = febraban['state'];
        document.querySelector(CPv1Pix.febraban.zipcode).value = febraban['zipcode'];
        if (!doSubmitPix) {
            document.querySelector(CPv1Pix.selectors.box_loading).style.background = "url(" + CPv1Pix.paths.loading + ") 0 50% no-repeat #fff";
            const btn = document.querySelector(CPv1Pix.selectors.form);
            btn.submit();
        }
    }

    CPv1Pix.getForm = function () {
        return document.querySelector(CPv1Pix.selectors.form);
    }

    CPv1Pix.addListenerEvent = function (el, eventName, handler) {
        if (el.addEventListener) {
            el.addEventListener(eventName, handler);
        } else {
            el.attachEvent('on' + eventName, function () {
                handler.call(el);
            });
        }

    };

    // Show/hide errors.

    CPv1Pix.showErrors = function (fields) {
        var $form = CPv1Pix.getForm();
        for (var x = 0; x < fields.length; x++) {
            var f = fields[x];
            var $span = $form.querySelector('#error_' + f);
            var $input = $form.querySelector($span.getAttribute("data-main"));
            $span.style.display = INLINE_BLOCK_FOR_PIX;
            $input.classList.add("mp-error-input");
        }
    }

    CPv1Pix.hideErrors = function () {
        for (let x = 0; x < document.querySelectorAll('[data-checkout]').length; x++) {
            const $field = document.querySelectorAll('[data-checkout]')[x];
            $field.classList.remove("mp-error-input");
        }

        for (let y = 0; y < document.querySelectorAll('.erro_febraban').length; y++) {
            const $span = document.querySelectorAll('.erro_febraban')[y];
            $span.style.display = 'none';
        }
    }

    CPv1Pix.actionsMLB = function () {
        CPv1Pix.addListenerEvent(document.querySelector(CPv1Pix.selectors.docNumber), 'keyup', CPv1Pix.execFormatDocument);
        CPv1Pix.addListenerEvent(document.querySelector(CPv1Pix.selectors.radioTypeFisica), "change", CPv1Pix.initializeDocumentPessoaFisica);
        CPv1Pix.addListenerEvent(document.querySelector(CPv1Pix.selectors.radioTypeJuridica), "change", CPv1Pix.initializeDocumentPessoaJuridica);
    }

    CPv1Pix.initializeDocumentPessoaFisica = function () {

        // show elements
        document.querySelector(CPv1Pix.selectors.boxLastName).style.display = INLINE_BLOCK_FOR_PIX;
        document.querySelector(CPv1Pix.selectors.titleFirstName).style.display = INLINE_BLOCK_FOR_PIX;
        document.querySelector(CPv1Pix.selectors.titleDocNumber).style.display = INLINE_BLOCK_FOR_PIX;

        // adjustment css
        document.querySelector(CPv1Pix.selectors.boxFirstName).classList.remove("form-col-12");
        document.querySelector(CPv1Pix.selectors.boxFirstName).classList.add("form-col-6");

        // hide elements
        document.querySelector(CPv1Pix.selectors.titleFirstNameRazaoSocial).style.display = 'none';
        document.querySelector(CPv1Pix.selectors.titleDocNumberCNPJ).style.display = 'none';

        // force max length CPF
        document.querySelector(CPv1Pix.selectors.docNumber).maxLength = 14;
    }

    CPv1Pix.initializeDocumentPessoaJuridica = function () {

        // show elements
        document.querySelector(CPv1Pix.selectors.titleFirstNameRazaoSocial).style.display = INLINE_BLOCK_FOR_PIX;
        document.querySelector(CPv1Pix.selectors.titleDocNumberCNPJ).style.display = INLINE_BLOCK_FOR_PIX;

        // adjustment css
        document.querySelector(CPv1Pix.selectors.boxFirstName).classList.remove("form-col-6");
        document.querySelector(CPv1Pix.selectors.boxFirstName).classList.add("form-col-12");

        // Hide Elements
        document.querySelector(CPv1Pix.selectors.boxLastName).style.display = 'none';
        document.querySelector(CPv1Pix.selectors.titleFirstName).style.display = 'none';
        document.querySelector(CPv1Pix.selectors.titleDocNumber).style.display = 'none';

        // force max length CNPJ
        document.querySelector(CPv1Pix.selectors.docNumber).maxLength = 18;
    }

    CPv1Pix.validaCPF = function (strCPF) {
        var Soma;
        var Resto;
        strCPF = String(strCPF.replace(/[.-\s]/g, ''))
        Soma = 0;

        if (strCPF === "00000000000") {
            return false;
        }

        for (let i = 1; i <= 9; i++) {
            Soma = Soma + parseInt(strCPF.substring(i - 1, i)) * (11 - i);
        }

        Resto = parseInt((Soma * 10) % 11);

        if ((Resto === 10) || (Resto === 11)) {
            Resto = 0;
        }

        if (Resto !== parseInt(strCPF.substring(9, 10))) {
            return false;
        }

        Soma = 0;
        for (let i = 1; i <= 10; i++) {
            Soma = Soma + parseInt(strCPF.substring(i - 1, i)) * (12 - i);
        }

        Resto = (Soma * 10) % 11;

        if ((Resto === 10) || (Resto === 11)) {
            Resto = 0;
        }

        return Resto === parseInt(strCPF.substring(10, 11));
    }

    CPv1Pix.validaCNPJ = function (strCNPJ) {
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
            if (strCNPJ.charAt(i) !== strCNPJ.charAt(i + 1)) {
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

        resultado = String(soma % 11 < 2 ? 0 : 11 - soma % 11);
        if (resultado !== digitos.charAt(0)) {
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
        return resultado === digitos.charAt(1);
    }

    CPv1Pix.execFormatDocument = function () {
        var v_obj = this;

        setTimeout(function () {
            v_obj.value = CPv1Pix.formatDocument(v_obj.value);
        }, 1);

    }

    CPv1Pix.formatDocument = function (v) {
        //Remove tudo o que não é dígito
        v = v.replace(/\D/g, "")

        if (document.querySelector(CPv1Pix.selectors.radioTypeFisica).checked) { //CPF

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

    CPv1Pix.Initialize = function (site_id, coupon_mode, discount_action_url, payer_email) {
        // Sets.
        CPv1Pix.site_id = site_id;
        CPv1Pix.coupon_of_discounts.default = coupon_mode;
        CPv1Pix.coupon_of_discounts.discount_action_url = discount_action_url;
        CPv1Pix.coupon_of_discounts.payer_email = payer_email;

        // flow: CPB
        if (String(CPv1Pix.site_id) === "CPB") {
            CPv1Pix.actionsMLB();
        }
    }

    CPv1Pix.getAmountWithoutDiscount = function () {
        return document.querySelector(CPv1Pix.selectors.amount).value;
    }

    CPv1Pix.getAmount = function () {
        return document.querySelector(CPv1Pix.selectors.amount).value - document.querySelector(CPv1Pix.selectors.discount).value;
    }

    CPv1Pix.getDocTypeSelected = function () {
        var docType = document.querySelector(CPv1Pix.selectors.docTypeFisica).value;
        if (document.querySelector(CPv1Pix.selectors.docTypeJuridica).checked) {
            docType = document.querySelector(CPv1Pix.selectors.docTypeJuridica).value;
        }

        return docType;
    }

    this.CPv1Pix = CPv1Pix;

}).call();
