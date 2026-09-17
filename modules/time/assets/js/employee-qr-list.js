(function () {
  if (window.__TA_EMP_QR_LIST_INITIALIZED) {
    return;
  }
  window.__TA_EMP_QR_LIST_INITIALIZED = true;

  const employees = Array.isArray(window.__TA_CONFIG?.employees)
    ? window.__TA_CONFIG.employees
    : [];
  let currentQr = null;

  function showEmpQrModal() {
    const modalElement = document.getElementById('empQrModal');
    if (!modalElement) {
      return;
    }

    modalElement.classList.remove('hidden');
    modalElement.setAttribute('aria-hidden', 'false');
    document.body.classList.add('emp-qr-modal-open');
  }

  function hideEmpQrModal() {
    const modalElement = document.getElementById('empQrModal');
    if (!modalElement) {
      return;
    }

    modalElement.classList.add('hidden');
    modalElement.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('emp-qr-modal-open');
  }

  function renderQrFor(id, name) {
    const container = document.getElementById('empQrcode');
    const nameElement = document.getElementById('empQrName');

    if (!container || !nameElement) {
      return;
    }

    container.innerHTML = '';
    currentQr = new QRCode(container, {
      text: String(id),
      width: 220,
      height: 220,
      correctLevel: QRCode.CorrectLevel.H,
      colorDark: '#0d47a1',
      colorLight: '#ffffff'
    });

    nameElement.textContent = name;
    showEmpQrModal();
  }

  function performPrint() {
    const content = document.getElementById('empQrcode')?.innerHTML;
    const name = document.getElementById('empQrName')?.textContent || 'Employee QR';

    if (!content) {
      return;
    }

    const win = window.open('', '', 'width=420,height=560');
    if (!win) {
      return;
    }

    win.document.write(`
      <html>
        <head>
          <title>Print QR</title>
          <style>
            body {
              margin: 0;
              min-height: 100vh;
              display: flex;
              flex-direction: column;
              align-items: center;
              justify-content: center;
              font-family: Arial, sans-serif;
              background: #f8fbff;
              color: #0d47a1;
            }
            .qr-wrap {
              text-align: center;
              padding: 18px;
              border-radius: 10px;
              background: #ffffff;
              box-shadow: 0 6px 18px rgba(13, 71, 161, 0.12);
            }
            h3 {
              margin: 10px 0 0;
            }
          </style>
        </head>
        <body>
          <div class="qr-wrap">
            ${content}
            <h3>${name}</h3>
          </div>
        </body>
      </html>
    `);
    win.document.close();
    win.focus();
    win.print();
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.viewQrBtn').forEach(btn => {
      btn.addEventListener('click', () => {
        renderQrFor(btn.dataset.id, btn.dataset.name);
      });
    });

    document.querySelectorAll('[data-dismiss="modal"]').forEach(button => {
      button.addEventListener('click', hideEmpQrModal);
    });

    const modalElement = document.getElementById('empQrModal');
    if (modalElement) {
      modalElement.addEventListener('click', function (event) {
        if (event.target === modalElement) {
          hideEmpQrModal();
        }
      });
    }

    const searchInput = document.getElementById('empSearch');
    const tableRows = Array.from(document.querySelectorAll('#empTable tbody tr'));
    const qrPageSize = 10;
    let qrCurrentPage = 1;
    let qrSearchTerm = '';
    const qrPageInfo = document.getElementById('qrPageInfo');
    const qrPrev = document.getElementById('qrPrev');
    const qrNext = document.getElementById('qrNext');

    function getFilteredRows() {
      return tableRows.filter(row => {
        const text = row.textContent.toLowerCase();
        return text.includes(qrSearchTerm);
      });
    }

    function updateQrPagination() {
      const filteredRows = getFilteredRows();
      const totalPages = Math.max(1, Math.ceil(filteredRows.length / qrPageSize));

      if (qrCurrentPage > totalPages) {
        qrCurrentPage = totalPages;
      }

      const startIndex = (qrCurrentPage - 1) * qrPageSize;
      const endIndex = startIndex + qrPageSize;

      tableRows.forEach(row => {
        const rowIndex = filteredRows.indexOf(row);
        const isMatch = rowIndex !== -1;
        const onPage = isMatch && rowIndex >= startIndex && rowIndex < endIndex;
        row.style.display = isMatch && onPage ? '' : 'none';
      });

      if (qrPageInfo) {
        qrPageInfo.textContent = `Page ${qrCurrentPage} of ${totalPages} — ${filteredRows.length} employees`;
      }

      if (qrPrev) qrPrev.disabled = qrCurrentPage <= 1;
      if (qrNext) qrNext.disabled = qrCurrentPage >= totalPages;
    }

    if (searchInput && tableRows.length) {
      searchInput.addEventListener('input', function () {
        qrSearchTerm = this.value.trim().toLowerCase();
        qrCurrentPage = 1;
        updateQrPagination();
      });
    }

    if (qrPrev) {
      qrPrev.addEventListener('click', function () {
        if (qrCurrentPage > 1) {
          qrCurrentPage -= 1;
          updateQrPagination();
        }
      });
    }

    if (qrNext) {
      qrNext.addEventListener('click', function () {
        const filteredRows = getFilteredRows();
        const totalPages = Math.max(1, Math.ceil(filteredRows.length / qrPageSize));
        if (qrCurrentPage < totalPages) {
          qrCurrentPage += 1;
          updateQrPagination();
        }
      });
    }

    updateQrPagination();

    const printButton = document.getElementById('printEmpQr');
    if (printButton) {
      printButton.addEventListener('click', performPrint);
    }
  });
})();
