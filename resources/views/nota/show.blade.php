<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota {{ $transaction->transaction_code }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            padding: 20px;
        }

        .nota {
            background: #fff;
            width: 100%;
            max-width: 380px;
            padding: 24px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
        }

        .header {
            text-align: {{ $setting?->header_alignment === 'kiri' ? 'left' : ($setting?->header_alignment === 'kanan' ? 'right' : 'center') }};
            margin-bottom: 12px;
        }

        .header img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            border-radius: 50%;
            margin-bottom: 8px;
        }

        .header .outlet-name {
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .header .outlet-address {
            font-size: 11px;
            color: #555;
            margin-top: 4px;
            line-height: 1.5;
        }

        .header .header-note {
            font-size: 11px;
            color: #777;
            margin-top: 6px;
            font-style: italic;
        }

        .dashed {
            border: none;
            border-top: 1px dashed #aaa;
            margin: 10px 0;
        }

        .customer-name {
            font-size: 16px;
            font-weight: bold;
            margin: 10px 0;
            text-align: center;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            font-size: 11px;
            margin-bottom: 3px;
        }

        .row .label {
            flex: 1;
            color: #444;
        }

        .row .value {
            text-align: right;
            color: #222;
        }

        .row.bold {
            font-weight: bold;
            font-size: 12px;
        }

        .service-item {
            margin-bottom: 8px;
        }

        .service-name {
            font-size: 11px;
            font-weight: bold;
        }

        .service-detail {
            font-size: 11px;
            color: #555;
        }

        .qr-placeholder {
            text-align: center;
            margin: 16px 0 8px;
        }

        .qr-placeholder img {
            width: 100px;
            height: 100px;
        }

        .qr-label {
            font-size: 11px;
            text-align: center;
            color: #555;
        }

        .footer-note {
            font-size: 10px;
            text-align: center;
            color: #999;
            margin-top: 8px;
        }

        .powered-by {
            font-size: 10px;
            text-align: center;
            color: #bbb;
            margin-top: 12px;
        }

        .badge {
            display: inline-block;
            background: #e8f5e9;
            color: #2e7d32;
            font-size: 10px;
            font-weight: bold;
            padding: 2px 8px;
            border-radius: 12px;
            margin-bottom: 4px;
        }
    </style>
</head>
<body>
<div class="nota">

    {{-- ─── HEADER ──────────────────────────────────────────── --}}
    <div class="header">

        @if($setting?->show_logo && $setting?->logo_url)
            <img src="{{ $setting->logo_url }}" alt="Logo Outlet">
        @endif

        @if($setting?->show_nama_outlet)
            <div class="outlet-name">{{ strtoupper($transaction->outlet->name ?? 'NAMA OUTLET') }}</div>
        @endif

        @if($setting?->show_alamat_outlet)
            <div class="outlet-address">
                {{ $transaction->outlet->address ?? '' }}<br>
                {{ $transaction->outlet->phone   ?? '' }}
            </div>
        @endif

        @if($setting?->show_header_fisik && $setting?->header_note)
            <div class="header-note">{{ $setting->header_note }}</div>
        @endif

    </div>

    <hr class="dashed">

    {{-- ─── NAMA PELANGGAN ─────────────────────────────────── --}}
    @if($setting?->show_nama_pelanggan)
        <div class="customer-name">{{ $transaction->customer_name ?? '-' }}</div>
    @endif

    {{-- ─── DETAIL TRANSAKSI ───────────────────────────────── --}}
    <div class="row">
        <span class="label">ID Transaksi</span>
        <span class="value">{{ $transaction->transaction_code }}</span>
    </div>
    <div class="row">
        <span class="label">Waktu Masuk</span>
        <span class="value">{{ \Carbon\Carbon::parse($transaction->created_at)->format('d M Y | H:i') }}</span>
    </div>

    @if($setting?->show_estimasi_selesai && $transaction->estimated_completion)
        <div class="row">
            <span class="label">Estimasi Selesai</span>
            <span class="value">{{ \Carbon\Carbon::parse($transaction->estimated_completion)->format('d M Y | H:i') }}</span>
        </div>
    @endif

    @if($setting?->show_nama_kasir)
        <div class="row">
            <span class="label">Kasir</span>
            <span class="value">{{ $transaction->createdByEmployee?->name ?? $transaction->createdByUser?->name ?? '-' }}</span>
        </div>
    @endif

    <hr class="dashed">

    {{-- ─── LAYANAN ─────────────────────────────────────────── --}}
    @if($setting?->show_kategori_layanan && $transaction->items->count() > 0)
        <div class="section-title">LAYANAN</div>

        @foreach($transaction->items as $item)
            <div class="service-item">
                <div class="service-name">{{ $item->service?->name ?? '-' }}</div>
                <div class="row service-detail">
                    <span class="label">{{ $item->qty }} x Rp {{ number_format($item->price, 0, ',', '.') }}</span>
                    <span class="value">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                </div>
                @if($setting?->show_jumlah_potong && $item->packaging_qty)
                    <div class="service-detail">Item : {{ $item->packaging_qty }} PCS</div>
                @endif
            </div>
        @endforeach

        <hr class="dashed">
    @endif

    {{-- ─── PARFUM ──────────────────────────────────────────── --}}
    @if($setting?->show_parfum && $transaction->parfum)
        <div class="row">
            <span class="label">Parfum</span>
            <span class="value">{{ $transaction->parfum }}</span>
        </div>
        <hr class="dashed">
    @endif

    {{-- ─── RINCIAN BIAYA ──────────────────────────────────── --}}
    <div class="row">
        <span class="label">Subtotal</span>
        <span class="value">Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
    </div>

    @if($transaction->discount > 0)
        <div class="row">
            <span class="label">Diskon</span>
            <span class="value">- Rp {{ number_format($transaction->discount, 0, ',', '.') }}</span>
        </div>
    @endif

    @if($transaction->tax_amount > 0)
        <div class="row">
            <span class="label">Pajak</span>
            <span class="value">Rp {{ number_format($transaction->tax_amount, 0, ',', '.') }}</span>
        </div>
    @endif

    <hr class="dashed">

    <div class="row bold">
        <span class="label">Total</span>
        <span class="value">Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</span>
    </div>

    <hr class="dashed">

    {{-- ─── PEMBAYARAN ─────────────────────────────────────── --}}
    @if($transaction->payments->count() > 0)
        @php $payment = $transaction->payments->first(); @endphp

        <div style="margin-bottom: 4px;">
            <span class="badge">
                {{ strtoupper($transaction->payment_status === 'paid' ? 'LUNAS' : 'BELUM LUNAS') }}
                - {{ strtoupper($payment->paymentMethod?->name ?? '-') }}
            </span>
        </div>

        <div class="row">
            <span class="label">Dibayarkan</span>
            <span class="value">Rp {{ number_format($payment->amount_paid, 0, ',', '.') }}</span>
        </div>
        <div class="row">
            <span class="label">Kembalian</span>
            <span class="value">Rp {{ number_format($payment->change_amount, 0, ',', '.') }}</span>
        </div>
        <div class="row">
            <span class="label">Waktu Bayar</span>
            <span class="value">{{ \Carbon\Carbon::parse($payment->created_at)->format('d M Y | H:i') }}</span>
        </div>
    @else
        <div style="margin-bottom: 4px;">
            <span class="badge" style="background:#fff3e0; color:#e65100;">BELUM LUNAS</span>
        </div>
    @endif

    {{-- ─── QR CODE ─────────────────────────────────────────── --}}
    @if($setting?->show_qr_code)
        <hr class="dashed">
        <div class="qr-placeholder">
            {{-- Gunakan Google Charts API untuk generate QR --}}
            <img src="https://chart.googleapis.com/chart?chs=100x100&cht=qr&chl={{ urlencode($transaction->transaction_code) }}" alt="QR Code">
        </div>
        <div class="qr-label">Scan Untuk Pengambilan</div>
    @endif

    {{-- ─── FOOTER FISIK ────────────────────────────────────── --}}
    @if($setting?->show_footer_fisik && $setting?->header_note)
        <hr class="dashed">
        <div class="footer-note">{{ $setting->header_note }}</div>
    @endif

    {{-- ─── POWERED BY ──────────────────────────────────────── --}}
    @if($setting?->show_powered_by)
        <div class="powered-by">Powered by MendingLaundry Kasir</div>
    @endif

</div>
</body>
</html>