class ExpenseCalculator {
    constructor() {
        this.items = [];
        this.currentItemId = 0;
        this.isCalculating = false;
        this.cache = new Map();
        this.init();
    }
    
    init() {
        this.addItem();
        this.bindEvents();
        this.loadPreferences();
        this.loadHistory();
    }
    
    bindEvents() {
        document.addEventListener('click', (e) => {
            if (e.target.id === 'add-item-btn') {
                e.preventDefault();
                this.addItem();
            } else if (e.target.id === 'calculate-btn') {
                e.preventDefault();
                this.calculate(e);
            } else if (e.target.id === 'clear-history-btn') {
                e.preventDefault();
                this.clearHistory();
            } else if (e.target.id === 'settings-btn') {
                e.preventDefault();
                this.toggleSettings();
            } else if (e.target.id === 'history-btn') {
                e.preventDefault();
                this.toggleHistory();
            } else if (e.target.id === 'save-settings-btn') {
                e.preventDefault();
                this.saveSettings();
            } else if (e.target.classList.contains('remove-item-btn')) {
                e.preventDefault();
                const itemId = parseInt(e.target.closest('.item-row').dataset.itemId);
                this.removeItem(itemId);
            } else if (e.target.classList.contains('reuse-btn')) {
                e.preventDefault();
                const historyId = e.target.dataset.historyId;
                this.reuseCalculation(historyId);
            }
        });
    }
    
    addItem() {
        const itemId = this.currentItemId++;
        const itemHtml = `
            <div class="item-row" data-item-id="${itemId}">
                <div class="form-group">
                    <label>Nama Minuman</label>
                    <input type="text" name="items[${itemId}][name]" placeholder="Contoh: Kopi Susu" required>
                </div>
                <div class="form-group">
                    <label>Harga per Item (Rp)</label>
                    <input type="number" name="items[${itemId}][price]" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label>Jumlah</label>
                    <input type="number" name="items[${itemId}][quantity]" min="1" value="1" required>
                </div>
                <div class="form-group">
                    <button type="button" class="remove-item-btn">Hapus</button>
                </div>
            </div>
        `;
        
        document.getElementById('items-container').insertAdjacentHTML('beforeend', itemHtml);
        this.items.push({id: itemId, name: '', price: 0, quantity: 1});
    }
    
    removeItem(itemId) {
        const itemRow = document.querySelector(`[data-item-id="${itemId}"]`);
        if (itemRow) {
            itemRow.remove();
            this.items = this.items.filter(item => item.id !== itemId);
        }
    }
    
