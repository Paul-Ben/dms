<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DMS Receipt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
      body { background-color: #f8fafc; }
      .receipt-card { border-radius: 1rem; }
      .brand { height: 40px; }
      .watermark { position: relative; }
      .watermark::before {
        content: 'PAID';
        position: absolute;
        inset: 0;
        display: grid;
        place-items: center;
        font-weight: 800;
        font-size: clamp(48px, 12vw, 100px);
        color: rgba(25, 135, 84, 0.12); /* Bootstrap success color at 12% */
        transform: rotate(-18deg);
        pointer-events: none;
        z-index: 0;
      }
      .watermark > * { position: relative; z-index: 1; }
      @media print {
        .no-print { display: none !important; }
        .watermark::before { color: rgba(25, 135, 84, 0.18); }
      }
    </style>
</head>
<body>
  <section class="container py-4 py-md-5">
    <div class="row justify-content-center">
      <div class="col-12 col-lg-10 col-xl-9">
        <div id="receipt" class="card shadow-sm receipt-card">
          <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
              <img src="{{ asset('BDIC Logo with name PNG.png') }}" alt="BDIC DMS Logo" class="brand" style="height: 40px; width: auto;">
              <h2 class="mt-3 mb-0 fw-semibold">Payment Receipt</h2>
            </div>

            <div class="row g-4 mb-4">
              <div class="col-12 col-md-7">
                <h6 class="text-uppercase text-muted mb-2">Received From</h6>
                <div class="small">
                  <div class="fw-semibold">{{ $authUser->name ?? 'User' }}</div>
                  <div>Phone: {{ $user->userDetail->phone_number ?? '—' }}</div>
                  <div>Email: {{ $user->email ?? '—' }}</div>
                </div>
              </div>
              <div class="col-12 col-md-5">
                <div class="row small gy-1">
                  <div class="col-6 fw-semibold">Receipt #</div>
                  <div class="col-6 text-end">{{ $receipt->reference ?? ('RCT-' . ($receipt->id ?? '000')) }}</div>
                  <div class="col-6 fw-semibold">Date</div>
                  <div class="col-6 text-end">{{ date('Y-m-d', strtotime($receipt->transDate)) }}</div>
                  <div class="col-6 fw-semibold">Time</div>
                  <div class="col-6 text-end">{{ date('H:i:s', strtotime($receipt->transDate)) }}</div>
                </div>
              </div>
            </div>

            <h6 class="text-uppercase text-muted mb-2">Being payment for</h6>
            <div class="watermark rounded-3 border">
              <div class="table-responsive">
                <table class="table align-middle mb-0">
                  <thead class="table-light">
                    <tr class="text-muted text-uppercase small">
                      <th scope="col">Qty</th>
                      <th scope="col">Description</th>
                      <th scope="col" class="text-end">Unit Price</th>
                      <th scope="col" class="text-end">Amount</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>1</td>
                      <td>Document Filing {{ $receipt->document_no ?? ($receipt->docuent_number ?? '') }}</td>
                      <td class="text-end">₦{{ number_format($receipt->transAmount, 2) }}</td>
                      <td class="text-end">₦{{ number_format($receipt->transAmount, 2) }}</td>
                    </tr>
                    <tr>
                      <td colspan="2"></td>
                      <td class="text-end fw-semibold text-muted">Subtotal</td>
                      <td class="text-end">₦{{ number_format($receipt->transAmount, 2) }}</td>
                    </tr>
                    <tr>
                      <td colspan="2"></td>
                      <td class="text-end fw-semibold text-muted">Processing fee</td>
                      <td class="text-end">₦{{ number_format($receipt->transFee, 2) }}</td>
                    </tr>
                    <tr>
                      <td colspan="2"></td>
                      <td class="text-end fw-bold">Total</td>
                      <td class="text-end fw-bold">₦{{ number_format($receipt->transTotal, 2) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <p class="mt-3 small text-muted">Received with thanks.</p>

            <div class="row g-4 mt-2 align-items-start">
              <div class="col-12 col-md-7">
                <h6 class="text-uppercase text-muted mb-2">Payment Details</h6>
                <div class="small">
                  <div>Channel: {{ $receipt->payment_channel ?? 'Card' }}</div>
                  <div>Gateway: {{ $receipt->gateway ?? 'Paystack' }}</div>
                  <div>Transaction ID: {{ $receipt->transaction_id ?? ($receipt->reference ?? '—') }}</div>
                  <div>Reference: {{ $receipt->reference ?? ('RCT-' . ($receipt->id ?? '000')) }}</div>
                </div>
              </div>
              <div class="col-12 col-md-5">
                <h6 class="text-uppercase text-muted mb-2">Verification</h6>
                <div class="border rounded-3 p-3 d-flex gap-3 align-items-center">
                  <div id="qr" class="flex-shrink-0" aria-label="Receipt QR code" role="img"></div>
                  <div class="small text-muted">
                    Scan to verify receipt details<br>
                    Ref: {{ $receipt->reference ?? ('RCT-' . ($receipt->id ?? '000')) }}
                  </div>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 no-print">
              <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" onclick="downloadReceipt()">
                <i class="bi bi-download"></i>
                <span>Download</span>
              </button>
              <button type="button" class="btn btn-success d-inline-flex align-items-center gap-2" onclick="printReceipt()">
                <i class="bi bi-printer"></i>
                <span>Print</span>
              </button>
            </div>

          </div>
        </div>
      </div>
    </div>
  </section>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  <script>
    function downloadReceipt() {
      const source = document.getElementById('receipt').cloneNode(true);
      // Clean any non-printable elements
      source.querySelectorAll('.no-print').forEach(el => el.remove());

      const options = {
        margin: 10,
        filename: 'receipt-{{ $receipt->reference ?? ('RCT-' . ($receipt->id ?? '000')) }}.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
      };
      html2pdf().from(source).set(options).save();
    }

    function printReceipt() {
      const source = document.getElementById('receipt').cloneNode(true);
      const w = window.open('', '', 'height=900,width=800');
      w.document.write('<html><head><title>Print Receipt</title>');
      w.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">');
      w.document.write('<style>@media print { .watermark::before { color: rgba(25, 135, 84, 0.18); } .brand { height: 40px !important; width: auto !important; } }</style>');
      w.document.write('</head><body>');
      w.document.write(source.outerHTML);
      w.document.write('</body></html>');
      w.document.close();
      w.focus();
      w.print();
    }

    // Generate QR code with key receipt details
    (function generateQR() {
      var qrElem = document.getElementById('qr');
      if (!qrElem || typeof QRCode === 'undefined') return;
      var qrText = [
        'BDIC DMS Receipt',
        'Ref: {{ $receipt->reference ?? ('RCT-' . ($receipt->id ?? '000')) }}',
        'Amount: ₦{{ number_format($receipt->transTotal ?? ($receipt->transAmount + ($receipt->transFee ?? 0)), 2) }}',
        'Date: {{ date('Y-m-d H:i:s', strtotime($receipt->transDate)) }}'
      ].join('\n');
      new QRCode(qrElem, {
        text: qrText,
        width: 96,
        height: 96,
        colorDark: '#212529',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M
      });
    })();
  </script>
</body>
</html>
