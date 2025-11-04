 // Ambil config Firebase
    const res = await fetch('/firebase/config');
    const config = await res.json();

    const { initializeApp } = await import('https://www.gstatic.com/firebasejs/11.0.1/firebase-app.js');
    const { getDatabase, ref, push, set } = await import('https://www.gstatic.com/firebasejs/11.0.1/firebase-database.js');

    const app = initializeApp(config);
    const db = getDatabase(app);

    const orderList = document.getElementById('order-list');
    const statusEl = document.getElementById('status');
    const noMejaEl = document.getElementById('noMeja');
    let pesanan = [];

    document.querySelectorAll('.add-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const card = btn.closest('.menu-card');
        const nama = card.dataset.nama;
        const harga = parseInt(card.dataset.harga);
        pesanan.push({ nama, harga });

        renderOrderList();
      });
    });

    function renderOrderList() {
      orderList.innerHTML = '';
      pesanan.forEach((p, i) => {
        const li = document.createElement('li');
        li.innerHTML = `${p.nama} - Rp${p.harga.toLocaleString()} 
          <button class="hapus" data-i="${i}">❌</button>`;
        orderList.appendChild(li);
      });

      // tombol hapus
      document.querySelectorAll('.hapus').forEach(btn => {
        btn.addEventListener('click', e => {
          pesanan.splice(e.target.dataset.i, 1);
          renderOrderList();
        });
      });
    }

    // Kirim pesanan ke Firebase
    document.getElementById('submit-order').addEventListener('click', async () => {
      const noMeja = noMejaEl.value.trim();

      if (!noMeja || pesanan.length === 0) {
        statusEl.textContent = '⚠️ Isi nomor meja dan pilih menu!';
        statusEl.style.color = 'red';
        return;
      }

      try {
        const pesananRef = ref(db, 'pesanan');
        const newPesanan = push(pesananRef);
        await set(newPesanan, {
          noMeja,
          listMenu: pesanan,
          status: 'menunggu'
        });

        pesanan = [];
        renderOrderList();
        noMejaEl.value = '';
        statusEl.textContent = '✅ Pesanan dikirim!';
        statusEl.style.color = 'green';
      } catch (err) {
        statusEl.textContent = '❌ Gagal mengirim pesanan!';
        statusEl.style.color = 'red';
      }
    });
