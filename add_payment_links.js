
cj(function ($) {
    'use strict';

    $('.transaction-id').each(
        (i, el) => {
            linkToStripe($, el);
        });

    // Select the node that will be observed for mutations
    const targetNode = document.getElementsByTagName('body')[0]; // document.getElementsByClassName('CRM_Contribute_Form_Search')[0];

    listenForPayments($, targetNode);

});


function linkToStripe($, el) {
    var stripe_section = '';

    var text = $(el).text();
    if (text.startsWith('ch_')) {
        stripe_section = 'payments';
    } else if (text.startsWith('sub_')) {
        stripe_section = 'subscriptions'
    } else {
        return
    }

    $(el).html(
        '<a target=_new href="https://dashboard.stripe.com/'
        + stripe_section
        + '/'
        + $(el).text()
        + '">View in Stripe</a>'
    );
}

function listenForPayments(jQuery, targetNode) {

    // Options for the observer (which mutations to observe)
    const config = { attributes: true, childList: true, subtree: true };

    const callback = function (mutationsList, observer) {
        for (const mutation of mutationsList) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(node => {
                    jQuery(node).find('.transaction-id').each(
                        (i, el) => {
                            linkToStripe(jQuery, el);
                        });
                });
            }
            console.log("heard " + mutation.type);
        }
    };

    // Create an observer instance linked to the callback function
    const observer = new MutationObserver(callback);

    // Start observing the target node for configured mutations
    observer.observe(targetNode, config);

    return observer;
}
