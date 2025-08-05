    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Finance Tracker</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100 text-gray-800">

    <div class="max-w-4xl mx-auto p-6">
        <h1 class="text-3xl font-bold mb-6 text-center">💰 Finance Tracker</h1>
        <button id="downloadExcel" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded my-4">
        Download Excel
        </button>

        <!-- Form -->
        <form id="transactionForm" class="bg-white shadow-md rounded px-6 py-4 mb-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="date" name="date" required class="border p-2 rounded w-full" />
            <select name="type" required class="border p-2 rounded w-full">
            <option value="income">Income</option>
            <option value="expense">Expense</option>
            </select>
            <select name="category" required class="border p-2 rounded w-full">
            <option value="">-- Select Category --</option>
            <option value="Food">Food</option>
            <option value="Transport">Transport</option>
            <option value="Salary">Salary</option>
            <option value="Entertainment">Entertainment</option>
            <option value="Others">Others</option>
            </select>
            <input type="text" name="description" placeholder="Description" required class="border p-2 rounded w-full" />
            <input type="number" name="amount" placeholder="Amount" required class="border p-2 rounded w-full" />
        </div>
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Add</button>
        </form>

        <!-- Transaction List -->
        <h2 class="text-xl font-semibold mb-2">🧾 Transaction List</h2>
        <div id="transactionList" class="space-y-2 mb-6"></div>

        <!-- Charts -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <h2 class="text-lg font-semibold mb-2 text-center">📊 Expense Chart</h2>
            <canvas id="expenseChart" height="200"></canvas>
        </div>
        <div>
            <h2 class="text-lg font-semibold mb-2 text-center">📈 Income Chart</h2>
            <canvas id="incomeChart" height="200"></canvas>
        </div>
        </div>
    </div>

    <script>
        document.getElementById('downloadExcel').addEventListener('click', () => {
        window.location.href = 'backend.php?action=download_excel';
        });

        document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('transactionForm');
        const list = document.getElementById('transactionList');
        let editId = null;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            data.amount = parseFloat(data.amount);

            let url = 'backend.php?action=add';
            if (editId !== null) {
            url = 'backend.php?action=update';
            data.id = editId;
            }

            const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
            });

            form.reset();
            editId = null;
            loadData();
        });

        window.deleteTransaction = async function (id) {
            if (!confirm('Yakin mau dihapus?')) return;
            await fetch('backend.php?action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
            });
            loadData();
        }

        window.editTransaction = function (button) {
            const item = JSON.parse(button.dataset.item);
            form.date.value = item.date;
            form.type.value = item.type;
            form.category.value = item.category;
            form.description.value = item.description;
            form.amount.value = item.amount;
            editId = item.id;
        }

        let expenseChart = null;
        let incomeChart = null;

        async function loadData() {
            const res = await fetch('backend.php?action=list');
            const data = await res.json();
            list.innerHTML = '';

            const expenseData = {};
            const incomeData = {};

            data.forEach(item => {
            const div = document.createElement('div');
            div.className = "bg-white p-3 rounded shadow flex justify-between items-center";
            div.innerHTML = `
                <div>
                <p><strong>${item.date}</strong> - ${item.type} - ${item.category} - ${item.description} - <strong>Rp${item.amount.toLocaleString()}</strong></p>
                </div>
                <div class="space-x-2">
                <button data-item='${JSON.stringify(item)}' onclick="editTransaction(this)" class="bg-yellow-400 px-3 py-1 rounded text-white">Edit</button>
                <button onclick="deleteTransaction(${item.id})" class="bg-red-500 px-3 py-1 rounded text-white">Delete</button>
                </div>
            `;
            list.appendChild(div);

            if (item.type === 'expense') {
                expenseData[item.category] = (expenseData[item.category] || 0) + item.amount;
            } else if (item.type === 'income') {
                incomeData[item.category] = (incomeData[item.category] || 0) + item.amount;
            }
            });

            updateChart('expenseChart', expenseData, 'Expenses by Category', expenseChart => expenseChart = expenseChart);
            updateChart('incomeChart', incomeData, 'Income by Category', incomeChart => incomeChart = incomeChart);
        }

        function updateChart(canvasId, chartData, chartTitle, chartSetter) {
            const ctx = document.getElementById(canvasId).getContext('2d');
            const labels = Object.keys(chartData);
            const values = Object.values(chartData);

            const chartRef = canvasId === 'expenseChart' ? expenseChart : incomeChart;
            if (chartRef) chartRef.destroy();

            const newChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                data: values,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.6)',
                    'rgba(54, 162, 235, 0.6)',
                    'rgba(255, 206, 86, 0.6)',
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(153, 102, 255, 0.6)',
                    'rgba(255, 159, 64, 0.6)'
                ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                legend: { position: 'bottom' },
                title: {
                    display: true,
                    text: chartTitle
                }
                }
            }
            });

            if (canvasId === 'expenseChart') {
            expenseChart = newChart;
            } else {
            incomeChart = newChart;
            }
        }

        loadData();
        });
    </script>
    </body>
    </html>
