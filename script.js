document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('transactionForm');
    const list = document.getElementById('transactionList');
    const tableBody = document.getElementById('tableBody');
    const toggleBtn = document.getElementById('toggleTransactionList');
    const transactionList = document.getElementById('transactionList');

    let editId = null;
    let expenseChart = null;
    let incomeChart = null;
    let lineChart = null;
    let barChart = null;
    let transactions = [];
    let filteredData = [];
    let sortConfig = { column: null, ascending: true };

    // Toggle list
    toggleBtn.addEventListener('click', () => {
        transactionList.classList.toggle('hidden');
        toggleBtn.textContent = transactionList.classList.contains('hidden') ? '▼' : '▲';
    });

    // Event listeners filter
    document.getElementById('chartFilter').addEventListener('change', (e) => {
        renderFilteredCharts(filteredData, e.target.value);
    });
    document.getElementById('resetFilters').addEventListener('click', resetFilter);
    document.getElementById('startDate').addEventListener('change', applyFilter);
    document.getElementById('endDate').addEventListener('change', applyFilter);
    document.getElementById('filterCategory').addEventListener('change', applyFilter);
    document.getElementById('filterType').addEventListener('change', applyFilter);
    document.getElementById('searchText').addEventListener('input', applyFilter);

    document.getElementById('downloadExcel').addEventListener('click', async () => {
    try {
            const res = await fetch('backend.php?action=list');
            const transactions = await res.json();

            // Kirim data ke export.php
            const response = await fetch('export.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(transactions)
            });

            // Terima file sebagai blob
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);

            const a = document.createElement('a');
            a.href = url;
            a.download = 'finance_export.xlsx';
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
        } catch (err) {
            console.error('Download failed:', err);
        }
    });

    document.querySelectorAll('#transactionTable thead th').forEach(th => {
        th.addEventListener('click', () => {
            const column = th.dataset.column;
            if (sortConfig.column === column) sortConfig.ascending = !sortConfig.ascending;
            else { sortConfig.column = column; sortConfig.ascending = true; }
            sortTableData();
        });
    });

    async function loadData() {
        const res = await fetch('backend.php?action=list');
        transactions = await res.json();
        filteredData = [...transactions];
        renderAll();
        renderFilteredCharts(filteredData, document.getElementById('chartFilter').value);
    }

    function renderAll() {
        renderList(filteredData);
        renderTable(filteredData);
    }

    function renderList(data) {
    const transactionList = document.getElementById('transactionList');
    transactionList.innerHTML = '';

    if (data.length === 0) {
        // tetap bisa kasih info kalau kosong
        transactionList.innerHTML = '<p class="text-gray-500 text-center">No transactions found.</p>';
    } else {
        data.forEach(trx => {
            const div = document.createElement('div');
            div.className = 'p-2 border rounded flex justify-between items-center';
            div.innerHTML = `
                <div>
                    <p class="font-semibold">${trx.description}</p>
                    <p class="text-sm text-gray-500">${trx.date} | ${trx.category} | ${trx.type}</p>
                </div>
                <div>
                    <span class="${trx.type==='income'?'text-green-600':'text-red-600'} font-semibold">Rp${trx.amount.toLocaleString()}</span>
                </div>
            `;
            transactionList.appendChild(div);
        });
    }

    // Update summary
    const totalIncome = data.filter(d => d.type === 'income').reduce((sum, d) => sum + d.amount, 0);
    const totalExpense = data.filter(d => d.type === 'expense').reduce((sum, d) => sum + d.amount, 0);
    const balance = totalIncome - totalExpense;
    document.getElementById('totalIncome').textContent = 'Rp' + totalIncome.toLocaleString();
    document.getElementById('totalExpense').textContent = 'Rp' + totalExpense.toLocaleString();
    document.getElementById('balance').textContent = 'Rp' + balance.toLocaleString();
    
}


