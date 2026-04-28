document.addEventListener('DOMContentLoaded', () => {
    const brandSelect = document.getElementById('brand');
    const modelSelect = document.getElementById('model');
    const customPriceWrapper = document.getElementById('custom-price-wrapper');
    const customPriceInput = document.getElementById('custom_price');
    const errorMessage = document.querySelector('.error-message');
    const resultsDiv = document.querySelector('.results');
    const passportTaxSpan = document.querySelector('.passport-tax span');
    const cnicTaxSpan = document.querySelector('.cnic-tax span');

    // Debug: Log phoneModels to verify data
    console.log('phoneModels:', phoneModels);
    console.log('usdToPkr:', usdToPkr);

    // Function to calculate PTA tax for custom price
    function calculatePTATax(pkr_value, type, usd_to_pkr) {
        const usd_value = pkr_value / usd_to_pkr; // Convert PKR to USD
        const sales_tax = pkr_value * 0.17; // 17% sales tax on PKR value

        let base_tax;
        if (type === 'passport') {
            if (usd_value <= 30) base_tax = 430;
            else if (usd_value <= 100) base_tax = 3200;
            else if (usd_value <= 200) base_tax = 9580;
            else if (usd_value <= 350) base_tax = 12200;
            else if (usd_value <= 500) base_tax = 17800;
            else base_tax = 27600;
        } else { // CNIC
            if (usd_value <= 30) base_tax = 550;
            else if (usd_value <= 100) base_tax = 4323;
            else if (usd_value <= 200) base_tax = 11561;
            else if (usd_value <= 350) base_tax = 14661;
            else if (usd_value <= 500) base_tax = 23420;
            else base_tax = 37007;
        }

        return Math.round(base_tax + sales_tax);
    }

    // Function to format number as PKR
    function formatPKR(value) {
        if (!value) return 'N/A';
        // Convert to integer to remove decimal places and format as PKR
        return parseInt(value).toLocaleString('en-PK', { style: 'currency', currency: 'PKR', minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    // Function to reset UI (hide input, results, and errors)
    function resetUI() {
        console.log('Resetting UI');
        customPriceWrapper.style.display = 'none';
        customPriceWrapper.classList.add('hidden');
        customPriceInput.value = '';
        resultsDiv.style.display = 'none';
        errorMessage.style.display = 'none';
    }

    // Function to update phone models dropdown
    function updatePhoneModels() {
        const brand = brandSelect.value;
        console.log('updatePhoneModels - Selected brand:', brand);
        console.log('Models for brand:', phoneModels[brand]);

        // Clear model dropdown
        modelSelect.innerHTML = '<option value="">Select a model</option>';

        // Populate models if brand is valid and not 'Other'
        if (brand && brand !== 'Other' && phoneModels[brand] && Array.isArray(phoneModels[brand])) {
            phoneModels[brand].forEach(model => {
                if (model.model_name && model.price_pkr != null) { // Ensure model has required fields
                    const option = document.createElement('option');
                    option.value = model.model_name;
                    option.textContent = model.model_name; // Only show model name
                    modelSelect.appendChild(option);
                    console.log('Added model:', model.model_name);
                } else {
                    console.warn('Invalid model data:', model);
                }
            });
        } else {
            console.log('No models for brand or brand is Other:', brand);
        }

        // Always add "Other" option
        const otherOption = document.createElement('option');
        otherOption.value = 'Other';
        otherOption.textContent = 'Other';
        modelSelect.appendChild(otherOption);
        console.log('Added Other option to model dropdown');

        // Reset UI to ensure input is hidden
        resetUI();
    }

    // Function to display taxes or show custom price input
    function displayTaxes() {
        const model = modelSelect.value;
        console.log('displayTaxes - Selected model:', model);
        resetUI(); // Always reset UI first

        if (!model) {
            console.log('No model selected');
            return;
        }

        if (model === 'Other') {
            console.log('Showing custom price input for Other');
            customPriceWrapper.style.display = 'block';
            customPriceWrapper.classList.remove('hidden');
            customPriceInput.focus();
            return;
        }

        // Find selected model
        let selectedModel = null;
        for (const brand in phoneModels) {
            if (phoneModels[brand] && Array.isArray(phoneModels[brand])) {
                selectedModel = phoneModels[brand].find(m => m.model_name === model);
                if (selectedModel) break;
            }
        }

        console.log('Selected model data:', selectedModel);

        if (selectedModel) {
            passportTaxSpan.textContent = formatPKR(selectedModel.pta_tax_passport);
            cnicTaxSpan.textContent = formatPKR(selectedModel.pta_tax_cnic);
            resultsDiv.style.display = 'block';
            console.log('Displaying taxes for:', selectedModel.model_name);
        } else {
            errorMessage.textContent = 'Selected model not found.';
            errorMessage.style.display = 'block';
            console.warn('Model not found:', model);
        }
    }

    // Function to handle custom price input
    function handleCustomPrice() {
        const price = parseFloat(customPriceInput.value);
        console.log('handleCustomPrice - Entered price:', price);
        resultsDiv.style.display = 'none';
        errorMessage.style.display = 'none';

        if (isNaN(price) || price <= 0) {
            errorMessage.textContent = 'Please enter a valid price greater than 0.';
            errorMessage.style.display = 'block';
            console.warn('Invalid price input:', price);
            return;
        }

        const passportTax = calculatePTATax(price, 'passport', usdToPkr);
        const cnicTax = calculatePTATax(price, 'cnic', usdToPkr);
        passportTaxSpan.textContent = formatPKR(passportTax);
        cnicTaxSpan.textContent = formatPKR(cnicTax);
        resultsDiv.style.display = 'block';
        console.log('Calculated taxes - Passport:', passportTax, 'CNIC:', cnicTax);
    }

    // Event listeners
    if (brandSelect) {
        brandSelect.addEventListener('change', () => {
            console.log('Brand changed');
            updatePhoneModels();
            displayTaxes();
        });
    } else {
        console.error('Brand select element not found');
    }

    if (modelSelect) {
        modelSelect.addEventListener('change', displayTaxes);
    } else {
        console.error('Model select element not found');
    }

    if (customPriceInput) {
        customPriceInput.addEventListener('input', handleCustomPrice);
    } else {
        console.error('Custom price input not found');
    }

    // Initialize
    console.log('Initializing calculator');
    resetUI();
    updatePhoneModels();
});