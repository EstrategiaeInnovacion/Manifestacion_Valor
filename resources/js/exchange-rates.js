/**
 * Exchange Rates Module
 * Maneja la obtención automática de tipos de cambio desde Banxico
 * para campos de fecha y moneda en formularios MVE.
 */

class ExchangeRateManager {
    constructor(config = {}) {
        this.config = {
            apiEndpoint: '/api/exchange-rate',
            debounceMs: 400,
            maxRetries: 3,
            decimalPlaces: 4,
            cacheExpirationHours: 24,
            loadingText: 'Cargando tipo de cambio...',
            errorText: 'No se encontró tipo de cambio',
            ...config
        };

        this.activeRequests = new Map();
        this.manualFlags = new Map();
        this.rateCache = new Map();
        this.debounceTimers = new Map();

        this.loadCacheFromStorage();
    }

    initialize(container, fieldSelectors = {}) {
        const containerEl = typeof container === 'string'
            ? document.querySelector(container)
            : container;

        if (!containerEl) {
            console.error('ExchangeRateManager: Container not found');
            return;
        }

        this.fieldSelectors = {
            dateField: '[data-exchange-date]',
            currencyField: '[data-exchange-currency]',
            rateField: '[data-exchange-rate]',
            autoButton: '[data-exchange-auto]',
            statusIndicator: '[data-exchange-status]',
            ...fieldSelectors
        };

        this.setupEventListeners(containerEl);
    }

    setupEventListeners(container) {
        container.addEventListener('change', (e) => {
            if (e.target.matches(this.fieldSelectors.dateField)
                || e.target.matches(this.fieldSelectors.currencyField)) {
                this.handleFieldChange(e.target);
            }
        });

        container.addEventListener('input', (e) => {
            if (e.target.matches(this.fieldSelectors.rateField)) {
                this.handleRateInput(e.target);
            }
        });

        container.addEventListener('blur', (e) => {
            if (e.target.matches(this.fieldSelectors.rateField)) {
                this.normalizeRateValue(e.target);
            }
        });

        container.addEventListener('click', (e) => {
            if (e.target.matches(this.fieldSelectors.autoButton)) {
                e.preventDefault();
                this.handleAutoButtonClick(e.target);
            }
        });
    }

    loadCacheFromStorage() {
        try {
            const storedCache = localStorage.getItem('exchange_rate_cache');
            if (storedCache) {
                const cacheData = JSON.parse(storedCache);
                const now = Date.now();

                Object.entries(cacheData).forEach(([key, data]) => {
                    if (data.expirationTime > now) {
                        this.rateCache.set(key, data.value);
                    }
                });
            }
        } catch (error) {
            console.error('Error loading cache from localStorage:', error);
            localStorage.removeItem('exchange_rate_cache');
        }
    }

    saveCacheToStorage() {
        try {
            const cacheData = {};
            const expirationTime = Date.now() + (this.config.cacheExpirationHours * 60 * 60 * 1000);

            this.rateCache.forEach((value, key) => {
                cacheData[key] = {
                    value,
                    expirationTime
                };
            });

            localStorage.setItem('exchange_rate_cache', JSON.stringify(cacheData));
        } catch (error) {
            console.error('Error saving cache to localStorage:', error);
        }
    }

    autoCompleteRelatedFields(currency, rate, excludeRowId = null) {
        const currencyFields = document.querySelectorAll(this.fieldSelectors.currencyField);

        currencyFields.forEach((currencyField) => {
            const rowId = this.getRowId(currencyField);

            if (rowId === excludeRowId) {
                return;
            }

            if (currencyField.value === currency) {
                const rateField = document.querySelector(`[data-row="${rowId}"]${this.fieldSelectors.rateField}`);

                if (rateField && (!rateField.value || rateField.value === '')) {
                    rateField.value = rate.toFixed(this.config.decimalPlaces);
                    rateField.dispatchEvent(new Event('change', { bubbles: true }));
                    this.setStatus(rowId, 'Auto-completado', 'success');
                    this.showAutoButton(rowId);
                }
            }
        });
    }

    handleFieldChange(field) {
        const rowId = this.getRowId(field);
        const rowData = this.getRowData(rowId);

        if (this.manualFlags.get(rowId)) {
            return;
        }

        this.cancelRequest(rowId);

        if (this.debounceTimers.has(rowId)) {
            clearTimeout(this.debounceTimers.get(rowId));
        }

        if (!rowData.date || !rowData.currency) {
            this.clearRateField(rowId);
            return;
        }

        const timer = setTimeout(() => {
            this.fetchExchangeRate(rowId, rowData.currency, rowData.date);
        }, this.config.debounceMs);

        this.debounceTimers.set(rowId, timer);
    }

    handleRateInput(rateField) {
        const rowId = this.getRowId(rateField);

        this.manualFlags.set(rowId, true);
        this.cancelRequest(rowId);
        this.showAutoButton(rowId);
        this.setStatus(rowId, '');
    }

