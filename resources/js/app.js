import './bootstrap';

const svgElement = (name) => document.createElementNS('http://www.w3.org/2000/svg', name);
const validPanelArea = (points) => Array.isArray(points) && points.length === 4 && points.every((point) =>
    point && typeof point === 'object' && Number.isFinite(Number(point.x)) && Number.isFinite(Number(point.y))
    && Number(point.x) >= 0 && Number(point.x) <= 100 && Number(point.y) >= 0 && Number(point.y) <= 100);
const areaPoints = (points) => points.map((point) => `${Number(point.x)},${Number(point.y)}`).join(' ');
const simplePanelArea = (points) => {
    if (!validPanelArea(points)) return false;
    const cross = (a, b, c) => (b.x - a.x) * (c.y - a.y) - (b.y - a.y) * (c.x - a.x);
    const intersects = (a, b, c, d) => cross(a, b, c) * cross(a, b, d) < 0 && cross(c, d, a) * cross(c, d, b) < 0;
    const twiceArea = points.reduce((sum, point, index) => {
        const next = points[(index + 1) % 4];
        return sum + point.x * next.y - next.x * point.y;
    }, 0);
    return Math.abs(twiceArea) >= 1 && !intersects(points[0], points[1], points[2], points[3])
        && !intersects(points[1], points[2], points[3], points[0]);
};

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
    let panelInputs = [];
    const photoInput = wizard.querySelector('[data-photo-input]');
    const description = wizard.querySelector('textarea[name="description"]');
    const vehicleMake = wizard.querySelector('[name="vehicle_make_id"]');
    const vehicleModel = wizard.querySelector('[name="vehicle_model_id"]');
    const panelContainer = wizard.querySelector('[data-model-panels]');
    const modelPhoto = wizard.querySelector('[data-model-photo]');
    const oldPanels = new Set(JSON.parse(wizard.querySelector('[data-old-panels]')?.textContent || '[]'));
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

    let modelsRequest = 0;
    let modelRequest = 0;
    let selectedModelData = null;
    const selectedMakeName = () => vehicleMake.selectedOptions[0]?.textContent || '';
    const selectedModelName = () => vehicleModel.selectedOptions[0]?.textContent || '';
    const populateModels = async (preferred = '') => {
        const request = ++modelsRequest;
        ++modelRequest;
        vehicleModel.replaceChildren(new Option(vehicleMake.value ? 'Loading models…' : 'Select vehicle make first', ''));
        selectedModelData = null;
        renderEmptyModel();
        if (!vehicleMake.value) return;
        try {
            const response = await fetch(`${form.dataset.catalogMakesUrl}/${encodeURIComponent(vehicleMake.value)}/models`, {headers: {'Accept': 'application/json'}});
            if (!response.ok) throw new Error('Could not load models.');
            const models = await response.json();
            if (request !== modelsRequest) return;
            vehicleModel.replaceChildren(new Option('Select vehicle model', ''));
            models.forEach((model) => vehicleModel.add(new Option(`${model.name} · ${model.kind}`, model.id)));
            vehicleModel.value = preferred;
            if (!vehicleModel.value) vehicleModel.value = '';
            if (vehicleModel.value) await renderModel();
        } catch (error) {
            if (request === modelsRequest) {
                vehicleModel.replaceChildren(new Option('Models unavailable. Try again.', ''));
                showWizardError(error.message);
            }
        }
    };
    const renderEmptyModel = () => {
        panelContainer.replaceChildren();
        panelInputs = [];
        const note = document.createElement('p');
        note.className = 'catalog-empty-note';
        note.textContent = 'Select a vehicle model to see its panels.';
        panelContainer.append(note);
        const stage = modelPhoto.querySelector('[data-model-photo-stage]');
        const img = stage.querySelector('img');
        stage.hidden = true;
        img.removeAttribute('src');
        img.alt = '';
        modelPhoto.querySelector('[data-model-areas]').replaceChildren();
        modelPhoto.querySelector('.model-photo__pending').hidden = false;
        modelPhoto.querySelector('small').textContent = 'Select a model to see its photo.';
    };
    const renderModel = async () => {
        const request = ++modelRequest;
        selectedModelData = null;
        renderEmptyModel();
        if (!vehicleModel.value) return;
        try {
            const response = await fetch(`${form.dataset.catalogModelsUrl}/${encodeURIComponent(vehicleModel.value)}`, {headers: {'Accept': 'application/json'}});
            if (!response.ok) throw new Error('Could not load model details.');
            const model = await response.json();
            if (request !== modelRequest) return;
            selectedModelData = model;
            panelContainer.replaceChildren();
            model.panels.forEach((panel) => {
                const label = document.createElement('label');
                const input = document.createElement('input');
                input.type = 'checkbox'; input.name = 'panels[]'; input.value = panel.key;
                input.dataset.panelInput = '';
                input.checked = oldPanels.has(panel.key);
                input.addEventListener('change', syncPanels);
                const name = document.createElement('span');
                const range = panel.min_sqm === null || panel.max_sqm === null
                    ? 'area range pending'
                    : `min ${Number(panel.min_sqm).toFixed(2)} m², max ${Number(panel.max_sqm).toFixed(2)} m²`;
                name.textContent = `${panel.name} (${range})`;
                label.append(input, name); panelContainer.append(label); panelInputs.push(input);
            });
            const img = modelPhoto.querySelector('img');
            const stage = modelPhoto.querySelector('[data-model-photo-stage]');
            stage.hidden = !model.photo_url;
            if (model.photo_url) img.src = model.photo_url;
            img.alt = model.photo_url ? `${model.make_name} ${model.name}` : '';
            modelPhoto.querySelector('.model-photo__pending').hidden = Boolean(model.photo_url);
            modelPhoto.querySelector('small').textContent = model.photo_url
                ? `${model.make_name} ${model.name}` : 'Model photo pending';
            const areas = modelPhoto.querySelector('[data-model-areas]');
            areas.replaceChildren();
            if (model.photo_url) model.panels.forEach((panel) => {
                if (!validPanelArea(panel.photo_polygon)) return;
                const area = svgElement('polygon');
                area.setAttribute('points', areaPoints(panel.photo_polygon));
                area.setAttribute('class', 'model-panel-area');
                area.setAttribute('role', 'button');
                area.setAttribute('tabindex', '0');
                area.setAttribute('aria-label', `Select ${panel.name}`);
                area.dataset.panelKey = panel.key;
                const toggle = () => {
                    const input = panelInputs.find((item) => item.value === panel.key);
                    if (input) { input.checked = !input.checked; syncPanels(); }
                };
                area.addEventListener('click', toggle);
                area.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); toggle(); }
                });
                const title = svgElement('title'); title.textContent = panel.name; area.append(title);
                areas.append(area);
            });
            if (model.photo_url) {
                modelPhoto.querySelector('small').textContent = areas.childElementCount
                    ? `${model.make_name} ${model.name} · tap a panel area or use the checklist`
                    : `${model.make_name} ${model.name} · use the checklist (photo areas not mapped yet)`;
            }
            syncPanels();
        } catch (error) {
            if (request === modelRequest) showWizardError(error.message);
        }
    };
    vehicleMake.addEventListener('change', () => { populateModels(); });
    vehicleModel.addEventListener('change', () => { renderModel(); });
    populateModels(vehicleModel.dataset.oldModel || '');

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
        const labels = {vehicle_make_id: 'vehicle make', vehicle_model_id: 'vehicle model', registration_number: 'registration number'};
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
        modelPhoto.querySelectorAll('[data-panel-key]').forEach((area) => {
            const selected = panelInputs.find((input) => input.value === area.dataset.panelKey)?.checked || false;
            area.classList.toggle('selected', selected);
            area.setAttribute('aria-pressed', String(selected));
            const name = area.querySelector('title')?.textContent || 'panel';
            area.setAttribute('aria-label', `${selected ? 'Deselect' : 'Select'} ${name}`);
        });
    };

    wizard.querySelectorAll('[data-panel-shape]').forEach((shape) => shape.addEventListener('click', () => {
        const input = wizard.querySelector(`[data-panel-input][value="${shape.dataset.panelShape}"]`);
        if (input) {
            input.checked = !input.checked;
            syncPanels();
        }
    }));

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
        wizard.querySelector('[data-review-vehicle]').textContent = `${selectedMakeName()} ${selectedModelData?.name || selectedModelName()}`.trim();
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

