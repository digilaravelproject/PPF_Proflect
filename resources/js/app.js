import './bootstrap';

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.passwordToggle);
        if (!input) return;
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        button.setAttribute('aria-pressed', String(show));
    });
});

document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const open = document.body.classList.toggle('sidebar-open');
        document.querySelector('.menu-button')?.setAttribute('aria-expanded', String(open));
    });
});

if (document.querySelector('.sidebar') && !document.querySelector('.menu-button')) {
    const menuButton = document.createElement('button');
    menuButton.type = 'button';
    menuButton.className = 'menu-button';
    menuButton.setAttribute('aria-label', 'Open menu');
    menuButton.textContent = '☰';
    menuButton.addEventListener('click', () => document.body.classList.toggle('sidebar-open'));
    document.querySelector('.topbar')?.prepend(menuButton);

    const scrim = document.createElement('div');
    scrim.className = 'sidebar-scrim';
    scrim.addEventListener('click', () => document.body.classList.remove('sidebar-open'));
    document.body.append(scrim);
}

document.querySelectorAll('[data-payment-button]').forEach((button) => {
    button.addEventListener('click', async () => {
        const errorBox = document.getElementById('payment-error');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        const originalText = button.innerHTML;
        const showError = (message) => {
            errorBox.textContent = message;
            errorBox.hidden = false;
            button.disabled = false;
            button.innerHTML = originalText;
        };

        button.disabled = true;
        button.textContent = 'Preparing secure checkout…';
        errorBox.hidden = true;

        try {
            if (!window.Razorpay) throw new Error('The secure payment window could not load. Please check your connection and try again.');

            const orderResponse = await fetch(button.dataset.orderUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
                body: JSON.stringify({plan_id: Number(button.dataset.planId)}),
            });
            const order = await orderResponse.json();
            if (!orderResponse.ok) throw new Error(order.message || 'Unable to prepare the payment.');

            const checkout = new window.Razorpay({
                key: order.key,
                amount: order.amount,
                currency: order.currency,
                name: 'Proflect',
                description: 'PPF Replacement Program',
                order_id: order.order_id,
                prefill: {name: button.dataset.customerName, email: button.dataset.customerEmail},
                theme: {color: '#111820'},
                modal: {ondismiss: () => { button.disabled = false; button.innerHTML = originalText; }},
                handler: async (response) => {
                    button.textContent = 'Confirming payment…';
                    const verifyResponse = await fetch(button.dataset.verifyUrl, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
                        body: JSON.stringify(response),
                    });
                    const result = await verifyResponse.json();
                    if (!verifyResponse.ok) return showError(result.message || 'Payment verification failed.');
                    window.location.assign(result.redirect);
                },
            });
            checkout.on('payment.failed', (response) => showError(response.error?.description || 'Payment was not completed.'));
            checkout.open();
        } catch (error) {
            showError(error.message || 'Unable to open Razorpay checkout.');
        }
    });
});
