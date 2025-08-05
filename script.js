    document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('transactionForm');
    const list = document.getElementById('transactionList');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        data.amount = parseFloat(data.amount);

        console.log('Mengirim data:', data); // Debug log

        try {
        const res = await fetch('backend.php?action=add', {
            method: 'POST',
            headers: {
            'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        console.log('Hasil respon:', result); // Debug log

        if (result.status === 'success') {
            form.reset();
            loadData(); // muat ulang data
        }
        } catch (err) {
        console.error('Gagal mengirim data:', err);
        }
    });

    async function loadData() {
        try {
        const res = await fetch('backend.php?action=list');
        const data = await res.json();
        console.log('Data dari backend:', data); // Debug log

        list.innerHTML = '';
        data.forEach(item => {
            const div = document.createElement('div');
            div.textContent = `${item.date} - ${item.type} - ${item.category} - ${item.description} - Rp${item.amount}`;
            list.appendChild(div);
        });
        } catch (err) {
        console.error('Gagal memuat data:', err);
        }
    }

    // Panggil saat pertama kali
    loadData();
    });
