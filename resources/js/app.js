import './bootstrap';

document.querySelectorAll('[data-landing-nav-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const open = document.body.classList.toggle('landing-nav-open');
        button.setAttribute('aria-expanded', String(open));
    });
});

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

document.querySelectorAll('[data-customer-nav-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const open = document.body.classList.toggle('customer-nav-open');
        button.setAttribute('aria-expanded', String(open));
    });
});

document.querySelectorAll('[data-profile-menu]').forEach((menu) => {
    const toggle = menu.querySelector('[data-profile-toggle]');
    toggle?.addEventListener('click', (event) => {
        event.stopPropagation();
        const open = menu.classList.toggle('open');
        toggle.setAttribute('aria-expanded', String(open));
    });
    document.addEventListener('click', (event) => {
        if (!menu.contains(event.target)) {
            menu.classList.remove('open');
            toggle?.setAttribute('aria-expanded', 'false');
        }
    });
});

document.querySelectorAll('[data-notification-menu]').forEach((menu) => {
    const toggle = menu.querySelector('[data-notification-toggle]');
    toggle?.addEventListener('click', async (event) => {
        event.stopPropagation();
        const open = menu.classList.toggle('open');
        toggle.setAttribute('aria-expanded', String(open));
        if (open && menu.querySelector('[data-notification-badge]')) {
            try {
                await fetch(menu.dataset.readUrl, {method: 'POST', headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content}});
                menu.querySelector('[data-notification-badge]')?.remove();
                menu.querySelectorAll('.notification-item.unread').forEach((item) => item.classList.remove('unread'));
                const unread = menu.querySelector('.notification-dropdown header small');
                if (unread) unread.textContent = '0 unread';
            } catch (_) {
                // Reading the list still works if acknowledgement is temporarily unavailable.
            }
        }
    });
    document.addEventListener('click', (event) => {
        if (!menu.contains(event.target)) {
            menu.classList.remove('open');
            toggle?.setAttribute('aria-expanded', 'false');
        }
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

document.querySelectorAll('[data-claim-wizard]').forEach((wizard) => {
    let step = 1;
    const form = wizard.querySelector('form');
    const panelInputs = [...wizard.querySelectorAll('[data-panel-input]')];
    const photoInput = wizard.querySelector('[data-photo-input]');
    const description = wizard.querySelector('textarea[name="description"]');
    const vehicleMake = wizard.querySelector('[name="vehicle_make"]');
    const vehicleModel = wizard.querySelector('[name="vehicle_model"]');
    const registration = wizard.querySelector('[name="registration_number"]');
    const vehicleYear = wizard.querySelector('[name="vehicle_year"]');
    const warrantyCode = wizard.querySelector('[name="warranty_code"]');
    const warrantyControl = wizard.querySelector('[data-warranty-control]');
    const warrantyFeedback = wizard.querySelector('[data-warranty-feedback]');
    const maximumVehicleYear = Number(form.dataset.maximumVehicleYear);
    let warrantyState = 'unchecked';
    let warrantyTimer;
    let warrantyRequest = 0;
    const errorBox = document.createElement('div');
    errorBox.className = 'alert admin-error wizard-error';
    errorBox.setAttribute('role', 'alert');
    errorBox.hidden = true;
    form.prepend(errorBox);

    const showWizardError = (message, input = null) => {
        errorBox.textContent = message;
        errorBox.hidden = false;
        input?.focus();
        errorBox.scrollIntoView({behavior: 'smooth', block: 'center'});
    };

    const clearWizardError = () => {
        errorBox.hidden = true;
        errorBox.textContent = '';
    };

    const setWarrantyFeedback = (state, message) => {
        warrantyState = state;
        warrantyFeedback.textContent = message;
        warrantyFeedback.className = `warranty-feedback ${state}`;
        warrantyControl.classList.toggle('is-valid', state === 'valid');
        warrantyControl.classList.toggle('is-invalid', state === 'invalid');
        warrantyControl.classList.toggle('is-checking', state === 'checking');
    };

    const checkWarrantyCode = async () => {
        const code = warrantyCode.value.trim().toUpperCase();
        warrantyCode.value = code;
        const currentRequest = ++warrantyRequest;
        if (!code) {
            setWarrantyFeedback('unchecked', '');
            return;
        }
        if (!/^CLM-[0-9]{6}-[A-Z0-9]{6}$/.test(code)) {
            setWarrantyFeedback('invalid', 'Enter a valid warranty code in the format CLM-260901-ZM9JVS.');
            return;
        }

        setWarrantyFeedback('checking', 'Checking warranty code…');
        try {
            const response = await fetch(form.dataset.warrantyCheckUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content},
                body: JSON.stringify({warranty_code: code}),
            });
            const result = await response.json();
            if (currentRequest !== warrantyRequest) return;
            if (!response.ok) throw new Error(result.message || 'Warranty code could not be checked.');
            setWarrantyFeedback(result.valid ? 'valid' : 'invalid', result.message);
        } catch (error) {
            if (currentRequest !== warrantyRequest) return;
            setWarrantyFeedback('invalid', error.message || 'Warranty code could not be checked. Please try again.');
        }
    };

    warrantyCode?.addEventListener('input', () => {
        warrantyCode.value = warrantyCode.value.toUpperCase();
        clearTimeout(warrantyTimer);
        const code = warrantyCode.value.trim();
        if (!code) setWarrantyFeedback('unchecked', '');
        else if (!/^CLM-[0-9]{6}-[A-Z0-9]{6}$/.test(code)) setWarrantyFeedback('invalid', 'Enter a valid warranty code in the format CLM-260901-ZM9JVS.');
        else setWarrantyFeedback('checking', 'Checking warranty code…');
        warrantyTimer = setTimeout(checkWarrantyCode, 350);
    });
    if (warrantyCode?.value) checkWarrantyCode();

    const validateVehicle = () => {
        if (warrantyState !== 'valid') {
            if (warrantyState === 'unchecked') setWarrantyFeedback('invalid', 'Enter a valid and unused warranty code before continuing.');
            warrantyCode.focus();
            warrantyFeedback.scrollIntoView({behavior: 'smooth', block: 'center'});
            return false;
        }
        const labels = {vehicle_make: 'vehicle make', vehicle_model: 'vehicle model', registration_number: 'registration number'};
        const missing = [vehicleMake, vehicleModel, registration].find((input) => !input.value.trim());
        if (missing) {
            showWizardError(`Please enter the ${labels[missing.name]}.`, missing);
            return false;
        }
        if (vehicleYear.value) {
            const year = Number(vehicleYear.value);
            if (!Number.isInteger(year) || year < 1950 || year > maximumVehicleYear) {
                showWizardError(`Enter a model year between 1950 and ${maximumVehicleYear}.`, vehicleYear);
                return false;
            }
        }
        if (!panelInputs.some((input) => input.checked)) {
            showWizardError('Select at least one damaged panel.');
            return false;
        }
        clearWizardError();
        return true;
    };

    const validatePhotos = () => {
        const count = photoInput.files?.length || 0;
        if (count < 1 || count > 6) {
            showWizardError('Choose between 1 and 6 damage photos.', photoInput);
            return false;
        }
        clearWizardError();
        return true;
    };

    const showStep = (nextStep) => {
        step = nextStep;
        wizard.querySelectorAll('[data-step]').forEach((section) => section.classList.toggle('active', Number(section.dataset.step) === step));
        wizard.querySelectorAll('[data-step-target]').forEach((button) => {
            const target = Number(button.dataset.stepTarget);
            button.classList.toggle('active', target === step);
            button.classList.toggle('done', target < step);
        });
        window.scrollTo({top: wizard.offsetTop - 20, behavior: 'smooth'});
    };

    const syncPanels = () => {
        panelInputs.forEach((input) => wizard.querySelector(`[data-panel-shape="${input.value}"]`)?.classList.toggle('selected', input.checked));
    };

    wizard.querySelectorAll('[data-panel-shape]').forEach((shape) => shape.addEventListener('click', () => {
        const input = wizard.querySelector(`[data-panel-input][value="${shape.dataset.panelShape}"]`);
        if (input) {
            input.checked = !input.checked;
            syncPanels();
        }
    }));
    panelInputs.forEach((input) => input.addEventListener('change', syncPanels));

    const renderPhotos = () => {
        const preview = wizard.querySelector('[data-photo-previews]');
        preview.innerHTML = '';
        [...(photoInput.files || [])].slice(0, 6).forEach((file, index) => {
            const item = document.createElement('div');
            item.className = 'photo-preview-item';
            const image = document.createElement('img');
            image.alt = file.name;
            image.src = URL.createObjectURL(file);
            image.onload = () => URL.revokeObjectURL(image.src);
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'photo-remove';
            remove.setAttribute('aria-label', `Remove ${file.name}`);
            remove.title = 'Remove photo';
            remove.textContent = '×';
            remove.addEventListener('click', () => {
                const transfer = new DataTransfer();
                [...photoInput.files].forEach((current, currentIndex) => {
                    if (currentIndex !== index) transfer.items.add(current);
                });
                photoInput.files = transfer.files;
                renderPhotos();
            });
            item.append(image, remove);
            preview.appendChild(item);
        });
    };
    photoInput?.addEventListener('change', renderPhotos);

    const review = () => {
        const selected = panelInputs.filter((input) => input.checked);
        const list = wizard.querySelector('[data-review-panels]');
        list.innerHTML = '';
        selected.forEach((input) => {
            const item = document.createElement('li');
            item.textContent = input.nextElementSibling?.textContent || input.value;
            list.appendChild(item);
        });
        const count = photoInput.files?.length || 0;
        wizard.querySelector('[data-review-photos]').textContent = `${count} photo${count === 1 ? '' : 's'} ready to submit`;
        wizard.querySelector('[data-review-description]').textContent = description.value.trim() || 'No additional details provided.';
        wizard.querySelector('[data-review-vehicle]').textContent = `${vehicleMake.value.trim()} ${vehicleModel.value.trim()}`;
        wizard.querySelector('[data-review-registration]').textContent = registration.value.trim().toUpperCase();
        const photoStrip = wizard.querySelector('[data-review-photo-strip]');
        photoStrip.innerHTML = '';
        [...(photoInput.files || [])].forEach((file) => {
            const image = document.createElement('img');
            image.alt = file.name;
            image.src = URL.createObjectURL(file);
            image.onload = () => URL.revokeObjectURL(image.src);
            photoStrip.appendChild(image);
        });
    };

    wizard.querySelectorAll('[data-next]').forEach((button) => button.addEventListener('click', () => {
        if (step === 1) {
            if (!validateVehicle()) return;
        }
        if (step === 2) {
            if (!validatePhotos()) return;
            review();
        }
        showStep(Math.min(3, step + 1));
    }));
    wizard.querySelectorAll('[data-prev]').forEach((button) => button.addEventListener('click', () => showStep(Math.max(1, step - 1))));
    wizard.querySelectorAll('[data-step-target]').forEach((button) => button.addEventListener('click', () => {
        const target = Number(button.dataset.stepTarget);
        if (target < step) showStep(target);
    }));
    form?.addEventListener('submit', (event) => {
        if (!validateVehicle()) {
            event.preventDefault();
            showStep(1);
            return;
        }
        if (!validatePhotos()) {
            event.preventDefault();
            showStep(2);
            return;
        }
        form.querySelector('button[type="submit"]')?.setAttribute('disabled', 'disabled');
    });
    syncPanels();
});
