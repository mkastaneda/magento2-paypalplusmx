/**
 * 
 * /**
 * MMDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDMMM
 * MDDDDDDDDDDDDDNNDDDDDDDDDDDDDDDDD=.DDDDDDDDDDDDDDDDDDDDDDDMM
 * MDDDDDDDDDDDD===8NDDDDDDDDDDDDDDD=.NDDDDDDDDDDDDDDDDDDDDDDMM
 * DDDDDDDDDN===+N====NDDDDDDDDDDDDD=.DDDDDDDDDDDDDDDDDDDDDDDDM
 * DDDDDDD$DN=8DDDDDD=~~~DDDDDDDDDND=.NDDDDDNDNDDDDDDDDDDDDDDDM
 * DDDDDDD+===NDDDDDDDDN~~N........8$........D ........DDDDDDDM
 * DDDDDDD+=D+===NDDDDDN~~N.?DDDDDDDDDDDDDD:.D .DDDDD .DDDDDDDN
 * DDDDDDD++DDDN===DDDDD~~N.?DDDDDDDDDDDDDD:.D .DDDDD .DDDDDDDD
 * DDDDDDD++DDDDD==DDDDN~~N.?DDDDDDDDDDDDDD:.D .DDDDD .DDDDDDDN
 * DDDDDDD++DDDDD==DDDDD~~N.... ...8$........D ........DDDDDDDM
 * DDDDDDD$===8DD==DD~~~~DDDDDDDDN.IDDDDDDDDDDDNDDDDDDNDDDDDDDM
 * NDDDDDDDDD===D====~NDDDDDD?DNNN.IDNODDDDDDDDN?DNNDDDDDDDDDDM
 * MDDDDDDDDDDDDD==8DDDDDDDDDDDDDN.IDDDNDDDDDDDDNDDNDDDDDDDDDMM
 * MDDDDDDDDDDDDDDDDDDDDDDDDDDDDDN.IDDDDDDDDDDDDDDDDDDDDDDDDDMM
 * MMDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDMMM
 *
 * @author José Castañeda <jose@qbo.tech>
 * @category qbo
 * @package qbo\PayPalPlusMx\
 * @copyright   qbo (http://www.qbo.tech)
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * 
 * © 2016 QBO DIGITAL SOLUTIONS. 
 *
 * qbo_PayPalPlusMx Magento JS component
 *
 */
