jQuery(function($){
  $('.variations_form').on('found_variation', function(event, variation) {
    let price = variation.display_price * 100;
    const existingWidget = document.querySelector('klix-pay-later');
    if (!existingWidget) {
        return;
    }
    const brandId = existingWidget.getAttribute('brand_id');
    const language = existingWidget.getAttribute('language');
    const container = existingWidget.parentNode || document.body;
    existingWidget.remove();
    const newWidget = document.createElement('klix-pay-later');
    newWidget.setAttribute('amount', price);
    newWidget.setAttribute('brand_id', brandId);
    newWidget.setAttribute('language', language);
    container.appendChild(newWidget);
    });
});
