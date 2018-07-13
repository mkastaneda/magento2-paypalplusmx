/**
 * MMDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDMMM
 * MDDDDDDDDDDDDDNNDDDDDDDDDDDDDDDDD=.DDDDDDDDDDDDDDDDDDDDDDDMM
 * MDDDDDDDDDDDD===8NDDDDDDDDDDDDDDD=.NDDDDDDDDDDDDDDDDDDDDDDMM
 * DDDDDDDDDN===+N====NDDDDDDDDDDDDD=.DDDDDDDDDDDDDDDDDDDDDDDDM
 * DDDDDDD%DN=8DDDDDD=~~~DDDDDDDDDND=.NDDDDDNDNDDDDDDDDDDDDDDDM
 * DDDDDDD+===NDDDDDDDDN~~N........8%........D ........DDDDDDDM
 * DDDDDDD+=D+===NDDDDDN~~N.?DDDDDDDDDDDDDD:.D .DDDDD .DDDDDDDN
 * DDDDDDD++DDDN===DDDDD~~N.?DDDDDDDDDDDDDD:.D .DDDDD .DDDDDDDD
 * DDDDDDD++DDDDD==DDDDN~~N.?DDDDDDDDDDDDDD:.D .DDDDD .DDDDDDDN
 * DDDDDDD++DDDDD==DDDDD~~N.... ...8%........D ........DDDDDDDM
 * DDDDDDD%===8DD==DD~~~~DDDDDDDDN.IDDDDDDDDDDDNDDDDDDNDDDDDDDM
 * NDDDDDDDDD===D====~NDDDDDD?DNNN.IDNODDDDDDDDN?DNNDDDDDDDDDDM
 * MDDDDDDDDDDDDD==8DDDDDDDDDDDDDN.IDDDNDDDDDDDDNDDNDDDDDDDDDMM
 * MDDDDDDDDDDDDDDDDDDDDDDDDDDDDDN.IDDDDDDDDDDDDDDDDDDDDDDDDDMM
 * MMDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDMMM
 *
 * @author Néstor Alain <alain@qbo.tech>
 * @category qbo
 * @package Qbo\PayPalPlusMx\
 * @copyright qbo (http://www.qbo.tech)
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * © 2016 QBO DIGITAL SOLUTIONS.
 *
 */

define([
    'underscore',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/payment/method-list',
    'Magento_Checkout/js/action/select-payment-method'
], function (_, quote, methodList, selectPaymentMethod) {
    'use strict';
    
    var paypalPlusMethodCode= "qbo_paypalplusmx";
    
    return function (targetModule) {
        
        targetModule.setPaymentMethods = function (methods) {
            /**
             * Overwrite methods to exclude PayPalPlus 
             * so it gets reloaded on apply coupon/credit 
             * 
             * This code was originally implemeneted on M2.2. 
             * It needs to be overrided because the iframe needs to make 
             * an API call to /paypalplus/payment in order the update payment details 
             * on PayPal's API and reload itself with the new totals.
             * 
             */
            targetModule.methodNames = _.pluck(methods, 'method');
            _.map(methodList(), function (existingMethod) {
                var existingMethodIndex = targetModule.methodNames.indexOf(existingMethod.method);
                if (existingMethodIndex !== -1 && existingMethod.method !== paypalPlusMethodCode) {
                    methods[existingMethodIndex] = existingMethod;
                }
            });

            methodList(methods);
        }
        return targetModule;
    };
});