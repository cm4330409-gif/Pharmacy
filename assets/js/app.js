// PharmaCare — App JS

// ── Auto-dismiss alerts ─────────────────────────────
document.querySelectorAll('.alert').forEach(el => {
  setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity .5s'; setTimeout(() => el.remove(), 500); }, 4000);
});

// ── Confirm delete ──────────────────────────────────
document.querySelectorAll('[data-confirm]').forEach(el => {
  el.addEventListener('click', e => {
    if (!confirm(el.dataset.confirm || 'Are you sure?')) e.preventDefault();
  });
});

// ── Sale builder: add row ───────────────────────────
function addSaleRow() {
  const tbody = document.getElementById('sale-rows');
  if (!tbody) return;
  const idx = tbody.children.length;
  const medOptions = window.medicineOptions || '';
  const row = document.createElement('tr');
  row.innerHTML = `
    <td>
      <select name="items[${idx}][medicine_id]" class="med-select" onchange="updatePrice(this,${idx})" required>
        <option value="">-- Select --</option>${medOptions}
      </select>
    </td>
    <td><input type="number" name="items[${idx}][quantity]" class="qty-inp" min="1" value="1" onchange="calcRow(${idx})" required style="width:70px"></td>
    <td><input type="number" name="items[${idx}][unit_price]" class="price-inp" step="0.01" readonly style="width:90px"></td>
    <td><strong class="subtotal-cell">0.00</strong></td>
    <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove();calcTotal()">✕</button></td>
  `;
  tbody.appendChild(row);
}

function updatePrice(sel, idx) {
  const opt = sel.selectedOptions[0];
  const price = opt ? (opt.dataset.price || 0) : 0;
  const row = sel.closest('tr');
  row.querySelector('.price-inp').value = parseFloat(price).toFixed(2);
  calcRow(idx);
}

function calcRow(idx) {
  const rows = document.querySelectorAll('#sale-rows tr');
  rows.forEach((row, i) => {
    const qty   = parseFloat(row.querySelector('.qty-inp')?.value || 0);
    const price = parseFloat(row.querySelector('.price-inp')?.value || 0);
    const sub   = row.querySelector('.subtotal-cell');
    if (sub) sub.textContent = (qty * price).toFixed(2);
  });
  calcTotal();
}

function calcTotal() {
  let total = 0;
  document.querySelectorAll('.subtotal-cell').forEach(el => {
    total += parseFloat(el.textContent || 0);
  });
  const disc = parseFloat(document.getElementById('discount')?.value || 0);
  const net  = Math.max(0, total - disc);
  const totalEl = document.getElementById('grand-total');
  if (totalEl) totalEl.textContent = net.toFixed(2);
  const hiddenTotal = document.getElementById('total-hidden');
  if (hiddenTotal) hiddenTotal.value = net.toFixed(2);
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', e => {
  const sidebar = document.querySelector('.sidebar');
  const toggle  = document.querySelector('.menu-toggle');
  if (sidebar && sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target !== toggle) {
    sidebar.classList.remove('open');
  }
});