    calculate(e) {
        e.preventDefault();
        
        const formData = new FormData(document.getElementById('expense-form'));
        const data = {
            items: [],
            tax_rate: parseFloat(formData.get('tax_rate')) || 0,
            service_charge: parseFloat(formData.get('service_charge')) || 0,
            discount_rate: parseFloat(formData.get('discount_rate')) || 0,
            tips_rate: parseFloat(formData.get('tips_rate')) || 0,
            number_of_people: parseInt(formData.get('number_of_people')) || 1,
            rounding_mode: formData.get('rounding_mode') || 'up'
        };
        
        const itemRows = document.querySelectorAll('.item-row');
        
        itemRows.forEach(row => {
            const nameInput = row.querySelector('input[name*="[name]"]');
            const priceInput = row.querySelector('input[name*="[price]"]');
            const quantityInput = row.querySelector('input[name*="[quantity]"]');
            
            if (nameInput && priceInput && quantityInput) {
                const name = nameInput.value.trim();
                const price = parseFloat(priceInput.value) || 0;
                const quantity = parseInt(quantityInput.value) || 1;
                
                if (name && price > 0 && quantity > 0) {
                    data.items.push({
                        name: name,
                        price: price,
                        quantity: quantity
                    });
                }
            }
        });
        
        if (data.items.length === 0) {
            console.log('Debug: No items found. Item rows:', itemRows.length);
            console.log('Debug: Form data:', data);
            this.displayErrors(['Minimal harus ada satu item minuman. Pastikan nama, harga, dan jumlah sudah diisi.']);
            return;
        }
        
        if (data.number_of_people <= 0) {
            this.displayErrors(['Jumlah orang harus lebih dari 0']);
            return;
        }
        
        fetch('api/calculate.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                this.displayResults(result.data);
                this.generateCharts(result.data);
            } else {
                this.displayErrors(result.errors || ['Terjadi kesalahan dalam perhitungan']);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.displayErrors(['Terjadi kesalahan dalam perhitungan']);
        });
    }
    
    displayResults(data) {
        const resultsContainer = document.getElementById('results-container');
        if (!resultsContainer) return;
        
        const currency = data.currency || 'IDR';
        
        let html = `
            <div class="results">
                <h2>Hasil Perhitungan</h2>
                <div class="calculation-table">
                    <table>
                        <tr><td>Subtotal:</td><td>${this.formatCurrency(data.subtotal, currency)}</td></tr>
        `;
        
        if (data.discount_rate > 0) {
            html += `<tr><td>Diskon (${data.discount_rate}%):</td><td>${this.formatCurrency(data.discount_amount, currency)}</td></tr>`;
            html += `<tr><td>Setelah Diskon:</td><td>${this.formatCurrency(data.after_discount, currency)}</td></tr>`;
        }
        
        html += `
                        <tr><td>Pajak (${data.tax_rate}%):</td><td>${this.formatCurrency(data.tax_amount, currency)}</td></tr>
                        <tr><td>Service Charge (${data.service_charge_rate}%):</td><td>${this.formatCurrency(data.service_charge_amount, currency)}</td></tr>
        `;
        
        if (data.tips_rate > 0) {
            html += `<tr><td>Tips (${data.tips_rate}%):</td><td>${this.formatCurrency(data.tips_amount, currency)}</td></tr>`;
        }
        
        html += `
                        <tr class="total-row"><td>Total Cost:</td><td>${this.formatCurrency(data.total, currency)}</td></tr>
                        <tr class="per-person-row"><td>Per Person (${data.number_of_people} people):</td><td>${this.formatCurrency(data.per_person, currency)}</td></tr>
                    </table>
                </div>
                <div class="action-buttons">
                    <button id="export-pdf-btn" class="action-btn">Export PDF</button>
                    <button id="share-whatsapp-btn" class="action-btn">Share WhatsApp</button>
                    <button id="generate-qr-btn" class="action-btn">Generate QR Code</button>
                </div>
            </div>
        `;
        
        resultsContainer.innerHTML = html;
        
        setTimeout(() => {
            const exportPdfBtn = document.getElementById('export-pdf-btn');
            const shareWhatsappBtn = document.getElementById('share-whatsapp-btn');
            const generateQrBtn = document.getElementById('generate-qr-btn');
            
            if (exportPdfBtn) {
                exportPdfBtn.onclick = () => this.exportPDF(data);
            }
            if (shareWhatsappBtn) {
                shareWhatsappBtn.onclick = () => this.shareWhatsApp(data);
            }
            if (generateQrBtn) {
                generateQrBtn.onclick = () => this.generateQRCode(data);
            }
        }, 100);
    }
    
    displayErrors(errors) {
        const resultsContainer = document.getElementById('results-container');
        if (!resultsContainer) return;
        
        let html = '<div class="error-messages">';
        errors.forEach(error => {
            html += `<div class="error-message">${error}</div>`;
        });
        html += '</div>';
        resultsContainer.innerHTML = html;
    }
    
    generateCharts(data) {
        const chartsContainer = document.getElementById('charts-container');
        if (chartsContainer) {
            chartsContainer.style.display = 'block';
        }
        
        this.generateExpenseChart(data);
        this.generatePerPersonChart(data);
    }
    
    generateExpenseChart(data) {
        const canvas = document.getElementById('expense-chart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Subtotal', 'Tax', 'Service Charge'],
                datasets: [{
                    data: [data.subtotal, data.tax_amount, data.service_charge_amount],
                    backgroundColor: ['#8b4513', '#a0522d', '#654321'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    generatePerPersonChart(data) {
        const canvas = document.getElementById('per-person-chart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        const labels = [];
        const amounts = [];
        
        for (let i = 1; i <= data.number_of_people; i++) {
            labels.push(`Person ${i}`);
            amounts.push(data.per_person);
        }
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Amount per Person',
                    data: amounts,
                    backgroundColor: '#8b4513',
                    borderColor: '#654321',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    formatCurrency(amount, currency = 'IDR') {
        const symbols = {
            'IDR': 'Rp',
            'USD': '$',
            'EUR': '€'
        };
        
        const symbol = symbols[currency] || symbols['IDR'];
        
        if (currency === 'IDR') {
            return symbol + ' ' + new Intl.NumberFormat('id-ID').format(amount);
        } else {
            return symbol + new Intl.NumberFormat('en-US', {minimumFractionDigits: 2}).format(amount);
        }
    }
    
    exportPDF(data) {
        const html = this.generatePDFHTML(data);
        const blob = new Blob([html], { type: 'text/html' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'perhitungan_patungan.html';
        a.click();
        window.URL.revokeObjectURL(url);
    }
    
    generatePDFHTML(data) {
        let html = `
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Perhitungan Patungan Minuman</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #8b4513; padding-bottom: 20px; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background: #f0f0f0; font-weight: bold; }
        .total-row td { background: #e8f4f8; font-weight: bold; }
        .per-person-row td { background: #f0f8e8; font-weight: bold; }
        @media print { body { margin: 0; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>Kalkulator Patungan Minuman</h1>
        <p>Hasil Perhitungan Patungan Minuman</p>
        <p><strong>Tanggal:</strong> ${new Date().toLocaleDateString('id-ID')} ${new Date().toLocaleTimeString('id-ID')}</p>
    </div>
    
    <h2>Detail Item:</h2>
    <table>
        <tr><th>Nama Item</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th></tr>
        `;
        
        data.items.forEach(item => {
            const subtotal = item.price * item.quantity;
            html += `<tr>
                <td>${item.name}</td>
                <td>${this.formatCurrency(item.price)}</td>
                <td>${item.quantity}</td>
                <td>${this.formatCurrency(subtotal)}</td>
            </tr>`;
        });
        
        html += `</table>
        
        <h2>Ringkasan Perhitungan:</h2>
        <table>
            <tr><td>Subtotal:</td><td>${this.formatCurrency(data.subtotal)}</td></tr>
        `;
        
        if (data.discount_rate > 0) {
            html += `<tr><td>Diskon (${data.discount_rate}%):</td><td>${this.formatCurrency(data.discount_amount)}</td></tr>`;
            html += `<tr><td>Setelah Diskon:</td><td>${this.formatCurrency(data.after_discount)}</td></tr>`;
        }
        
        html += `
            <tr><td>Pajak (${data.tax_rate}%):</td><td>${this.formatCurrency(data.tax_amount)}</td></tr>
            <tr><td>Service Charge (${data.service_charge_rate}%):</td><td>${this.formatCurrency(data.service_charge_amount)}</td></tr>
        `;
        
        if (data.tips_rate > 0) {
            html += `<tr><td>Tips (${data.tips_rate}%):</td><td>${this.formatCurrency(data.tips_amount)}</td></tr>`;
        }
        
        html += `
            <tr class="total-row"><td><strong>Total:</strong></td><td><strong>${this.formatCurrency(data.total)}</strong></td></tr>
            <tr class="per-person-row"><td><strong>Per Orang (${data.number_of_people} orang):</strong></td><td><strong>${this.formatCurrency(data.per_person)}</strong></td></tr>
        </table>
        
        <div class="footer">
            <p>Dihasilkan oleh Kalkulator Patungan Minuman - ${new Date().toLocaleDateString('id-ID')}</p>
        </div>
        
        <script>
            window.onload = function() {
                window.print();
            }
        </script>
</body>
</html>`;
        
        return html;
    }
    
    shareWhatsApp(data) {
        const message = `🍻 *Hasil Perhitungan Patungan Minuman*

💰 Total: ${this.formatCurrency(data.total)}
👥 Per Orang (${data.number_of_people} orang): ${this.formatCurrency(data.per_person)}
📅 Tanggal: ${new Date().toLocaleDateString('id-ID')}

Dihitung menggunakan Kalkulator Patungan Minuman`;
        
        const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;
        window.open(whatsappUrl, '_blank');
    }
    
    generateQRCode(data) {
        const qrData = {
            total: data.total,
            per_person: data.per_person,
            people: data.number_of_people,
            timestamp: new Date().toISOString()
        };
        
        const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(JSON.stringify(qrData))}`;
        
        const modal = document.createElement('div');
        modal.className = 'qr-modal';
        modal.innerHTML = `
            <div class="qr-content">
                <h3>QR Code untuk Hasil Perhitungan</h3>
                <img src="${qrUrl}" alt="QR Code">
                <button onclick="this.parentElement.parentElement.remove()">Tutup</button>
            </div>
        `;
        
        document.body.appendChild(modal);
    }
    
    clearHistory() {
        if (confirm('Apakah Anda yakin ingin menghapus semua riwayat perhitungan?')) {
            fetch('api/history.php', {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.loadHistory();
                } else {
                    alert('Gagal menghapus riwayat: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error clearing history:', error);
                alert('Terjadi kesalahan saat menghapus riwayat');
            });
        }
    }
    
    loadHistory() {
        fetch('api/history.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.displayHistory(data.history);
            }
        })
        .catch(error => {
            console.error('Error loading history:', error);
        });
    }
    
    displayHistory(history) {
        const historyContainer = document.getElementById('history-container');
        if (!historyContainer) return;
        
        let html = '';
        
        if (history.length === 0) {
            html += '<p>Belum ada riwayat perhitungan</p>';
        } else {
            history.forEach(item => {
                html += `
                    <div class="history-item">
                        <div class="history-date">${item.timestamp}</div>
                        <div class="history-total">Total: ${this.formatCurrency(item.data.total)}</div>
                        <div class="history-per-person">Per Orang: ${this.formatCurrency(item.data.per_person)}</div>
                        <button type="button" class="reuse-btn" data-history-id="${item.id}">Gunakan Lagi</button>
                    </div>
                `;
            });
        }
        
        historyContainer.innerHTML = html;
    }
    
    reuseCalculation(historyId) {
        fetch(`api/history.php?id=${historyId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.populateForm(data.data);
            } else {
                alert('Gagal memuat perhitungan: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error reusing calculation:', error);
            alert('Terjadi kesalahan saat memuat perhitungan');
        });
    }
    
    populateForm(data) {
        const taxRateInput = document.getElementById('tax_rate');
        const serviceChargeInput = document.getElementById('service_charge');
        const discountRateInput = document.getElementById('discount_rate');
        const tipsRateInput = document.getElementById('tips_rate');
        const numberOfPeopleInput = document.getElementById('number_of_people');
        const roundingModeInput = document.getElementById('rounding_mode');
        
        if (taxRateInput) taxRateInput.value = data.tax_rate;
        if (serviceChargeInput) serviceChargeInput.value = data.service_charge_rate;
        if (discountRateInput) discountRateInput.value = data.discount_rate;
        if (tipsRateInput) tipsRateInput.value = data.tips_rate;
        if (numberOfPeopleInput) numberOfPeopleInput.value = data.number_of_people;
        if (roundingModeInput) roundingModeInput.value = data.rounding_mode;
        
        const itemsContainer = document.getElementById('items-container');
        if (itemsContainer) {
            itemsContainer.innerHTML = '';
            this.items = [];
            
            data.items.forEach(item => {
                this.addItem();
                const lastItem = document.querySelector('#items-container .item-row:last-child');
                if (lastItem) {
                    const nameInput = lastItem.querySelector('input[name*="[name]"]');
                    const priceInput = lastItem.querySelector('input[name*="[price]"]');
                    const quantityInput = lastItem.querySelector('input[name*="[quantity]"]');
                    
                    if (nameInput) nameInput.value = item.name;
                    if (priceInput) priceInput.value = item.price;
                    if (quantityInput) quantityInput.value = item.quantity;
                }
            });
        }
    }
    
    toggleSettings() {
        const settingsPanel = document.getElementById('settings-panel');
        const historyPanel = document.getElementById('history-panel');
        
        if (settingsPanel) {
            settingsPanel.style.display = settingsPanel.style.display === 'none' ? 'block' : 'none';
        }
        
        if (historyPanel) {
            historyPanel.style.display = 'none';
        }
    }
    
    toggleHistory() {
        const historyPanel = document.getElementById('history-panel');
        const settingsPanel = document.getElementById('settings-panel');
        
        if (historyPanel) {
            historyPanel.style.display = historyPanel.style.display === 'none' ? 'block' : 'none';
            if (historyPanel.style.display === 'block') {
                this.loadHistory();
            }
        }
        
        if (settingsPanel) {
            settingsPanel.style.display = 'none';
        }
    }
    
    saveSettings() {
        const preferences = {
            default_tax_rate: parseFloat(document.getElementById('default_tax_rate').value),
            default_service_charge: parseFloat(document.getElementById('default_service_charge').value),
            default_currency: document.getElementById('default_currency').value,
            rounding_mode: document.getElementById('default_rounding_mode').value
        };
        
        fetch('api/settings.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(preferences)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Pengaturan berhasil disimpan');
                this.toggleSettings();
            } else {
                alert('Gagal menyimpan pengaturan: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error saving settings:', error);
            alert('Terjadi kesalahan saat menyimpan pengaturan');
        });
    }
    
    loadPreferences() {
        fetch('api/settings.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.preferences) {
                const taxRateInput = document.getElementById('tax_rate');
                const serviceChargeInput = document.getElementById('service_charge');
                const roundingModeInput = document.getElementById('rounding_mode');
                
                if (taxRateInput) taxRateInput.value = data.preferences.default_tax_rate;
                if (serviceChargeInput) serviceChargeInput.value = data.preferences.default_service_charge;
                if (roundingModeInput) roundingModeInput.value = data.preferences.rounding_mode;
            }
        })
        .catch(error => {
            console.error('Error loading preferences:', error);
        });
    }
}

let calculator;
document.addEventListener('DOMContentLoaded', function() {
    calculator = new ExpenseCalculator();
});
