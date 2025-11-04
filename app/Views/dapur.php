<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Panel Dapur (Debuggable)</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="/assets/css/dapur.css" />
  <style>
    /* minimal inline style untuk memudahkan testing */
    body{font-family:Inter,system-ui,Arial;margin:20px;background:#f6f8fb}
    .container{max-width:1100px;margin:0 auto}
    h1{color:#333}
    #ordersContainer{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;margin-top:16px}
    .order-card{background:#fff;padding:16px;border-radius:12px;box-shadow:0 6px 18px rgba(20,20,30,0.06);position:relative;transition:transform .12s}
    .order-card.new{animation:pop .5s ease}
    @keyframes pop{0%{transform:scale(.96);opacity:.6}100%{transform:none;opacity:1}}
    .order-card h3{margin:0 0 8px;color:#111}
    .order-card ul{margin:0 0 8px;padding-left:18px}
    .order-card .meta{display:flex;justify-content:space-between;align-items:center;gap:8px}
    .order-card .status{font-weight:700}
    .actions{margin-top:10px;text-align:right}
    .btn{padding:8px 12px;border-radius:8px;border:0;color:#fff;font-weight:700;cursor:pointer}
    .btn.process{background:#ffb020}
    .btn.done{background:#28a745}
    .btn[disabled]{background:#cfcfcf;cursor:not-allowed}
    .log{margin-top:12px;color:#666;font-size:0.9rem}
  </style>
</head>
<body>
  <div class="container">
    <h1>👨‍🍳 Panel Dapur</h1>
    <p>Menunggu pesanan... (cek console untuk debug)</p>

    <div id="ordersContainer"></div>

    <div class="log" id="debugLog"></div>

    <audio id="notif-audio" src="https://actions.google.com/sounds/v1/alarms/beep_short.ogg" preload="auto"></audio>
  </div>

<script type="module">
  // debug helper
  const debug = (msg) => {
    console.log(msg);
    const el = document.getElementById('debugLog');
    el.textContent = typeof msg === 'string' ? msg : JSON.stringify(msg);
  };

  try {
    // ambil config dari server
    const res = await fetch('/firebase/config');
    if (!res.ok) throw new Error('Gagal fetch /firebase/config: ' + res.status);
    const config = await res.json();
    debug('firebase config loaded');

    // inisialisasi firebase
    const { initializeApp } = await import('https://www.gstatic.com/firebasejs/11.0.1/firebase-app.js');
    const { getDatabase, ref, get, child, onValue, onChildAdded, update } = await import('https://www.gstatic.com/firebasejs/11.0.1/firebase-database.js');

    const app = initializeApp(config);
    const db = getDatabase(app);
    const audio = document.getElementById('notif-audio');
    const ordersContainer = document.getElementById('ordersContainer');

    // fungsi untuk render single order (aman terhadap berbagai bentuk data)
    function renderOrderCard(id, payload) {
      // normalisasi properti
      const meja = payload.noMeja ?? payload.meja ?? payload.table ?? '-';
      const status = payload.status ?? 'menunggu';
      // listMenu bisa array of objects, array of strings, atau object map
      let menuEntries = [];
      if (Array.isArray(payload.listMenu) || Array.isArray(payload.menu)) {
        const arr = payload.listMenu ?? payload.menu ?? [];
        menuEntries = arr.map(item => {
          if (item && typeof item === 'object') {
            const name = item.nama ?? item.name ?? item.n;
            const price = item.harga ?? item.price ?? 0;
            return { nama: name, harga: price };
          } else {
            return { nama: String(item), harga: 0 };
          }
        });
      } else if (payload.listMenu && typeof payload.listMenu === 'object') {
        // object map: { "Nasi Goreng": 2 } or { "Nasi Goreng": { harga:..., qty:... } }
        menuEntries = Object.entries(payload.listMenu).map(([k,v]) => {
          if (typeof v === 'number') return { nama: k, harga: 0, qty: v };
          if (v && typeof v === 'object') return { nama: k, harga: v.harga ?? v.price ?? 0, qty: v.qty ?? v.jumlah ?? 1 };
          return { nama: k, harga: 0 };
        });
      } else {
        menuEntries = [];
      }

      // hitung total
      let total = 0;
      menuEntries.forEach(it => { total += (it.harga ?? 0) * (it.qty ?? 1); });

      // buat elemen kartu
      const card = document.createElement('div');
      card.className = 'order-card ' + status;
      card.dataset.id = id;

      const menuHtml = menuEntries.map(it => {
        const priceText = it.harga ? ` - Rp${Number(it.harga).toLocaleString()}` : '';
        const qtyText = it.qty && it.qty > 1 ? ` x${it.qty}` : '';
        return `<li>${escapeHtml(it.nama)}${qtyText}${priceText}</li>`;
      }).join('');

      const btnLabel = status === 'menunggu' ? 'Proses' : (status === 'proses' ? 'Selesai' : 'Selesai ✅');

      card.innerHTML = `
        <h3>Meja ${escapeHtml(String(meja))}</h3>
        <ul>${menuHtml}</ul>
        <div class="meta"><div><strong>Total:</strong> Rp${total.toLocaleString()}</div><div class="status">${escapeHtml(status)}</div></div>
        <div class="actions"><button class="btn ${status==='menunggu'? 'process' : 'done'}" data-id="${id}" ${status==='selesai' ? 'disabled' : ''}>${btnLabel}</button></div>
      `;

      return card;
    }

    // helper untuk escape html sederhana
    function escapeHtml(s) {
      return String(s).replace(/[&<>"']/g, (m) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
    }

    // deteksi node aktif: coba 'orders' dulu, kalau kosong coba 'pesanan'
    const rootRef = ref(db, '/');
    const candidatePaths = ['orders', 'pesanan', 'pesanan', 'order', 'orders_list'];
    let activePath = null;

    debug('Mendeteksi node aktif (orders / pesanan) ...');
    for (const p of candidatePaths) {
      const snap = await get(child(rootRef, p));
      if (snap.exists()) {
        activePath = p;
        debug('Menemukan data di node: ' + p);
        break;
      } else {
        debug('Node kosong/tidak ada: ' + p);
      }
    }
    if (!activePath) {
      // default ke 'orders' (agar tetap memantau)
      activePath = 'orders';
      debug('Tidak menemukan node berisi data — menggunakan default path: ' + activePath);
    }

    const activeRef = ref(db, activePath);

    // gunakan onChildAdded untuk notifikasi saat ada order baru
    onChildAdded(activeRef, (snap) => {
      const id = snap.key;
      const payload = snap.val();
      // mainkan suara & beri efek highlight
      try { audio.currentTime = 0; audio.play().catch(()=>{}); } catch(e){}
      // highlight kartu baru sebentar
      const newCard = renderAndAttachSingle(id, payload, true);
      setTimeout(()=> newCard.classList.remove('new'), 1500);
    });

    // onValue untuk render keseluruhan saat data berubah
    onValue(activeRef, (snapshot) => {  
      const data = snapshot.val();
      ordersContainer.innerHTML = '';
      if (!data) {
        ordersContainer.innerHTML = '<p>Tidak ada pesanan saat ini</p>';
        return;
      }

      // render semua kartu, kecuali yang statusnya selesai
      Object.entries(data).reverse().forEach(([id, payload]) => {
        if (payload.status !== 'selesai') {
          renderAndAttachSingle(id, payload, false);
        }
      });
    });

    // render single helper & pasang event handler tombol
    function renderAndAttachSingle(id, payload, prepend=true) {
      // jika sudah ada kartu untuk id ini, hapus dulu (update)
      const existing = ordersContainer.querySelector(`[data-id="${id}"]`);
      if (existing) existing.remove();

      const card = renderOrderCard(id, payload);
      // pasang event handler async untuk tombol
      const btn = card.querySelector('button.btn');
      if (btn && !btn.disabled) {
        btn.addEventListener('click', async (ev) => {
          const currentState = payload.status ?? 'menunggu';
          const nextState = currentState === 'menunggu' ? 'proses' : (currentState === 'proses' ? 'selesai' : 'selesai');
          // disable sementara
          btn.disabled = true;
          btn.textContent = '...';
          try {
            await update(ref(db, `${activePath}/${id}`), { status: nextState });
            debug(`Status ${id} berhasil diubah → ${nextState}`);
          } catch (err) {
            console.error('Gagal update status:', err);
            alert('Gagal mengubah status. Periksa console (F12).');
          } finally {
            // re-enable handled by onValue update -> but re-enable in case
            btn.disabled = false;
          }
        });
      }

      if (prepend) ordersContainer.prepend(card); else ordersContainer.appendChild(card);
      return card;
    }

  } catch (err) {
    console.error('Inisialisasi gagal:', err);
    debug('Inisialisasi gagal: ' + (err && err.message ? err.message : err));
  }
</script>
</body>
</html>
