<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pemesanan Pelanggan</title>
  <link rel="stylesheet" href="/assets/css/pelanggan.css">
</head>
<body>
  <div class="container">
    <h1>🍛 Menu Restoran</h1>

    <div class="input-meja">
      <label for="noMeja">Nomor Meja:</label>
      <input type="number" id="noMeja" placeholder="Masukkan nomor meja" min="1" required>
    </div>

    <div id="menu-list" class="menu-grid">
      <!-- Nanti kamu bisa ganti gambar & harga sesuai data real -->
      <div class="menu-card" data-nama="Nasi Goreng" data-harga="25000">
        <img src="/assets/images/nasigoreng.jpg" alt="Nasi Goreng">
        <h3>Nasi Goreng</h3>
        <p>Rp25.000</p>
        <button class="add-btn">Tambah</button>
      </div>

      <div class="menu-card" data-nama="Ayam Bakar" data-harga="30000">
        <img src="/assets/images/ayambakar.jpg" alt="Ayam Bakar">
        <h3>Ayam Bakar</h3>
        <p>Rp30.000</p>
        <button class="add-btn">Tambah</button>
      </div>

      <div class="menu-card" data-nama="Sate Ayam" data-harga="28000">
        <img src="/assets/images/sate.jpg" alt="Sate Ayam">
        <h3>Sate Ayam</h3>
        <p>Rp28.000</p>
        <button class="add-btn">Tambah</button>
      </div>
    </div>

    <div class="order-box">
      <h2>🧾 Pesanan Anda</h2>
      <ul id="order-list"></ul>
      <button id="submit-order" class="btn-submit">Kirim Pesanan</button>
      <p id="status"></p>
    </div>
  </div>

  <script type="module">
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
  </script>
</body>
</html>
