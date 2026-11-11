<?php if (!empty($pdf)): ?><!doctype html><html><head><meta charset="utf-8"><style>@page{margin:18mm 16mm}body{font-family:'DejaVu Sans',sans-serif;font-size:11px;color:#111}</style></head><body><?php endif; ?>
<style>
.doc{font-size:12px;color:#111;line-height:1.5}.doc table{border-collapse:collapse;width:100%}
.doc .kop td{border-bottom:3px double #111;padding-bottom:8px}.doc h2{text-align:center;margin:14px 0 2px;font-size:16px;letter-spacing:1px}
.doc .grid td{padding:2px 4px;vertical-align:top}.doc .det th,.doc .det td{border:1px solid #444;padding:5px 8px}.doc .det th{background:#eee;text-align:left}
.doc .r{text-align:right}
</style>
<div class="doc">
  <table class="kop"><tr>
    <td style="width:70px"><?php if (!empty($logo)): ?><img src="<?= $logo ?>" style="width:60px;height:60px"><?php endif; ?></td>
    <td><div style="font-size:16px;font-weight:bold"><?= e(setting('company_name', setting('site_name'))) ?></div>
      <div><?= e(setting('company_address')) ?></div>
      <div>Telp/WA: <?= e(setting('company_phone')) ?><?= setting('company_email') ? ' · ' . e(setting('company_email')) : '' ?></div></td>
  </tr></table>
  <h2>SLIP GAJI KARYAWAN</h2>
  <div style="text-align:center;margin-bottom:10px">No. <?= e($p['invoice_no']) ?> · Periode <?= e(bulan_id((int)$p['period_month']) . ' ' . $p['period_year']) ?></div>
  <table class="grid">
    <tr><td style="width:120px">ID Karyawan</td><td>: <?= e($p['employee_code']) ?></td><td style="width:110px">Jabatan/Role</td><td>: <?= e($p['role_name'] ?? '-') ?></td></tr>
    <tr><td>Nama</td><td>: <?= e($p['full_name']) ?></td><td>Status</td><td>: <?= $p['status'] === 'paid' ? 'Sudah Dibayar' : 'Belum Dibayar' ?></td></tr>
  </table>
  <br>
  <table class="det">
    <tr><th colspan="2">Rincian Kehadiran</th></tr>
    <tr><td>Hari hadir (dibayar)</td><td class="r"><?= (int)$p['days_present'] ?> hari</td></tr>
    <tr><td>Hari izin</td><td class="r"><?= (int)$p['days_leave'] ?> hari</td></tr>
    <tr><td>Hari tanpa keterangan</td><td class="r"><?= (int)$p['days_absent'] ?> hari</td></tr>
    <tr><th colspan="2">Rincian Gaji</th></tr>
    <tr><td>Tarif gaji per hari</td><td class="r"><?= rupiah($p['daily_rate']) ?></td></tr>
    <tr><td>Total gaji kotor (<?= (int)$p['days_present'] ?> × <?= rupiah($p['daily_rate']) ?>)</td><td class="r"><?= rupiah($p['gross']) ?></td></tr>
    <tr><td>Potongan<?= $p['deduction_note'] ? ' — ' . e($p['deduction_note']) : '' ?></td><td class="r">- <?= rupiah($p['deduction']) ?></td></tr>
    <tr><th>TOTAL GAJI BERSIH</th><th class="r"><?= rupiah($p['net']) ?></th></tr>
  </table>
</div>
<?php if (!empty($pdf)): ?></body></html><?php endif; ?>