document.querySelectorAll('[data-catalog-panels]').forEach((container) => {
    const form = container.closest('form');
    const photo = form.querySelector('[data-catalog-photo]');
    const photoInput = photo.querySelector('input[type="file"]');
    const preview = photo.querySelector('[data-photo-preview]');
    const stage = photo.querySelector('[data-photo-stage]');
    const previewImage = stage.querySelector('img');
    const areas = stage.querySelector('[data-photo-areas]');
    const instruction = photo.querySelector('[data-photo-instruction]');
    const removePhoto = photo.querySelector('[data-remove-photo]');
    const removalNote = photo.querySelector('[data-photo-removal-note]');
    const cancelDraw = photo.querySelector('[data-cancel-photo-draw]');
    let activeRow = null;
    let selectedRow = null;
    let draftPoints = null;
    let drawingError = '';
    let previewUrl = null;
    let gesture = null;
    const defaultBoxAt = (point, w = 24, h = 16) => {
        const halfW = w / 2;
        const halfH = h / 2;
        const minX = Math.max(0, Math.min(100 - w, point.x - halfW));
        const minY = Math.max(0, Math.min(100 - h, point.y - halfH));
        return [
            { x: Number(minX.toFixed(2)), y: Number(minY.toFixed(2)) },
            { x: Number((minX + w).toFixed(2)), y: Number(minY.toFixed(2)) },
            { x: Number((minX + w).toFixed(2)), y: Number((minY + h).toFixed(2)) },
            { x: Number(minX.toFixed(2)), y: Number((minY + h).toFixed(2)) },
        ];
    };
    const readPoints = (row) => {
        try { return JSON.parse(row.querySelector('[data-photo-polygon]').value); }
        catch (_) { return null; }
    };
    const savePoints = (row, points) => {
        row.querySelector('[data-photo-polygon]').value = JSON.stringify(points);
    };
    const rectangle = (a, b) => [
        { x: Math.min(a.x, b.x), y: Math.min(a.y, b.y) },
        { x: Math.max(a.x, b.x), y: Math.min(a.y, b.y) },
        { x: Math.max(a.x, b.x), y: Math.max(a.y, b.y) },
        { x: Math.min(a.x, b.x), y: Math.max(a.y, b.y) },
    ];
    const pointAt = (event) => {
        const rect = previewImage.getBoundingClientRect();
        if (!rect.width || !rect.height) return null;
        return {
            x: Number((Math.max(0, Math.min(100, (event.clientX - rect.left) / rect.width * 100))).toFixed(2)),
            y: Number((Math.max(0, Math.min(100, (event.clientY - rect.top) / rect.height * 100))).toFixed(2)),
        };
    };
    const syncOverlay = () => {
        const image = previewImage.getBoundingClientRect();
        const parent = stage.getBoundingClientRect();
        areas.style.left = `${image.left - parent.left}px`;
        areas.style.top = `${image.top - parent.top}px`;
        areas.style.width = `${image.width}px`;
        areas.style.height = `${image.height}px`;
    };
    previewImage.addEventListener('load', syncOverlay);
    new ResizeObserver(syncOverlay).observe(previewImage);
    const drawAreas = () => {
        areas.replaceChildren();
        container.querySelectorAll('[data-catalog-panel-row]').forEach((row) => {
            const points = readPoints(row);
            const button = row.querySelector('[data-draw-panel-area]');
            const panelNameInput = row.querySelector('input[name$="[name]"]');
            const panelName = panelNameInput ? panelNameInput.value.trim() : 'Panel';
            const isMapped = validPanelArea(points);
            const isSetting = row === activeRow;
            const isSelected = row === selectedRow;

            button.classList.toggle('is-mapped', isMapped);
            button.classList.toggle('is-setting', isSetting);
            button.textContent = isSetting ? 'Drawing…' : (isMapped ? (isSelected ? 'Selected' : 'Edit area') : 'Draw area');

            if (!isMapped || isSetting) return;

            const group = svgElement('g');
            group.setAttribute('class', `catalog-photo-area-group${isSelected ? ' is-selected' : ''}`);

            const polygon = svgElement('polygon');
            polygon.setAttribute('points', areaPoints(points));
            polygon.setAttribute('class', `catalog-photo-area${isSelected ? ' is-selected' : ''}`);
            polygon.panelRow = row;

            const title = svgElement('title');
            title.textContent = panelName;
            polygon.append(title);
            group.append(polygon);

            if (panelName) {
                const avgX = (points.reduce((sum, p) => sum + p.x, 0) / points.length).toFixed(2);
                const minY = Math.min(...points.map((p) => p.y));
                const textY = Math.max(3.5, minY - 1.2).toFixed(2);
                const text = svgElement('text');
                text.setAttribute('x', avgX);
                text.setAttribute('y', textY);
                text.setAttribute('text-anchor', 'middle');
                text.setAttribute('class', `catalog-photo-area-label${isSelected ? ' is-selected' : ''}`);
                text.textContent = panelName;
                group.append(text);
            }

            areas.append(group);

            if (isSelected) {
                const cornerCursors = ['nwse-resize', 'nesw-resize', 'nwse-resize', 'nesw-resize'];
                points.forEach((point, index) => {
                    const handle = svgElement('circle');
                    handle.setAttribute('cx', point.x);
                    handle.setAttribute('cy', point.y);
                    handle.setAttribute('r', '2.4');
                    handle.setAttribute('class', 'catalog-photo-handle');
                    handle.style.cursor = cornerCursors[index] || 'move';
                    handle.panelRow = row;
                    handle.cornerIndex = index;
                    areas.append(handle);
                });
            }
        });

        if (activeRow && draftPoints) {
            const draft = svgElement('polygon');
            draft.setAttribute('points', areaPoints(draftPoints));
            draft.setAttribute('class', 'catalog-photo-draft');
            areas.append(draft);
        }

        stage.classList.toggle('is-setting', Boolean(activeRow));
        instruction.textContent = drawingError || (activeRow
            ? `Click or drag on the photo to place area for ${activeRow.querySelector('input[name$="[name]"]').value || 'panel'}.`
            : 'Click or drag an area to move it. Drag corner handles to adjust shape.');
        cancelDraw.hidden = !activeRow;
        syncOverlay();
    };
    photoInput.addEventListener('change', () => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        const file = photoInput.files?.[0];
        if (!file) return;
        previewUrl = URL.createObjectURL(file);
        previewImage.src = previewUrl;
        preview.hidden = false;
        removePhoto.value = '0';
        removalNote.hidden = true;
        drawAreas();
    });
    photo.querySelector('[data-remove-model-photo]').addEventListener('click', () => {
        photoInput.value = '';
        if (previewUrl) { URL.revokeObjectURL(previewUrl); previewUrl = null; }
        previewImage.removeAttribute('src'); preview.hidden = true;
        removePhoto.value = '1'; removalNote.hidden = false;
        activeRow = null; selectedRow = null; draftPoints = null; gesture = null; drawingError = ''; drawAreas();
    });
    stage.addEventListener('pointerdown', (event) => {
        if (event.button !== 0 || preview.hidden) return;
        const imageRect = previewImage.getBoundingClientRect();
        if (event.clientX < imageRect.left || event.clientX > imageRect.right || event.clientY < imageRect.top || event.clientY > imageRect.bottom) return;
        const point = pointAt(event);
        if (!point) return;
        const handle = event.target.closest?.('.catalog-photo-handle');
        const polygon = event.target.closest?.('.catalog-photo-area');
        if (activeRow) {
            gesture = { type: 'draw', row: activeRow, start: point, pointerId: event.pointerId };
            draftPoints = rectangle(point, point);
            drawingError = '';
        } else if (handle?.panelRow) {
            selectedRow = handle.panelRow;
            gesture = { type: 'corner', row: handle.panelRow, points: readPoints(handle.panelRow), cornerIndex: handle.cornerIndex, pointerId: event.pointerId };
        } else if (polygon?.panelRow) {
            selectedRow = polygon.panelRow;
            gesture = { type: 'move', row: polygon.panelRow, points: readPoints(polygon.panelRow), start: point, pointerId: event.pointerId };
        } else {
            selectedRow = null;
            drawAreas();
            return;
        }
        stage.setPointerCapture(event.pointerId);
        stage.classList.add('is-dragging');
        event.preventDefault();
        drawAreas();
    });
    stage.addEventListener('pointermove', (event) => {
        if (!gesture || gesture.pointerId !== event.pointerId) return;
        const current = pointAt(event);
        if (!current) return;
        if (gesture.type === 'draw') {
            draftPoints = rectangle(gesture.start, current);
        } else if (gesture.type === 'corner') {
            const points = gesture.points.map((point) => ({ ...point }));
            points[gesture.cornerIndex] = current;
            if (simplePanelArea(points)) savePoints(gesture.row, points);
        } else {
            const minX = Math.min(...gesture.points.map((point) => point.x));
            const maxX = Math.max(...gesture.points.map((point) => point.x));
            const minY = Math.min(...gesture.points.map((point) => point.y));
            const maxY = Math.max(...gesture.points.map((point) => point.y));
            const dx = current.x - gesture.start.x;
            const dy = current.y - gesture.start.y;
            const clampedDx = Math.max(-minX, Math.min(100 - maxX, dx));
            const clampedDy = Math.max(-minY, Math.min(100 - maxY, dy));
            savePoints(gesture.row, gesture.points.map((point) => ({
                x: Number((point.x + clampedDx).toFixed(2)), y: Number((point.y + clampedDy).toFixed(2)),
            })));
        }
        drawAreas();
    });
    const stopGesture = (event) => {
        if (!gesture || gesture.pointerId !== event.pointerId) return;
        if (gesture.type === 'draw') {
            const end = pointAt(event);
            let points = null;
            if (end && (Math.abs(end.x - gesture.start.x) > 2 || Math.abs(end.y - gesture.start.y) > 2)) {
                points = rectangle(gesture.start, end);
            }
            if (!points || !simplePanelArea(points)) {
                points = defaultBoxAt(gesture.start);
            }
            savePoints(gesture.row, points);
            selectedRow = gesture.row;
            activeRow = null;
            drawingError = '';
            draftPoints = null;
        }
        gesture = null;
        stage.classList.remove('is-dragging');
        if (stage.hasPointerCapture(event.pointerId)) stage.releasePointerCapture(event.pointerId);
        drawAreas();
    };
    stage.addEventListener('pointerup', stopGesture);
    stage.addEventListener('pointercancel', (event) => {
        if (gesture?.pointerId !== event.pointerId) return;
        gesture = null; draftPoints = null;
        stage.classList.remove('is-dragging'); drawAreas();
    });
    const cancelDrawing = () => {
        if (gesture && stage.hasPointerCapture(gesture.pointerId)) stage.releasePointerCapture(gesture.pointerId);
        gesture = null; activeRow = null; draftPoints = null; drawingError = '';
        stage.classList.remove('is-dragging'); drawAreas();
    };
    cancelDraw.addEventListener('click', cancelDrawing);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && activeRow) cancelDrawing();
    });
    form.querySelector('[data-add-panel]')?.addEventListener('click', () => {
        const index = Number(container.dataset.nextIndex || 0);
        container.dataset.nextIndex = String(index + 1);
        const row = document.createElement('div');
        row.className = 'catalog-panel-row';
        row.dataset.catalogPanelRow = '';
        [['Panel name', 'name', 'text'], ['Min m²', 'min_sqm', 'number'], ['Max m²', 'max_sqm', 'number']].forEach(([labelText, key, type]) => {
            const field = document.createElement('div'); field.className = 'field';
            const label = document.createElement('label'); label.textContent = labelText;
            const control = document.createElement('div'); control.className = 'field__control';
            const input = document.createElement('input'); input.name = `panels[${index}][${key}]`; input.type = type; input.required = true;
            if (type === 'number') { input.step = '0.01'; input.min = '0'; }
            control.append(input); field.append(label, control); row.append(field);
        });
        const position = document.createElement('div'); position.className = 'catalog-panel-position';
        const polygon = document.createElement('input'); polygon.type = 'hidden'; polygon.name = `panels[${index}][photo_polygon]`; polygon.dataset.photoPolygon = ''; position.append(polygon);
        const drawArea = document.createElement('button'); drawArea.type = 'button'; drawArea.dataset.drawPanelArea = ''; drawArea.textContent = 'Draw area'; position.append(drawArea);
        const clearArea = document.createElement('button'); clearArea.type = 'button'; clearArea.dataset.clearPanelArea = ''; clearArea.textContent = '×'; clearArea.title = 'Clear panel area'; clearArea.setAttribute('aria-label', 'Clear panel area'); position.append(clearArea); row.append(position);
        const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'icon-action icon-action--danger'; remove.dataset.removePanel = ''; remove.textContent = '×'; remove.setAttribute('aria-label', 'Remove panel'); row.append(remove);
        container.append(row); row.querySelector('input')?.focus();
    });
    container.addEventListener('click', (event) => {
        const row = event.target.closest('[data-catalog-panel-row]');
        if (!row) return;
        if (event.target.closest('[data-remove-panel]')) { if (activeRow === row) { activeRow = null; draftPoints = null; } if (selectedRow === row) selectedRow = null; row.remove(); drawingError = ''; drawAreas(); }
        if (event.target.closest('[data-clear-panel-area]')) { row.querySelector('[data-photo-polygon]').value = ''; if (activeRow === row) { activeRow = null; draftPoints = null; } if (selectedRow === row) selectedRow = null; drawingError = ''; drawAreas(); }
        if (event.target.closest('[data-draw-panel-area]')) {
            if (preview.hidden) { photoInput.focus(); return; }
            if (validPanelArea(readPoints(row))) {
                selectedRow = row;
                activeRow = null;
                drawingError = '';
                drawAreas();
                preview.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                activeRow = activeRow === row ? null : row;
                selectedRow = null;
                draftPoints = null;
                drawingError = '';
                drawAreas();
                if (activeRow) preview.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
    drawAreas();
});
