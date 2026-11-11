<?php $qs = array_filter(['q' => $_GET['q'] ?? '', 'period' => $_GET['period'] ?? '', 'status' => $_GET['status'] ?? '', 'user_id' => $_GET['user_id'] ?? '']); $nf = count($qs); ?>
<section class="panel panel-flush">
  <div class="panel-head">
    <div class="u-grow"><h2 class="panel-title">Daftar gaji</h2><div class="panel-sub"><?= num($pg['total'] ?? count($pg['rows'])) ?> data</div></div>
    <?php if (can('kelola_gaji.view')): ?><a class="btn btn-primary" href="<?= e(url('/admin/gaji/kelola')) ?>"><i class="fa-solid fa-calendar-check"></i> Kelola gaji</a><?php endif; ?>
  </div>
  <div class="filters <?= $nf ? 'is-open' : '' ?>">
    <button type="button" class="filters-toggle"><span><i class="fa-solid fa-sliders"></i> Filter</span><span class="count"><?= $nf ? $nf . ' aktif' : '' ?></span></button>
    <form method="get" class="filters-body">
      <div class="filters-grid cols-4">
        <div class="search"><label class="label" for="fq">Pencarian</label><i class="fa-solid fa-magnifying-glass"></i><input class="input" id="fq" name="q" placeholder="ID, nama, atau no. invoice" value="<?= e($_GET['q'] ?? '') ?>"></div>
        <div><label class="label" for="fp">Periode</label><input class="input" id="fp" type="month" name="period" value="<?= e($_GET['period'] ?? '') ?>"></div>
        <div><label class="label" for="fu">Karyawan</label><select class="input" id="fu" name="user_id"><option value="">Semua karyawan</option><?php foreach ($emps as $e_): ?><option value="<?= $e_['id'] ?>" <?= (int)($_GET['user_id'] ?? 0) === (int)$e_['id'] ? 'selected' : '' ?>><?= e($e_['employee_code'] . ' — ' . $e_['full_name']) ?></option><?php endforeach; ?></select></div>
        <div><label class="label" for="fs">Status</label><select class="input" id="fs" name="status"><option value="">Semua status</option><option value="unpaid" <?= ($_GET['status'] ?? '') === 'unpaid' ? 'selected' : '' ?>>Belum Dibayar</option><option value="paid" <?= ($_GET['status'] ?? '') === 'paid' ? 'selected' : '' ?>>Sudah Dibayar</option></select></div>
        <div class="filters-actions"><button class="btn btn-primary">Terapkan</button><a class="btn btn-ghost" href="<?= e(url('/admin/gaji')) ?>">Reset</a></div>
      </div>
    </form>
  </div>
  <div class="table-wrap"><table class="tbl tbl-stack"><thead><tr><th>No. invoice</th><th>Karyawan</th><th>Periode</th><th class="num" title="Hadir / Izin / Tanpa keterangan">H / I / A</th><th class="num">Tarif/hari</th><th class="num">Kotor</th><th class="num">Potongan</th><th class="num">Bersih</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
  <?php foreach ($pg['rows'] as $p): ?>
    <tr><td data-label="No. invoice"><span class="strong"><?= e($p['invoice_no']) ?></span><?= $p['is_final'] ? '' : ' <span class="badge badge-gray">dikoreksi</span>' ?><span class="sub"><?= dmy($p['created_at']) ?></span></td>
    <td data-label="Karyawan"><span class="strong"><?= e($p['full_name']) ?></span><span class="sub"><?= e($p['employee_code']) ?></span></td>
    <td data-label="Periode"><?= e(bulan_id((int)$p['period_month']) . ' ' . $p['period_year']) ?></td>
    <td data-label="H / I / A" class="num"><?= $p['days_present'] ?> / <?= $p['days_leave'] ?> / <?= $p['days_absent'] ?></td>
    <td data-label="Tarif/hari" class="num"><?= rupiah($p['daily_rate']) ?></td><td data-label="Kotor" class="num"><?= rupiah($p['gross']) ?></td><td data-label="Potongan" class="num"><?= rupiah($p['deduction']) ?></td><td data-label="Bersih" class="num strong"><?= rupiah($p['net']) ?></td><td data-label="Status"><?= badge($p['status']) ?></td>
    <td class="cell-actions"><div class="row-actions">
      <?php if ($p['is_final']): ?><a class="btn btn-ghost btn-sm btn-icon" title="Lihat slip" aria-label="Lihat slip" href="<?= e(url('/admin/gaji/invoice/' . $p['invoice_no'])) ?>"><i class="fa-solid fa-file-invoice"></i></a><?php endif; ?>
      <?php if (can('gaji.edit') && $p['is_final'] && $p['status'] === 'unpaid'): ?>
        <form method="post" action="<?= e(url('/admin/gaji/' . $p['id'] . '/bayar')) ?>" data-confirm="Tandai gaji ini sudah dibayar?"><?= csrf_field() ?><button class="btn btn-ok btn-sm btn-icon" title="Tandai dibayar" aria-label="Tandai dibayar"><i class="fa-solid fa-check"></i></button></form>
      <?php endif; ?>
      <?php if (can('gaji.approve') && $p['status'] === 'unpaid' && $p['is_final']): ?>
        <form method="post" action="<?= e(url('/admin/gaji/' . $p['id'] . '/koreksi')) ?>" data-confirm="Buka periode ini untuk koreksi? Tercatat di audit log."><?= csrf_field() ?>
          <input type="hidden" name="note" value="Koreksi data kehadiran/gaji"><button class="btn btn-ghost btn-sm btn-icon" title="Buka koreksi" aria-label="Buka koreksi"><i class="fa-solid fa-pen-ruler"></i></button></form>
      <?php endif; ?>
    </div></td></tr>
  <?php endforeach; if (!$pg['rows']): ?><tr><td colspan="10" class="empty">Belum ada data gaji. Buat lewat menu Kelola Gaji.</td></tr><?php endif; ?>
  </tbody></table></div>
  <?= pager($pg, $qs) ?>
</section>
