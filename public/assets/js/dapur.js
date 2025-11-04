    // Ambil konfigurasi dari server (berasal dari file .env)
    const res = await fetch('/firebase/config');
    const config = await res.json();

    // Import modul Firebase
    const { initializeApp } = await import('https://www.gstatic.com/firebasejs/11.0.1/firebase-app.js');
    const { getDatabase, ref, onValue, update, remove } = await import('https://www.gstatic.com/firebasejs/11.0.1/firebase-database.js');

    // Inisialisasi Firebase
    const app = initializeApp(config);
    const db = getDatabase(app);
    const pesananRef = ref(db, 'pesanan');
    const container = document.getElementById('pesanan-container');

    // Listener untuk setiap perubahan data
    onValue(pesananRef, (snapshot) => {
      container.innerHTML = '';
      const data = snapshot.val();

      if (!data) {
        container.innerHTML = '<p class="empty">Belum ada pesanan masuk 🍽️</p>';
        return;
      }

      Object.entries(data).forEach(([id, pesanan]) => {
        // Jika pesanan sudah selesai, hapus dari Firebase dan lewati
        if (pesanan.status === 'selesai') {
          remove(ref(db, `pesanan/${id}`));
          return;
        }

        // Buat card pesanan
        const card = document.createElement('div');
        card.className = `card ${pesanan.status}`;

        // Hitung total harga
        const total = pesanan.listMenu?.reduce((sum, item) => sum + (item.harga || 0), 0) || 0;

        // Render tampilan pesanan
        card.innerHTML = `
          <h2>Meja ${pesanan.noMeja}</h2>
          <ul>
            ${pesanan.listMenu
              ?.map(m => `<li>${m.nama} - Rp${m.harga?.toLocaleString() || 0}</li>`)
              .join('') || ''}
          </ul>
          <p><strong>Total:</strong> Rp${total.toLocaleString()}</p>
          <p>Status: <span>${pesanan.status}</span></p>
          <button class="btn" data-id="${id}">
            ${pesanan.status === 'menunggu' ? 'Proses' :
              pesanan.status === 'proses' ? 'Selesai' :
              'Selesai ✅'}
          </button>
        `;

        // Aksi tombol
        const btn = card.querySelector('.btn');
        if (pesanan.status === 'menunggu' || pesanan.status === 'proses') {
          btn.addEventListener('click', () => {
            const nextStatus = pesanan.status === 'menunggu' ? 'proses' : 'selesai';
            update(ref(db, `pesanan/${id}`), { status: nextStatus });
          });
        } else {
          btn.disabled = true;
        }

        container.appendChild(card);
      });
    });