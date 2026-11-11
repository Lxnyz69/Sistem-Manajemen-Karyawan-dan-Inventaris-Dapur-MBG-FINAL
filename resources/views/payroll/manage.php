<?php
$locked = $payroll && (int)$payroll['is_final'] === 1;
$ro = !can('kelola_gaji.edit') && !can('kelola_gaji.create');   // hanya lihat: tanpa input/tombol
$view = $locked || $ro;
$cnt = ['hadir' => 0, 'izin' => 0, 'alpa' => 0];
$days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$offset = (int)date('N', strtotime(sprintf('%04d-%02d-01', $year, $month))) - 1;
$names = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
?>
<section class="panel panel-flush u-mb">
  <div class="panel-head"><div class="u-grow"><h2 class="panel-title">Pilih karyawan dan periode</h2></div></div>
  <form method="get" class="panel-body">
    <div class="filters-grid" style="grid-template-columns:repeat(2,minmax(0,1fr))">
      <div class="f-wide" style="grid-column:1/-1"><label class="label" for="mu">Karyawan</label>
        <select class="input" id="mu" name="user_id" required><option value="">— pilih karyawan —</option>
          <?php foreach ($emps as $x): ?><option value="<?= $x['id'] ?>" <?= $uid === (int)$x['id'] ? 'selected' : '' ?>><?= e($x['employee_code'] . ' — ' . $x['full_name']) ?></option><?php endforeach; ?></select></div>
      <div><label class="label" for="mm">Bulan</label><select class="input" id="mm" name="month"><?php for ($m = 1; $m <= 12; $m++): ?><option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>><?= bulan_id($m) ?></option><?php endfor; ?></select></div>
      <div><label class="label" for="my">Tahun</label><input class="input" id="my" type="number" name="year" min="2000" max="2100" value="<?= $year ?>"></div>
      <div class="filters-actions"><button class="btn btn-primary">Tampilkan</button></div>
    </div>
  </form>
</section>

<?php if (!$emp): ?>
  <div class="panel empty-note">Pilih karyawan dan periode untuk mengisi kehadiran.</div>