    handleAutoButtonClick(button) {
        const rowId = this.getRowId(button);
        const rowData = this.getRowData(rowId);

        this.manualFlags.set(rowId, false);
        this.hideAutoButton(rowId);

        if (rowData.date && rowData.currency) {
            this.fetchExchangeRate(rowId, rowData.currency, rowData.date);
        }
    }

    getRowData(rowId) {
        const dateField = document.querySelector(`[data-row="${rowId}"]${this.fieldSelectors.dateField}`);
        const currencyField = document.querySelector(`[data-row="${rowId}"]${this.fieldSelectors.currencyField}`);
        const rateField = document.querySelector(`[data-row="${rowId}"]${this.fieldSelectors.rateField}`);

        return {
            date: dateField?.value || '',
            currency: currencyField?.value || '',
            rate: rateField?.value || '',
            dateField,
            currencyField,
            rateField
        };
    }

    getRowId(element) {
        let current = element;
        while (current && current !== document.body) {
            const rowId = current.getAttribute('data-row');
            if (rowId) {
                return rowId;
            }
            current = current.parentElement;
        }

        const rows = document.querySelectorAll('[data-row]');
        for (let i = 0; i < rows.length; i += 1) {
            if (rows[i].contains(element)) {
                return rows[i].getAttribute('data-row') || i.toString();
            }
        }

        return '0';
    }

    cancelRequest(rowId) {
        if (this.activeRequests.has(rowId)) {
            const controller = this.activeRequests.get(rowId);
            controller.abort();
            this.activeRequests.delete(rowId);
        }
    }

    async fetchExchangeRate(rowId, currency, date) {
        try {
            const cacheKey = `${currency}_${date}`;
            if (this.rateCache.has(cacheKey)) {
                const cachedRate = this.rateCache.get(cacheKey);
                this.updateRateField(rowId, cachedRate, 'Cache');
                this.setStatus(rowId, 'Cache', 'success');
                this.autoCompleteRelatedFields(currency, cachedRate, rowId);
                return;
            }

            this.setStatus(rowId, this.config.loadingText, 'loading');

            this.cancelRequest(rowId);

            const controller = new AbortController();
            this.activeRequests.set(rowId, controller);

            const response = await fetch(`${this.config.apiEndpoint}?currency=${currency}&date=${date}`, {
                signal: controller.signal
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();

            if (data.success && data.data && data.data.rate) {
                const rate = parseFloat(data.data.rate);

                this.rateCache.set(cacheKey, rate);
                this.saveCacheToStorage();

                this.updateRateField(rowId, rate, 'Auto');
                this.setStatus(rowId, 'Auto', 'success');

                this.autoCompleteRelatedFields(currency, rate, rowId);
            } else {
                this.setStatus(rowId, this.config.errorText, 'error');
            }
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            console.error('Error fetching exchange rate:', error);
            this.setStatus(rowId, this.config.errorText, 'error');
        } finally {
            this.activeRequests.delete(rowId);
        }
    }

    updateRateField(rowId, rate) {
        const rowData = this.getRowData(rowId);
        if (rowData.rateField) {
            rowData.rateField.value = rate.toFixed(this.config.decimalPlaces);
            rowData.rateField.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    clearRateField(rowId) {
        const rowData = this.getRowData(rowId);
        if (rowData.rateField) {
            rowData.rateField.value = '';
            rowData.rateField.dispatchEvent(new Event('change', { bubbles: true }));
        }
        this.setStatus(rowId, '');
    }

    setStatus(rowId, message, type = '') {
        const statusEl = document.querySelector(`[data-row="${rowId}"]${this.fieldSelectors.statusIndicator}`);
        if (statusEl) {
            statusEl.textContent = message;
            statusEl.className = `exchange-status exchange-status-${type}`;
        }
    }

    showAutoButton(rowId) {
        const button = document.querySelector(`[data-row="${rowId}"]${this.fieldSelectors.autoButton}`);
        if (button) {
            button.style.display = 'inline-block';
        }
    }

    hideAutoButton(rowId) {
        const button = document.querySelector(`[data-row="${rowId}"]${this.fieldSelectors.autoButton}`);
        if (button) {
            button.style.display = 'none';
        }
    }

    normalizeRateValue(rateField) {
        const value = parseFloat(rateField.value);
        if (!isNaN(value) && value > 0) {
            rateField.value = value.toFixed(this.config.decimalPlaces);
        }
    }

    destroy() {
        for (const controller of this.activeRequests.values()) {
            controller.abort();
        }
        this.activeRequests.clear();

        for (const timer of this.debounceTimers.values()) {
            clearTimeout(timer);
        }
        this.debounceTimers.clear();

        this.manualFlags.clear();
        this.rateCache.clear();
    }

    clearCacheForCurrency(currency) {
        const keysToRemove = [];
        this.rateCache.forEach((value, key) => {
            if (key.startsWith(`${currency}_`)) {
                keysToRemove.push(key);
            }
        });
        keysToRemove.forEach((key) => this.rateCache.delete(key));

        this.saveCacheToStorage();
    }
}

export default ExchangeRateManager;
