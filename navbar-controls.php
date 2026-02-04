<div class="nav-controls">
    <div class="currency-selector">
        <button onclick="toggleCurrencyDropdown()" class="currency-btn" id="currency-btn">
            <i class="fas fa-dollar-sign"></i>
            <span id="selected-currency">USD</span>
            <i class="fas fa-chevron-down"></i>
        </button>
        <div class="currency-dropdown" id="currency-dropdown">
            <div class="currency-option" onclick="changeCurrency('USD')">
                <i class="fas fa-dollar-sign"></i>
                <span>USD - US Dollar</span>
            </div>
            <div class="currency-option" onclick="changeCurrency('LKR')">
                <i class="fas fa-rupee-sign"></i>
                <span>LKR - Sri Lankan Rupee</span>
            </div>
        </div>
    </div>
    <button onclick="toggleTheme()" class="theme-toggle-btn" id="theme-toggle">
        <i class="fas fa-moon"></i>
    </button>
</div>