<?php else: ?>
<?php if ($ro): ?><div class="panel panel-flush" id="attForm"><?php else: ?>
<form method="post" class="panel panel-flush" id="attForm" action="<?= e(url('/admin/gaji/kelola/simpan')) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="user_id" value="<?= $uid ?>"><input type="hidden" name="year" value="<?= $year ?>"><input type="hidden" name="month" value="<?= $month ?>"><?php endif; ?>
  <div class="panel-head">
    <div class="u-grow"><h2 class="panel-title"><?= e($emp['full_name']) ?></h2><div class="panel-sub"><?= bulan_id($month) . ' ' . $year ?></div></div>
    <?php if ($payroll): ?><span class="u-small u-muted"><?= e($payroll['invoice_no']) ?><?= $locked ? ' · Final' : ' · dibuka untuk koreksi' ?></span> <?= badge($payroll['status']) ?><?php endif; ?>
  </div>
  <div class="panel-body">
  <?php if ($ro): ?><div class="notice">Anda hanya memiliki akses lihat untuk data kehadiran dan gaji.</div><?php elseif ($locked): ?><div class="notice notice-warn">Gaji periode ini sudah final. Untuk mengubah, gunakan &ldquo;Buka koreksi&rdquo; di halaman Gaji Karyawan.</div><?php endif; ?>

  <?php if (!$view): ?>
  <div class="bulk">
    <button type="button" class="btn btn-ghost btn-sm" id="bulkWeekdays">Senin–Jumat = Hadir</button>
    <span class="sep"></span>
    <button type="button" class="btn btn-ghost btn-sm" id="pickAll">Centang semua</button>
    <select id="bulkStatus" class="input input-sm input-auto" aria-label="Status massal"><?php foreach (PayrollController::STATUSES as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?><option value="">(kosongkan)</option></select>
    <button type="button" class="btn btn-ghost btn-sm" id="bulkApply">Terapkan ke yang dicentang</button>
  </div>
  <?php endif; ?>

  <div class="cal">
    <?php foreach ($names as $n): ?><div class="cal-h"><?= $n ?></div><?php endforeach; ?>
    <?php for ($i = 0; $i < $offset; $i++): ?><div class="cal-pad"></div><?php endfor; ?>
    <?php for ($d = 1; $d <= $days; $d++):
        $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $dow = ((int)date('N', strtotime($date)));
        $cur = $att[$date] ?? ($dow >= 6 ? 'libur' : '');
        if (isset($cnt[$cur])) $cnt[$cur]++; ?>
      <div class="cal-d" data-dow="<?= $dow ?>" data-status="<?= e($cur) ?>">
        <label class="top"><?php if (!$view): ?><input type="checkbox" class="pick" aria-label="Pilih tanggal <?= $d ?>"><?php endif; ?><?= $d ?><span class="cal-dow"><?= $names[$dow - 1] ?></span></label>
        <?php if ($ro): ?><div class="att-ro"><?= e(PayrollController::STATUSES[$cur] ?? '—') ?></div><?php else: ?>
        <select name="att[<?= $date ?>]" class="input att" aria-label="Status tanggal <?= $d ?>" <?= $locked ? 'disabled' : '' ?>>
          <option value="">—</option>
          <?php foreach (PayrollController::STATUSES as $k => $l): ?><option value="<?= $k ?>" <?= $cur === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
        </select><?php endif; ?>
      </div>
    <?php endfor; ?>
  </div>

  <?php if ($ro): ?>
  <div class="form-grid cols-3" style="margin-top:1.25rem">
    <div class="field"><div class="label">Tarif gaji per hari</div><div class="strong"><?= $rate ? rupiah($rate) : '-' ?></div></div>
    <div class="field"><div class="label">Potongan</div><div class="strong"><?= rupiah($payroll ? (int)$payroll['deduction'] : 0) ?></div></div>
    <div class="field"><div class="label">Keterangan potongan</div><div class="strong"><?= e(($payroll['deduction_note'] ?? '') !== '' ? $payroll['deduction_note'] : '-') ?></div></div>
  </div>
  <?php else: ?>
  <div class="form-grid cols-3" style="margin-top:1.25rem">
    <div class="field"><label class="label" for="rate">Tarif gaji per hari (Rp)</label><input class="input" id="rate" name="rate" inputmode="numeric" value="<?= $rate ? (int)$rate : '' ?>" <?= $locked ? 'disabled' : '' ?>></div>
    <div class="field"><label class="label" for="ded">Potongan (Rp)</label><input class="input" id="ded" name="deduction" inputmode="numeric" value="<?= $payroll ? (int)$payroll['deduction'] : 0 ?>" <?= $locked ? 'disabled' : '' ?>></div>
    <div class="field"><label class="label" for="dnote">Keterangan potongan</label><input class="input" id="dnote" name="deduction_note" value="<?= e($payroll['deduction_note'] ?? '') ?>" <?= $locked ? 'disabled' : '' ?>></div>
  </div>
  <?php endif; ?>

  <div class="sum-row" style="margin-top:1.25rem">
    <div><div class="k">Hadir</div><div class="v" id="cHadir"><?= $ro ? $cnt['hadir'] : 0 ?></div></div>
    <div><div class="k">Izin</div><div class="v" id="cIzin"><?= $ro ? $cnt['izin'] : 0 ?></div></div>
    <div><div class="k">Tanpa keterangan</div><div class="v" id="cAlpa"><?= $ro ? $cnt['alpa'] : 0 ?></div></div>
    <div><div class="k">Gaji kotor</div><div class="v" id="pGross"><?= $ro ? rupiah($cnt['hadir'] * (int)$rate) : 'Rp 0' ?></div></div>
    <div><div class="k">Gaji bersih</div><div class="v big" id="pNet"><?= $ro ? rupiah(max(0, $cnt['hadir'] * (int)$rate - ($payroll ? (int)$payroll['deduction'] : 0))) : 'Rp 0' ?></div></div>
  </div>
  <p class="hint">Aturan: hanya hari <b>Hadir</b> yang dibayar. Izin &amp; Tanpa Keterangan tidak dibayar. Libur/akhir pekan tidak dihitung absen.</p>

  <?php if (!$view): ?>
  <div class="form-actions">
    <button class="btn btn-ghost">Simpan kehadiran</button>
    <button class="btn btn-primary" formaction="<?= e(url('/admin/gaji/kelola/proses')) ?>" data-confirm="Simpan kehadiran dan finalisasi gaji periode ini? Setelah final, perubahan harus lewat koreksi resmi.">Simpan &amp; finalisasi gaji</button>
  </div>
  <?php endif; ?>
  </div>
<?= $ro ? '</div>' : '</form>' ?>
<?php if (!$ro): ?><script>
(function () {
  const sels = [...document.querySelectorAll('select.att')];
  const fmt = n => 'Rp ' + Math.max(0, n).toLocaleString('id-ID');
  const num = id => parseInt((document.getElementById(id).value || '0').replace(/\D/g, '')) || 0;
  function calc() {
    const c = { hadir: 0, izin: 0, alpa: 0 };
    sels.forEach(s => { if (c[s.value] !== undefined) c[s.value]++; });
    cHadir.textContent = c.hadir; cIzin.textContent = c.izin; cAlpa.textContent = c.alpa;
    const g = c.hadir * num('rate'); pGross.textContent = fmt(g); pNet.textContent = fmt(g - num('ded'));
  }
  sels.forEach(s => s.addEventListener('change', calc));
  ['rate', 'ded'].forEach(i => document.getElementById(i).addEventListener('input', calc));
  const $ = id => document.getElementById(id);
  $('bulkWeekdays') && $('bulkWeekdays').addEventListener('click', () => {
    document.querySelectorAll('[data-dow]').forEach(d => { if (+d.dataset.dow <= 5) d.querySelector('select').value = 'hadir'; }); calc();
  });
  $('pickAll') && $('pickAll').addEventListener('click', () => document.querySelectorAll('.pick').forEach(p => p.checked = true));
  $('bulkApply') && $('bulkApply').addEventListener('click', () => {
    document.querySelectorAll('.pick:checked').forEach(p => { p.closest('[data-dow]').querySelector('select').value = $('bulkStatus').value; p.checked = false; }); calc();
  });
  calc();
})();
</script><?php endif; ?>
<?php endif; ?>
