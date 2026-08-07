<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Test Ruler {{ $mm }}mm</title>
    <style>
        * {
            margin: 0; padding: 0; box-sizing: border-box;
            /* Browser default-nya TIDAK mencetak warna latar (background) untuk
               hemat tinta, kecuali dipaksa begini. Tanpa ini, kotak hitam & garis
               penggaris di bawah tidak akan muncul di kertas walau tampak normal
               di layar/preview. */
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            color-adjust: exact;
        }
        body { font-family: monospace; font-size: 12px; color: #000; background: #eee; }
        #page {
            width: {{ $mm }}mm;
            background: #fff;
            margin: 10px auto;
            border: 1px solid #999;
        }
        .bar {
            width: 100%;
            height: 0;
            background: #000;
            /* border SELALU ikut tercetak (beda dari background-color di atas
               yang butuh print-color-adjust) — dipakai sebagai sinyal cadangan
               yang paling bisa diandalkan untuk menguji lebar cetak sebenarnya. */
            border-top: 6mm solid #000;
            border-bottom: 6mm solid #000;
        }
        .ticks {
            position: relative;
            width: 100%;
            height: 6mm;
        }
        .tick {
            position: absolute;
            top: 0;
            width: 1px;
            height: 100%;
            background: #000;
            border-left: 1px solid #000;
        }
        .tick span {
            position: absolute;
            top: 6mm;
            left: -8px;
            font-size: 9px;
        }
        .info { padding: 4mm; font-size: 11px; }
        .no-print { text-align: center; margin: 12px 0; }
        @media print {
            body { background: #fff; }
            #page { margin: 0; border: none; }
            .no-print { display: none; }
            @page { size: {{ $mm }}mm auto; margin: 0; }
        }
    </style>
</head>
<body>
    <div id="page">
        <!-- Balok hitam PENUH selebar {{ $mm }}mm — kalau ini overflow / terpotong
             di kertas fisik, berarti masalahnya murni di printer/driver, bukan di
             kode struk aplikasi. -->
        <div class="bar"></div>
        <div class="ticks">
            @for ($i = 0; $i <= $mm; $i += 10)
                <div class="tick" style="left: {{ $i }}mm;"><span>{{ $i }}</span></div>
            @endfor
        </div>
        <div class="info">
            Halaman uji ini seharusnya persis {{ $mm }}mm, balok hitam di atas mengisi PENUH dari tepi kiri sampai tepi kanan kertas tanpa terpotong ataupun bersisa. Garis+angka di bawahnya adalah penggaris tiap 10mm untuk dibandingkan dengan penggaris fisik.
        </div>
    </div>
    <div class="no-print">
        <button onclick="window.print()" style="padding:8px 20px;font-size:14px;">Cetak Halaman Uji Ini</button>
    </div>
</body>
</html>
