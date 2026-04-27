const klix_settings = window.klixPaymentData || {};
const { createElement, useState, useEffect } = window.wp.element;
const { __ } = window.wp.i18n;
const { select } = window.wp.data;

const Content = (props) => {
    const { eventRegistration = {}, emitResponse = {} } = props || {};
    const { onPaymentProcessing, onPaymentSetup } = eventRegistration;

    const payment_methods = klix_settings.payment_methods || {};
    const countries = payment_methods.country_names || {};
    const logos = payment_methods.logos || {};

    // Sort and order groups
    let paymentMethodGroups = payment_methods.payment_method_groups || [];
    const desiredOrder = ['bank_transfer', 'klix_card', 'klix_pay_later'];
    paymentMethodGroups = desiredOrder
        .map(name => paymentMethodGroups.find(group => group.name === name))
        .filter(Boolean);

    // Sort countries with "any" last
    const otherEntry = countries.any ? [['any', __( countries.any, 'klix-payments' )]] : [];
    const entries = Object.entries(countries).filter(([code]) => code !== 'any');
    entries.sort((a, b) => a[1].localeCompare(b[1]));
    const sortedCountries = [...entries, ...otherEntry];

    const defaultCountry = sortedCountries.length ? sortedCountries[0][0] : '';
    const [selectedCountry, setSelectedCountry] = useState(defaultCountry);
    const [selectedPaymentMethod, setSelectedPaymentMethod] = useState('klix-payments');

    useEffect(() => {
        const country=select('wc/store/cart').getCustomerData().shippingAddress.country

        if(country == null) {
            setSelectedCountry(defaultCountry);
        }
        else {
            setSelectedCountry(country);
        }
    }, []);

    // ✅ Register the payment processing callback
    useEffect(() => {
        const register = onPaymentSetup || onPaymentProcessing;
        if (!register) return;

        const unsubscribe = register(async () => {

            return {
                type: emitResponse.responseTypes.SUCCESS,
                meta: {
                    paymentMethodData: {
                        klix_selected_option: selectedPaymentMethod
                    }
                }
            };
        });

        return () => unsubscribe();
    }, [selectedPaymentMethod, selectedCountry, onPaymentProcessing, onPaymentSetup, emitResponse]);

    // Show description only if no countries available
    if (!sortedCountries.length) {
        return createElement('p', null, klix_settings.description || '');
    }

    // Combine country-specific + "any" payment methods
    const countryMethods = payment_methods.by_country?.[selectedCountry] || [];
    const anyMethods = payment_methods.by_country?.any || [];
    const allowedMethods = [...new Set([...countryMethods, ...anyMethods])];

    return createElement(
        'div',
        {
            style: {
                marginTop: '15px',
                display: 'flex',
                flexDirection: 'column',
                gap: '25px',
                fontFamily: 'system-ui, sans-serif',
                fontSize: '14px',
                color: '#1d1d1f'
            }
        },
        // Country selector
        createElement(
            'div',
            null,
            createElement(
                'label',
                {
                    htmlFor: 'klix-country-selector',
                    style: { fontWeight: '600', marginBottom: '6px', display: 'block' }
                },
                __('Select your country', 'klix-payments')
            ),
            createElement(
                'select',
                {
                    id: 'klix-country-selector',
                    value: selectedCountry,
                    onChange: (e) => setSelectedCountry(e.target.value),
                    style: {
                        padding: '10px',
                        width: '240px',
                        fontSize: '14px',
                        borderRadius: '4px',
                        border: '1px solid #ccc'
                    }
                },
                sortedCountries.map(([code, name]) =>
                    createElement('option', { key: code, value: code }, __( name, 'klix-payments' ))
                )
            )
        ),

        // Payment groups
        ...paymentMethodGroups.map(group => {
            const filteredMethods = group.methods.filter(method =>
                allowedMethods.includes(method)
            );
            if (!filteredMethods.length) return null;

            return createElement(
                'div',
                { key: group.name },

                // Group title
                createElement(
                    'p',
                    {
                        style: {
                            fontWeight: '600',
                            fontSize: '15px',
                            marginBottom: '10px',
                            marginTop: '10px'
                        }
                    },
                    group.label
                ),

                // Payment method grid
                createElement(
                    'div',
                    {
                        style: {
                            display: 'grid',
                            gridTemplateColumns: 'repeat(auto-fill, minmax(220px, 1fr))',
                            gap: '12px'
                        }
                    },
                    filteredMethods.map(method =>
                        createElement(
                            'label',
                            {
                                key: method,
                                style: {
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: '10px',
                                    border: '1px solid #ddd',
                                    borderRadius: '6px',
                                    padding: '10px',
                                    cursor: 'pointer',
                                    backgroundColor: selectedPaymentMethod === method ? '#f0f8ff' : '#fff',
                                    transition: 'background-color 0.2s ease-in-out'
                                }
                            },
                            createElement('input', {
                                type: 'radio',
                                name: 'klix_method',
                                value: method,
                                checked: selectedPaymentMethod === method,
                                onChange: () => {
                                    setSelectedPaymentMethod(method);
                                    window.klixSelectedMethod = method; // For debugging
                                },
                                style: {
                                    margin: 0,
                                    accentColor: '#007cba'
                                }
                            }),
                            logos?.[method]
                                ? createElement('img', {
                                    src: `https://portal.klix.app${logos[method]}`,
                                    alt: method,
                                    style: { height: '20px', width: 'auto' }
                                })
                                : null,
                            createElement(
                                'span',
                                null,
                                payment_methods.names?.[method] || method
                            )
                        )
                    )
                )
            );
        })
    );
};

// ✅ Register the payment method
try {
    window.wc.wcBlocksRegistry.registerPaymentMethod({
        name: 'klix-payments',
        label: __( 'Klix payments', 'klix-payments' ),
        content: createElement(Content),
        edit: createElement(Content),
        canMakePayment: () => {
            return true;
        },
        ariaLabel: __( 'Klix payments', 'klix-payments' ),
        supports: {
            features: klix_settings.supports || ['products'],
        }
    });
} catch (error) {
    console.error('Error registering Klix payment method:', error);
}
