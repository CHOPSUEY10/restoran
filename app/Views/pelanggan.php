<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pemesanan Pelanggan</title>
  <link rel="stylesheet" href="/assets/css/pelanggan.css">
</head>
<body>
  <div class="container">
    <h1>🍛 Restoran CodeIgniter 🔥 </h1>

    <div class="input-meja">
      <label for="noMeja">Nomor Meja</label>
      <input type="number" id="noMeja" placeholder="Masukkan nomor meja" min="1" required>
    </div>

    <div id="menu-list" class="menu-grid">
      <!-- Nanti kamu bisa ganti gambar & harga sesuai data real -->
      <div class="menu-card" data-nama="Nasi Goreng" data-harga="25000">
        <img src="/assets/images/nasgor.jpg" alt="Nasi Goreng">
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
        <img src="/assets/images/sateayam.jpg" alt="Sate Ayam">
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

  <script type="module" src="<?= base_url('assets/js/pelanggan.js')?>"></script>

</body>
</html>
