const employees = Array.isArray(window.__TA_CONFIG?.employees)
  ? window.__TA_CONFIG.employees
  : [];
  let currentQr = null;

  function renderQrFor(id, name) {
    const container = document.getElementById('empQrcode');
    container.innerHTML = '';
    currentQr = new QRCode(container, {
      text: String(id),
      width: 220,
      height: 220,
      correctLevel: QRCode.CorrectLevel.H,
      colorDark: '#0d47a1',
      colorLight: '#ffffff'
    });
    document.getElementById('empQrName').textContent = name;
    $('#empQrModal').modal('show');
  }

  function performPrint() {
    const content = document.getElementById('empQrcode').innerHTML;
    const name = document.getElementById('empQrName').textContent;
    const win = window.open('', '', 'width=420,height=560');
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

  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.viewQrBtn').forEach(btn => {
      btn.addEventListener('click', () => {
        renderQrFor(btn.dataset.id, btn.dataset.name);
      });
    });

    const searchInput = document.getElementById('empSearch');
    const tableRows = document.querySelectorAll('#empTable tbody tr');

    searchInput.addEventListener('input', function() {
      const term = this.value.trim().toLowerCase();
      tableRows.forEach(tr => {
        const text = tr.textContent.toLowerCase();
        tr.style.display = text.includes(term) ? '' : 'none';
      });
    });

    document.getElementById('printEmpQr').addEventListener('click', performPrint);
  });
