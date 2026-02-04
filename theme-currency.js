const USD_TO_LKR = 320;

function initializeThemeAndCurrency() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    const savedCurrency = localStorage.getItem('currency') || 'USD';
    
    document.documentElement.setAttribute('data-theme', savedTheme);
    document.documentElement.setAttribute('data-currency', savedCurrency);
    
    updateThemeIcon(savedTheme);
    updateCurrencyDisplay(savedCurrency);
    convertAllPrices(savedCurrency);
    
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('currency-dropdown');
        const btn = document.getElementById('currency-btn');
        if (dropdown && btn && !btn.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });
}

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemeIcon(newTheme);
}

function updateThemeIcon(theme) {
    const themeBtn = document.getElementById('theme-toggle');
    if (themeBtn) {
        themeBtn.innerHTML = theme === 'light' ? 
            '<i class="fas fa-moon"></i>' : 
            '<i class="fas fa-sun"></i>';
    }
}

function toggleCurrencyDropdown() {
    const dropdown = document.getElementById('currency-dropdown');
    if (dropdown) {
        dropdown.classList.toggle('show');
    }
}

function changeCurrency(currency) {
    document.documentElement.setAttribute('data-currency', currency);
    localStorage.setItem('currency', currency);
    updateCurrencyDisplay(currency);
    convertAllPrices(currency);
    
    const dropdown = document.getElementById('currency-dropdown');
    if (dropdown) {
        dropdown.classList.remove('show');
    }
}

function updateCurrencyDisplay(currency) {
    const selectedCurrency = document.getElementById('selected-currency');
    const currencyBtn = document.getElementById('currency-btn');
    
    if (selectedCurrency) {
        selectedCurrency.textContent = currency;
    }
    
    if (currencyBtn) {
        const icon = currencyBtn.querySelector('i:first-child');
        if (icon) {
            icon.className = currency === 'USD' ? 'fas fa-dollar-sign' : 'fas fa-rupee-sign';
        }
    }
}

function convertAllPrices(currency) {
    const priceElements = document.querySelectorAll('[data-price]');
    
    priceElements.forEach(element => {
        const basePrice = parseFloat(element.getAttribute('data-price'));
        if (isNaN(basePrice)) return;
        
        let displayPrice;
        
        if (currency === 'LKR') {
            displayPrice = (basePrice * USD_TO_LKR).toFixed(2);
            element.textContent = 'Rs. ' + formatNumber(displayPrice);
        } else {
            displayPrice = basePrice.toFixed(2);
            element.textContent = '$' + formatNumber(displayPrice);
        }
    });
}

function formatNumber(num) {
    return parseFloat(num).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

document.addEventListener('DOMContentLoaded', initializeThemeAndCurrency);