function renderTable(data) {
    tableBody.innerHTML = '';
    data.forEach(trx => {
        tableBody.innerHTML += `
            <tr>
                <td>${trx.date}</td>
                <td>${trx.category}</td>
                <td>${trx.type}</td>
                <td>${trx.amount.toLocaleString()}</td>
                <td>
                    <button class="bg-yellow-500 text-white px-2 py-1 rounded text-sm"
                        onclick='editTransaction(this)' 
                        data-item='${JSON.stringify(trx)}'>Edit</button>
                    <button class="bg-red-500 text-white px-2 py-1 rounded text-sm"
                        onclick="deleteTransaction(${trx.id})">Delete</button>
                </td>
            </tr>
        `;
    });
}


    function applyFilter() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        const category = document.getElementById('filterCategory').value;
        const type = document.getElementById('filterType').value;
        const search = document.getElementById('searchText').value.toLowerCase();

        filteredData = transactions.filter(trx => {
            const trxDate = new Date(trx.date);
            const inDateRange =
                (!startDate || trxDate >= new Date(startDate)) &&
                (!endDate || trxDate <= new Date(endDate));
            const inCategory = !category || trx.category === category;
            const inType = !type || trx.type === type;
            const inSearch =
                trx.category.toLowerCase().includes(search) ||
                trx.type.toLowerCase().includes(search) ||
                trx.description.toLowerCase().includes(search) ||
                trx.amount.toString().includes(search);

            return inDateRange && inCategory && inType && inSearch;
        });

        sortTableData();
        renderFilteredCharts(filteredData, document.getElementById('chartFilter').value);
    }

    function resetFilter() {
        document.getElementById('startDate').value = '';
        document.getElementById('endDate').value = '';
        document.getElementById('filterCategory').value = '';
        document.getElementById('filterType').value = '';
        document.getElementById('searchText').value = '';

        filteredData = [...transactions];
        sortTableData();
        renderFilteredCharts(filteredData, document.getElementById('chartFilter').value);
    }

    function sortTableData() {
        if (sortConfig.column) {
            filteredData.sort((a, b) => {
                let valA = a[sortConfig.column];
                let valB = b[sortConfig.column];
                if (sortConfig.column === "date") { valA = new Date(valA); valB = new Date(valB); }
                if (sortConfig.column === "amount") { valA = Number(valA); valB = Number(valB); }
                if (valA < valB) return sortConfig.ascending ? -1 : 1;
                if (valA > valB) return sortConfig.ascending ? 1 : -1;
                return 0;
            });
        }
        renderAll();
    }

    function updateChart(canvasId, chartData, chartTitle) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        const labels = Object.keys(chartData);
        const values = Object.values(chartData);

        if (canvasId === 'expenseChart' && expenseChart) expenseChart.destroy();
        if (canvasId === 'incomeChart' && incomeChart) incomeChart.destroy();

        const newChart = new Chart(ctx, {
            type: 'pie',
            data: { labels, datasets: [{ data: values, backgroundColor: ['rgba(255,99,132,0.6)','rgba(54,162,235,0.6)','rgba(255,206,86,0.6)','rgba(75,192,192,0.6)','rgba(153,102,255,0.6)','rgba(255,159,64,0.6)'] }] },
            options: { responsive: true, plugins: { legend: { position: 'bottom' }, title: { display: true, text: chartTitle } } }
        });

        if (canvasId === 'expenseChart') expenseChart = newChart;
        else incomeChart = newChart;
    }

    function renderFilteredCharts(data, filterType) {
        const aggregated = {};
        const pieExpenseData = {};
        const pieIncomeData = {};

        data.forEach(item => {
            // Aggregate for line/bar chart
            let key = '';
            if (filterType === 'day') key = item.date;
            else if (filterType === 'month') key = item.date.slice(0,7);
            else if (filterType === 'year') key = item.date.slice(0,4);
            if (!aggregated[key]) aggregated[key] = { income: 0, expense: 0 };
            if (item.type === 'income') aggregated[key].income += item.amount;
            else aggregated[key].expense += item.amount;

            // Aggregate for pie chart
            if (item.type === 'income') pieIncomeData[item.category] = (pieIncomeData[item.category] || 0) + item.amount;
            else pieExpenseData[item.category] = (pieExpenseData[item.category] || 0) + item.amount;
        });

        const labels = Object.keys(aggregated).sort();
        const incomeValues = labels.map(l => aggregated[l].income);
        const expenseValues = labels.map(l => aggregated[l].expense);

        // Line chart
        const lineCtx = document.getElementById('lineChart').getContext('2d');
        if (lineChart) lineChart.destroy();
        lineChart = new Chart(lineCtx, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    { label: 'Income', data: incomeValues, borderColor: 'rgba(54,162,235,1)', backgroundColor: 'rgba(54,162,235,0.2)', fill: true, tension: 0.3 },
                    { label: 'Expense', data: expenseValues, borderColor: 'rgba(255,99,132,1)', backgroundColor: 'rgba(255,99,132,0.2)', fill: true, tension: 0.3 }
                ]
            },
            options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
        });

        // Bar chart
        const barCtx = document.getElementById('barChart').getContext('2d');
        if (barChart) barChart.destroy();
        barChart = new Chart(barCtx, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    { label: 'Income', data: incomeValues, backgroundColor: 'rgba(54,162,235,0.6)' },
                    { label: 'Expense', data: expenseValues, backgroundColor: 'rgba(255,99,132,0.6)' }
                ]
            },
            options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
        });

        // Pie charts
        updateChart('expenseChart', pieExpenseData, 'Expenses by Category');
        updateChart('incomeChart', pieIncomeData, 'Income by Category');
    }

    // Edit / Delete
    window.deleteTransaction = async function (id) {
        if (!confirm('Yakin mau dihapus?')) return;
        await fetch('backend.php?action=delete', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) });
        loadData();
    };

    window.editTransaction = function (button) {
        const item = JSON.parse(button.dataset.item);
        form.date.value = item.date;
        form.type.value = item.type;
        form.category.value = item.category;
        form.description.value = item.description;
        form.amount.value = item.amount;
        editId = item.id;
    };

    form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    data.amount = parseFloat(data.amount);

    // 🚨 Validasi wajib
    if (!data.date || !data.type || !data.category || !data.description) {
        alert("Semua field wajib diisi!");
        return; // stop
    }

    if (isNaN(data.amount) || data.amount <= 0) {
        alert("Amount harus berupa angka lebih besar dari 0!");
        return; // stop
    }

    // ✅ kalau lolos validasi baru kirim ke backend
    let url = editId ? 'backend.php?action=update' : 'backend.php?action=add';
    if (editId) data.id = editId;

    await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });

    form.reset();
    editId = null;
    loadData();
});

const uploadForm = document.getElementById('uploadForm');
uploadForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData(uploadForm);

    const res = await fetch('upload.php', {
        method: 'POST',
        body: formData
    });
    const result = await res.json();

    if (result.success) {
        alert(`Upload sukses! ${result.inserted} data dimasukkan.`);
        loadData(); // 🚀 reload tabel & chart
    } else {
        alert("Upload gagal: " + result.message);
    }
});

    loadData();
});
