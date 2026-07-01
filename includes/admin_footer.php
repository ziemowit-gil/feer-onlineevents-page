</div>

<!-- Modal: przeglądarka plików R2 (uzupełniana przez JS z admin/r2_list.php) -->
<div class="modal fade" id="r2Modal" tabindex="-1" aria-labelledby="r2ModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="r2ModalLabel"><i class="bi bi-cloud me-2"></i>Wybierz plik z R2</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
      </div>
      <div class="modal-body">
        <input type="text" id="r2ModalFilter" class="form-control form-control-sm mb-3" placeholder="Filtruj po nazwie…">
        <div id="r2ModalBody">
          <p class="text-muted small">Ładowanie…</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
  var modalEl = document.getElementById('r2Modal');
  if (!modalEl || typeof bootstrap === 'undefined') return;
  var modal     = new bootstrap.Modal(modalEl);
  var bodyEl    = document.getElementById('r2ModalBody');
  var filterEl  = document.getElementById('r2ModalFilter');
  var targetInput = null;
  var allFiles  = [];

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c];
    });
  }

  function humanSize(b) {
    var u = ['B','KB','MB','GB'], i = 0, n = b;
    while (n >= 1024 && i < u.length - 1) { n /= 1024; i++; }
    return (i === 0 ? b : n.toFixed(1)) + ' ' + u[i];
  }

  function render(files) {
    if (!files.length) {
      bodyEl.innerHTML = '<p class="text-muted small mb-0">Brak plików.</p>';
      return;
    }
    var html = '<div class="list-group">';
    files.forEach(function (f) {
      var disabled = f.url ? '' : 'disabled';
      html += '<button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center r2-file-item" ' +
        'data-url="' + esc(f.url) + '" ' + disabled + '>' +
        '<span class="text-break me-2"><i class="bi bi-file-earmark me-1 text-muted"></i>' + esc(f.key) + '</span>' +
        '<span class="badge bg-light text-dark flex-shrink-0">' + humanSize(f.size) + '</span>' +
        '</button>';
    });
    html += '</div>';
    bodyEl.innerHTML = html;

    bodyEl.querySelectorAll('.r2-file-item').forEach(function (item) {
      item.addEventListener('click', function () {
        if (targetInput && item.dataset.url) {
          targetInput.value = item.dataset.url;
          targetInput.dispatchEvent(new Event('input'));
        }
        modal.hide();
      });
    });
  }

  function loadFiles() {
    bodyEl.innerHTML = '<p class="text-muted small">Ładowanie…</p>';
    fetch('r2_list.php')
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.ok) {
          bodyEl.innerHTML = '<div class="alert alert-warning mb-0">' + esc(data.error || 'Błąd pobierania listy plików.') + '</div>';
          allFiles = [];
          return;
        }
        allFiles = data.files || [];
        if (!data.public_base) {
          bodyEl.innerHTML = '<div class="alert alert-warning mb-0">Ustaw „Publiczny adres bazowy” w Ustawieniach → R2, żeby móc wstawiać linki.</div>';
          return;
        }
        render(allFiles);
      })
      .catch(function () {
        bodyEl.innerHTML = '<div class="alert alert-danger mb-0">Błąd połączenia.</div>';
      });
  }

  filterEl.addEventListener('input', function () {
    var q = filterEl.value.trim().toLowerCase();
    render(q ? allFiles.filter(function (f) { return f.key.toLowerCase().indexOf(q) !== -1; }) : allFiles);
  });

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.btn-r2-browse');
    if (!btn) return;
    targetInput = btn.dataset.target
      ? document.getElementById(btn.dataset.target)
      : btn.closest('.rec-row').querySelector('input[type=url]');
    filterEl.value = '';
    modal.show();
    loadFiles();
  });
})();
</script>
</body>
</html>
