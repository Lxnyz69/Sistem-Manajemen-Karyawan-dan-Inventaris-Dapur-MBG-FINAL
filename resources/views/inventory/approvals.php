<?php $qs = array_filter(['status' => $_GET['status'] ?? '', 'type' => $_GET['type'] ?? '', 'from' => $_GET['from'] ?? '', 'to' => $_GET['to'] ?? '']); $nf = count($qs); ?>
<section class="panel panel-flush">
  <div class="panel-head"><div class="u-grow"><h2 class="panel-title">Pengajuan stok (tambah &amp; barang keluar)</h2><div class="panel-sub"><?= num($pg['total'] ?? count($pg['rows'])) ?> pengajuan</div></div></div>
  <div class="filters <?= $nf ? 'is-open' : '' ?>">
    <button type="button" class="filters-toggle"><span><i class="fa-solid fa-sliders"></i> Filter</span><span class="count"><?= $nf ? $nf . ' aktif' : '' ?></span></button>
    <form method="get" class="filters-body">
      <div class="filters-grid cols-4">
        <div><label class="label" for="fj">Jenis</label><select class="input" id="fj" name="type"><option value="">Semua jenis</option><?php foreach (['in' => 'Tambah stok', 'out' => 'Barang keluar'] as $k => $l): ?><option value="<?= $k ?>" <?= ($_GET['type'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
        <div><label class="label" for="fs">Status</label><select class="input" id="fs" name="status"><option value="">Semua status</option><?php foreach (['waiting' => 'Menunggu', 'approved' => 'Disetujui', 'declined' => 'Ditolak'] as $k => $l): ?><option value="<?= $k ?>" <?= ($_GET['status'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
        <div><label class="label" for="ff">Dari tanggal</label><input class="input" id="ff" type="date" name="from" value="<?= e($_GET['from'] ?? '') ?>"></div>
        <div><label class="label" for="ft">Sampai tanggal</label><input class="input" id="ft" type="date" name="to" value="<?= e($_GET['to'] ?? '') ?>"></div>
        <div class="filters-actions"><button class="btn btn-primary">Terapkan</button><a class="btn btn-ghost" href="<?= e(url('/admin/stok/persetujuan')) ?>">Reset</a></div>
      </div>
    </form>
  </div>
  <div class="table-wrap"><table class="tbl tbl-stack"><thead><tr><th>No.</th><th>Tanggal</th><th>Jenis</th><th>Barang</th><th class="num">Stok kini</th><th class="num">Diajukan</th><th class="num">Stok setelah</th><th>Pengaju</th><th>Alasan</th><th>Status</th><th>Keputusan</th></tr></thead><tbody>
  <?php foreach ($pg['rows'] as $r): ?>
    <tr><td data-label="No."><?= e($r['request_no']) ?></td><td data-label="Tanggal"><?= dmy($r['created_at']) ?></td><td data-label="Jenis"><?= stock_type_badge($r['type']) ?></td><td data-label="Barang" class="strong"><?= e($r['item_name']) ?><span class="sub"><?= e($r['item_code']) ?></span></td>
    <td data-label="Stok kini" class="num"><?= num($r['stock']) ?></td><td data-label="Diajukan" class="num"><?= $r['type'] === 'out' ? '-' : '+' ?><?= num($r['quantity']) ?> <?= e($r['unit']) ?></td><td data-label="Stok setelah" class="num"><?php $out = $r['type'] === 'out'; $after = $r['stock'] + ($out ? -$r['quantity'] : $r['quantity']); ?><?= $r['status'] === 'waiting' ? ($after < 0 ? '<span class="u-danger" style="color:#b3261e">Tidak cukup</span>' : num($after)) : '-' ?></td>
    <td data-label="Pengaju"><?= e($r['requester']) ?></td><td data-label="Alasan" style="max-width:14rem"><?= e($r['reason']) ?></td><td data-label="Status"><?= badge($r['status']) ?></td>
    <td class="cell-wide"><?php if ($r['status'] === 'waiting' && can('persetujuan_stok.approve')): ?>
      <div class="decision-cell">
        <form method="post" action="<?= e(url('/admin/stok/persetujuan/' . $r['id'] . '/setujui')) ?>" class="inline-decision" data-confirm="<?= $r['type'] === 'out' ? 'Setujui dan kurangi stok?' : 'Setujui dan tambahkan stok?' ?>"><?= csrf_field() ?><input class="input input-sm" name="note" placeholder="Catatan (opsional)" aria-label="Catatan"><button class="btn btn-ok btn-sm">Setujui</button></form>
        <form method="post" action="<?= e(url('/admin/stok/persetujuan/' . $r['id'] . '/tolak')) ?>" class="inline-decision" data-confirm="Tolak pengajuan ini?"><?= csrf_field() ?><input class="input input-sm" name="note" placeholder="Alasan penolakan" required aria-label="Alasan penolakan"><button class="btn btn-danger btn-sm">Tolak</button></form>
      </div><?php else: ?><div class="u-small u-muted"><?= e($r['decider'] ?? '-') ?><br><?= dmyhi($r['decided_at']) ?><br><?= e($r['decision_note']) ?></div><?php endif; ?></td></tr>
  <?php endforeach; if (!$pg['rows']): ?><tr><td colspan="11" class="empty">Tidak ada pengajuan.</td></tr><?php endif; ?>
  </tbody></table></div>
  <?= pager($pg, $qs) ?>
</section>
