const klix_settings = window.klixPaymentData || {};
const label = window.wp.htmlEntities.decodeEntities(klix_settings.title) || window.wp.i18n.__('Klix', 'klix-payments');
const Content = () => {
    return window.wp.htmlEntities.decodeEntities(klix_settings.description || '');
};
const Block_Gateway = {
    name: 'klix-payments',
    label: label,
    content: Object(window.wp.element.createElement)(Content, null),
    edit: Object(window.wp.element.createElement)(Content, null),
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: klix_settings.supports,
    }
};
window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway);