/*browser:true*/
/*global define*/
define(
        [
            'Magento_Checkout/js/view/payment/default',
            'Magento_Paypal/js/model/iframe',
            'jquery',
            'Magento_Checkout/js/model/quote',
            'mage/storage',
            'Magento_Checkout/js/model/error-processor',
            'Magento_Checkout/js/model/full-screen-loader',
            'Magento_Checkout/js/model/postcode-validator'

        ],
        function (Component, iframe, $, quote, storage, errorProcesor, fullScreenLoader, postcodeValidator) {
            'use strict';

            return Component.extend({
                defaults: {
                    template: 'qbo_PayPalPlusMx/payment/paypalplusmx-form',
                    paymentReady: true
                },
                accessToken: false,
                isPaymentReady: false,
                payerId: false,
                paymentId: false,
                token: false,
                data: false,
                terms: false,
                minimumInstallmentAmount: 500,
                tokenizeServiceUrl: 'paypalplus/payment/cards',
                paymentApiServiceUrl: 'paypalplus/payment',
                errorProcessor: errorProcesor,
                customerData: quote.billingAddress._latestValue,
                /**
                 * Wait until "ppplus" div exists and API responds with payment data
                 * @returns {undefined}
                 */
                initialize: function () {

                    fullScreenLoader.startLoader();
                    this.initPayment();
                    this._super();
                    var self = this;
                    var iframeLoaded = setInterval(function ()
                    {
                        if ($('#ppplus').length && self.isPaymentReady)
                        {
                            if (!window.checkoutConfig.payment.paypalPlusIframe.api.isQuoteReady || window.checkoutConfig.payment.paypalPlusIframe.api.error) {
                                $('#iframe-warning').hide();
                                $('#iframe-error').show();
                                $('#continueButton').prop("disabled", true);
                                return false;
                            }
                            self.initializeIframe();
                            fullScreenLoader.stopLoader();
                            clearInterval(iframeLoaded);
                        }
                    }, 300);
                },
                /**
                 * Initialize PayPal Object Library
                 * @returns {undefined}
                 */
                initializeIframe: function () {
                    //Hide previous error messages
                    $('#iframe-warning').hide();
                    $('#iframe-error-email').hide();
                    
                    //Build Iframe
                    var self = this;
                    var mode = window.checkoutConfig.payment.paypalPlusIframe.config.isSandbox === "1" ? 'sandbox' : 'live';
                    var installmentsActive = window.checkoutConfig.quoteData.base_grand_total > self.minimumInstallmentAmount && Boolean(parseInt(window.checkoutConfig.payment.paypalPlusIframe.config.installments)) ? true : false;
                    var email = quote.guestEmail ? quote.guestEmail : window.checkoutConfig.customerData.email;
                    
                    if(!email && !$("#customer-email").val()) {
                        //This will happen if no shipping address is required and user adds a payment address before entering an email address
                        $('#iframe-warning').hide();
                        $('#iframe-error-email').show();
                        $('#continueButton').prop("disabled", true);
                        //Wait for the user to specify an email address.
                        $("#customer-email").on('focusout', function(){
                            self.initializeIframe();
                        });
                        return false;
                    }
                    /**
                     * Object script included in <head> 
                     * @see di.xml
                     */
                    this.paypalObject = PAYPAL.apps.PPP(
                            {
                                "approvalUrl": window.checkoutConfig.payment.paypalPlusIframe.api.actionUrl,
                                "placeholder": "ppplus",
                                "mode": mode,
                                "buttonLocation": "outside",
                                "preselection": "none",
                                "surcharging": false,
                                "hideAmount": false,
                                disableContinue: "continueButton",
                                enableContinue: "continueButton",
                                "language": window.checkoutConfig.payment.paypalPlusIframe.config.iframeLanguage,
                                "country": "MX",
                                "disallowRememberedCards": window.checkoutConfig.customerData.id && window.checkoutConfig.payment.paypalPlusIframe.config.save_cards_token ? false : true,
                                "rememberedCards": window.checkoutConfig.payment.paypalPlusIframe.api.card_token  ? window.checkoutConfig.payment.paypalPlusIframe.api.card_token : "1",
                                "useraction": "continue",
                                "payerEmail": email ? email : $("#customer-email").val(),
                                "payerPhone": window.checkoutConfig.payment.paypalPlusIframe.api.shippingData.telephone ? window.checkoutConfig.payment.paypalPlusIframe.api.shippingData.telephone : window.checkoutConfig.payment.paypalPlusIframe.api.billingData.telephone,
                                "payerFirstName": window.checkoutConfig.payment.paypalPlusIframe.api.shippingData.firstname ? window.checkoutConfig.payment.paypalPlusIframe.api.shippingData.firstname : window.checkoutConfig.payment.paypalPlusIframe.api.billingData.firstname,
                                "payerLastName": window.checkoutConfig.payment.paypalPlusIframe.api.shippingData.lastname ? window.checkoutConfig.payment.paypalPlusIframe.api.shippingData.lastname : window.checkoutConfig.payment.paypalPlusIframe.api.billingData.telephone,
                                "payerTaxId": "",
                                "payerTaxIdType": "",
                                "merchantInstallmentSelection": installmentsActive ? parseInt(window.checkoutConfig.payment.paypalPlusIframe.config.installments_months) : 1,
                                //Are installmets activated and order total is greater than $500 ? ($500 is because thats the minimum amount allowed by PayPal)
                                "merchantInstallmentSelectionOptional": installmentsActive,
                                "hideMxDebitCards": false,
                                "iframeHeight": window.checkoutConfig.payment.paypalPlusIframe.config.iframeHeight,
                                /**
                                 * Do stuff after iframe is loaded
                                 * @returns {undefined}
                                 */
                                onLoad: function () {
                                    console.log("Iframe successfully lo aded !");
                                },
                                /**
                                 * Continue after payment is verifies (continueButton)
                                 * 
                                 * @param {string} rememberedCards
                                 * @param {string} payerId
                                 * @param {string} token
                                 * @param {string} term
                                 * @returns {}
                                 */
                                onContinue: function (rememberedCardsToken, payerId, token, term) {
                                    $('#continueButton').hide();
                                    $('#payNowButton').show();
                                    var accessToken = window.checkoutConfig.payment.paypalPlusIframe.api.accessToken;
                                    var paymentId = window.checkoutConfig.payment.paypalPlusIframe.api.paymentId;

                                    self.accessToken = accessToken;
                                    self.paymentId = paymentId;
                                    self.payerId = payerId;
                                    //Show Place Order button

                                    var message = {
                                        message: $.mage.__('Payment has been authorized.')
                                    };
                                    self.messageContainer.addSuccessMessage(message);

                                    if (rememberedCardsToken &&
                                            window.checkoutConfig.customerData.id &&
                                            rememberedCardsToken !== window.checkoutConfig.payment.paypalPlusIframe.api.card_token)
                                    {
                                        self.tokenizeCards(rememberedCardsToken);
                                    }

                                    if (typeof term !== 'undefined') {
                                        self.term = term;
                                    }
                                    $('#ppplus').hide();
                                    
                                    //end aproved card and payment method, run placePendingOrder
                                    self.placePendingOrder();
                                },
                                /**
                                 * Handle iframe error (if payment fails for example)
                                 * 
                                 * @param {type} err
                                 * @returns {undefined}
                                 */
                                onError: function (err) {
                                    var message = {
                                        message: JSON.stringify(err.cause)
                                    };
                                    //Display response error
                                    //that.messageContainer.addErrorMessage(message); 
                                }
                            });
                },
                /**
                 * Call PayPal API to create payment
                 * 
                 * @returns {mage/storage}
                 */
                initPayment: function () {
                    var self = this;
                    return storage.get(
                            self.paymentApiServiceUrl
                            ).fail(
                            function (response) {
                                var payment = JSON.parse(response.responseText);
                                console.log(payment);
                                console.log("Payment Error:" + response);
                                 if(payment.reason){
                                        self.onPaymentError(payment.reason);
                                    }else{
                                        self.onPaymentError(null);
                                 }
                            }
                    ).done(
                            function (result) {
                                var payment = JSON.parse(result);
                                if (payment.isQuoteReady) {
                                    //Set payment data to window variable
                                    window.checkoutConfig.payment.paypalPlusIframe.api = payment;
                                    self.isPaymentReady = true;
                                } else {
                                    if (payment.reason) {
                                        self.onPaymentError(payment.reason);
                                    } else {
                                        self.onPaymentError(null);
                                    }
                                }
                            }
                    );
                },
                onPaymentError: function (reason) {
                    var iframeErrorElem = '#iframe-error';
                    if (reason) {
                        if (reason === 'payment_not_ready') {
                            iframeErrorElem = '#iframe-error-payment-not-ready';
                        } else {
                            $(iframeErrorElem).html('');
                            $(iframeErrorElem).append('<div><span>' + reason + '</span></div>');
                        }
                    }
                    $(iframeErrorElem).show();
                    $('#iframe-warning').hide();
                    $('#continueButton').prop("disabled", true);
                    fullScreenLoader.stopLoader();
                },
                /**
                 * Handle Continue button, verify shipping address is set
                 * 
                 * @returns {undefined}
                 */
                doContinue: function () {
                    var self = this;
                    if (this.validateAddress() !== false) {
                        self.paypalObject.doContinue();
                    } else {
                        var message = {
                            message: $.mage.__('Please verify shipping address.')
                        };
                        self.messageContainer.addErrorMessage(message);
                    }
                },
                /**
                 * Gather and set payment after payment is authorized.
                 * This data is sent to the Capture methos via ajax.
                 * @see qbo\PayPalPlusMgetDatax\Model\Payment
                 * 
                 * @returns {array}
                 */
                getData: function () {
                    var data = {
                        'method': this.getCode(),
                        'additional_data': {
                            'access_token': this.accessToken,
                            'payer_id': this.payerId,
                            'payment_id': this.paymentId,
                            'execute_url': window.checkoutConfig.payment.paypalPlusIframe.api  ? window.checkoutConfig.payment.paypalPlusIframe.api.executeUrl : "",
                            'handle_pending_payment': window.checkoutConfig.payment.paypalPlusIframe.config.status_pending,
                            'terms': this.term ? this.term : false
                        }
                    };

                    return data;
                },
                paypalObject: {},
                getCode: function () {
                    return 'qbo_paypalplusmx';
                },
                /**
                 * Places order (PayNow Button)
                 */
                placePendingOrder: function () {
                    var self = this;
                    if (this.placeOrder()) {
                        // capture all click events
                        document.addEventListener('click', iframe.stopEventPropagation, true);
                    }
                },
                /**
                 * Save credit card token.
                 * 
                 * @param {type} token
                 * @returns {unresolved}
                 */
                tokenizeCards: function (token) {
                    var self = this;
                    //self.messageContainer = new Messages();
                    var payload = JSON.stringify({
                        token_id: token
                    });
                    return storage.post(
                            this.tokenizeServiceUrl, payload, false
                            ).fail(
                            function (response) {
                                console.log("Failed saving cards:" + token);
                                //self.errorProcessor.process(response, self.messageContainer);
                                message: $.mage.__('An error ocurred while saving card.');
                            }
                    ).done(
                            function (result) {
                                console.log("Saved cards:" + JSON.stringify(result));
                                var message = {
                                    message: $.mage.__('Card successfully saved.')
                                };
                                //TODO: Let or not the user know about saved card before placing order ? Let merchant decide with config ?
                                //self.messageContainer.addSuccessMessage(message);
                            }
                    );
                },
                /**
                 * Validate shipping address.
                 * 
                 * @returns {Boolean}
                 */
                validateAddress: function () {

                    this.customerData = quote.billingAddress._latestValue;
                    
                    if (typeof this.customerData.city === 'undefined' || this.customerData.city.length === 0) {
                        return false;
                    }

                    if (typeof this.customerData.countryId === 'undefined' || this.customerData.countryId.length === 0) {
                        return false;
                    }

                    if (typeof this.customerData.postcode === 'undefined' || this.customerData.postcode.length === 0 || !postcodeValidator.validate(this.customerData.postcode, "MX")) {
                        return false;
                    }

                    if (typeof this.customerData.street === 'undefined' || this.customerData.street[0].length === 0 ) {
                        return false;
                    }
                    if (typeof this.customerData.region === 'undefined' || this.customerData.region.length === 0) {
                        return false;
                    }
                    return true;
                }
            });
        }
);


              