<?php
$keys = ['q', 'from', 'to', 'week', 'month', 'year', 'type', 'category'];
$qs = array_filter(array_intersect_key($_GET, array_flip($keys)), fn($v) => $v !== '' && $v !== null); $nf = count($qs);
?>
<div class="stack">
  <section class="panel panel-flush">
    <div class="panel-head"><div class="u-grow"><h2 class="panel-title">Ringkasan anggaran</h2><div class="panel-sub">Saldo = total dana masuk − total pengeluaran · <?= e($b['week_label']) ?> (<?= dmy($b['week_start']) ?> s/d <?= dmy($b['week_end']) ?>)</div></div></div>
    <div class="metrics cols-3">
      <?= stat_card('', 'Saldo anggaran saat ini', rupiah($b['balance']), '#2563eb', true) ?>
      <?= stat_card('', 'Total dana masuk', rupiah($b['total_in'])) ?>
      <?= stat_card('', 'Total pengeluaran', rupiah($b['total_out'])) ?>
      <?= stat_card('', 'Dana masuk minggu ini', rupiah($b['week_in'])) ?>
      <?= stat_card('', 'Pengeluaran minggu ini', rupiah($b['week_out'])) ?>
      <?= stat_card('', 'Pengeluaran bulan ini', rupiah($b['month_out'])) ?>
    </div>
  </section>

  <?php if ($canManage): ?>
  <section class="panel"><h2 class="panel-title u-mb">Tambah anggaran</h2>
    <form data-live-ignore method="post" action="<?= e(url('/admin/anggaran/tambah')) ?>" class="stack" style="gap:.9rem"><?= csrf_field() ?>
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <div class="form-grid cols-3">
        <div class="field"><label class="label" for="ba">Nominal (Rp)</label><input class="input" id="ba" name="amount" inputmode="numeric" autocomplete="off" placeholder="1.000.000.000" value="<?= e(old('amount')) ?>" required></div>
        <div class="field"><label class="label" for="bd">Tanggal</label><input class="input" id="bd" type="date" name="date" value="<?= e(old('date', date('Y-m-d'))) ?>" required></div>
        <div class="field"><label class="label" for="bp">Periode / minggu</label><input class="input" id="bp" name="period" maxlength="60" placeholder="<?= e(Budget::weekLabel()) ?>" value="<?= e(old('period')) ?>"><div class="hint">Kosongkan untuk otomatis mengikuti tanggal (Senin–Minggu).</div></div>
        <div class="field span-2"><label class="label" for="bk">Keterangan</label><input class="input" id="bk" name="description" maxlength="150" placeholder="Anggaran operasional minggu ini" value="<?= e(old('description')) ?>"></div>
      </div>
      <p class="hint" style="margin:0">Saldo tidak di-reset tiap minggu: dana baru ditambahkan ke saldo berjalan.</p>
      <div><button class="btn btn-primary">Tambah anggaran</button></div>
    </form>
  </section>
  <?php endif; ?>

  <section class="panel panel-flush">
    <div class="panel-head"><div class="u-grow"><h2 class="panel-title">Riwayat transaksi</h2><div class="panel-sub"><?= num($pg['total']) ?> transaksi</div></div>
      <a class="btn btn-ghost" href="<?= e(url('/admin/anggaran/export') . ($qs ? '?' . http_build_query($qs) : '')) ?>"><i class="fa-solid fa-download"></i> Unduh CSV</a></div>
    <div class="filters <?= $nf ? 'is-open' : '' ?>">
      <button type="button" class="filters-toggle"><span><i class="fa-solid fa-sliders"></i> Filter</span><span class="count"><?= $nf ? $nf . ' aktif' : '' ?></span></button>
      <form method="get" class="filters-body">
        <div class="filters-grid cols-4">
          <div class="search"><label class="label" for="fq">Pencarian</label><i class="fa-solid fa-magnifying-glass"></i><input class="input" id="fq" name="q" placeholder="Keterangan, invoice, no. pengajuan" value="<?= e($_GET['q'] ?? '') ?>"></div>
          <div><label class="label" for="ff">Dari tanggal</label><input class="input" id="ff" type="date" name="from" value="<?= e($_GET['from'] ?? '') ?>"></div>
          <div><label class="label" for="ft">Sampai tanggal</label><input class="input" id="ft" type="date" name="to" value="<?= e($_GET['to'] ?? '') ?>"></div>
          <div><label class="label" for="fw">Minggu</label><input class="input" id="fw" type="week" name="week" value="<?= e($_GET['week'] ?? '') ?>"></div>
          <div><label class="label" for="fm">Bulan</label><input class="input" id="fm" type="month" name="month" value="<?= e($_GET['month'] ?? '') ?>"></div>
          <div><label class="label" for="fy">Tahun</label><select class="input" id="fy" name="year"><option value="">Semua tahun</option><?php foreach ($years as $y): ?><option value="<?= (int)$y ?>" <?= (string)($_GET['year'] ?? '') === (string)$y ? 'selected' : '' ?>><?= (int)$y ?></option><?php endforeach; ?></select></div>
          <div><label class="label" for="fj">Jenis</label><select class="input" id="fj" name="type"><option value="">Semua jenis</option><?php foreach (['in' => 'Masuk', 'out' => 'Keluar'] as $k => $l): ?><option value="<?= $k ?>" <?= ($_GET['type'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
          <div><label class="label" for="fc">Kategori</label><select class="input" id="fc" name="category"><option value="">Semua kategori</option><?php foreach ($cats as $c): ?><option value="<?= e($c) ?>" <?= ($_GET['category'] ?? '') === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
          <div class="filters-actions"><button class="btn btn-primary">Terapkan</button><a class="btn btn-ghost" href="<?= e(url('/admin/anggaran')) ?>">Reset</a></div>
        </div>
      </form>
    </div>
    <div class="table-wrap"><table class="tbl tbl-stack"><thead><tr><th>Tanggal</th><th>Jenis</th><th>Kategori</th><th>Keterangan</th><th class="num">Nominal</th><th>Sumber transaksi</th><th>Dibuat oleh</th><th>Waktu transaksi</th><?php if ($canManage): ?><th>Koreksi</th><?php endif; ?></tr></thead><tbody>
    <?php foreach ($pg['rows'] as $r): $in = $r['type'] === 'in'; $isRev = ($r['source_type'] ?? '') === 'reversal'; ?>
      <tr><td data-label="Tanggal"><?= dmy($r['transaction_date']) ?></td>
      <td data-label="Jenis"><?= $in ? '<span class="badge badge-green">MASUK</span>' : '<span class="badge badge-red">KELUAR</span>' ?><?= $isRev ? ' <span class="badge badge-gray">koreksi</span>' : '' ?><?= $r['reversed_by'] ? ' <span class="badge badge-yellow">dikoreksi</span>' : '' ?></td>
      <td data-label="Kategori"><?= e($r['category']) ?></td>
      <td data-label="Keterangan" style="max-width:20rem"><?= e($r['description']) ?></td>
      <td data-label="Nominal" class="num strong <?= $in ? 'pos' : 'neg' ?>"><?= $in ? '+' : '−' ?><?= rupiah($r['amount']) ?></td>
      <td data-label="Sumber transaksi"><?= e(Budget::sourceLabel($r)) ?></td>
      <td data-label="Dibuat oleh"><?= e($r['creator'] ?? 'Sistem') ?></td>
      <td data-label="Waktu transaksi"><?= dmyhi($r['created_at']) ?></td>
      <?php if ($canManage): ?><td class="cell-wide"><?php if (!$isRev && !$r['reversed_by']): ?>
        <form method="post" action="<?= e(url('/admin/anggaran/' . $r['id'] . '/koreksi')) ?>" class="inline-decision" data-confirm="Buat transaksi koreksi (reversal) untuk transaksi ini? Transaksi asal tetap tersimpan."><?= csrf_field() ?><input class="input input-sm" name="note" placeholder="Alasan koreksi" required minlength="5" aria-label="Alasan koreksi"><button class="btn btn-danger btn-sm">Koreksi</button></form>
      <?php else: ?><span class="u-muted">–</span><?php endif; ?></td><?php endif; ?></tr>
    <?php endforeach; if (!$pg['rows']): ?><tr><td colspan="<?= $canManage ? 9 : 8 ?>" class="empty">Belum ada transaksi anggaran.</td></tr><?php endif; ?>
    </tbody></table></div>
    <?= pager($pg, $qs) ?>
  </section>

  <div class="grid-2">
    <section class="panel panel-flush">
      <div class="panel-head"><h2 class="panel-title">Ringkasan per minggu <span class="panel-sub">· Senin–Minggu</span></h2></div>
      <div class="table-wrap"><table class="tbl"><thead><tr><th>Minggu</th><th class="num">Dana masuk</th><th class="num">Pengeluaran</th></tr></thead><tbody>
      <?php foreach ($weeks as $w): ?><tr><td><?= e($w['label']) ?><span class="sub"><?= dmy($w['start']) ?> – <?= dmy($w['end']) ?></span></td><td class="num pos"><?= rupiah($w['in']) ?></td><td class="num neg"><?= rupiah($w['out']) ?></td></tr><?php endforeach; ?>
      </tbody></table></div>
    </section>
    <section class="panel panel-flush">
      <div class="panel-head"><h2 class="panel-title">Ringkasan per bulan</h2></div>
      <div class="table-wrap"><table class="tbl"><thead><tr><th>Bulan</th><th class="num">Dana masuk</th><th class="num">Pengeluaran</th></tr></thead><tbody>
      <?php foreach ($months as $m): ?><tr><td><?= e($m['label']) ?></td><td class="num pos"><?= rupiah($m['in']) ?></td><td class="num neg"><?= rupiah($m['out']) ?></td></tr><?php endforeach; ?>
      </tbody></table></div>
    </section>
  </div>
</div>
