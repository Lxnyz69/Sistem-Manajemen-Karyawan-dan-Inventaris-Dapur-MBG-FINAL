<?php
$siteName = setting('site_name') ?: setting('company_name', 'Manajemen Perusahaan');
$used = $budget['total_in'] > 0 ? (int)min(100, round($budget['total_out'] / $budget['total_in'] * 100)) : 0;
$dashHref = auth_user() ? url(home_path()) : url('/login');
$dashText = auth_user() ? 'Buka Dashboard' : 'Masuk';
$showLogin = auth_user() || login_button_visible();   // diatur di Pengaturan → Akses publik
?>
<link rel="stylesheet" href="<?= e(asset('css/landing.css') . '?v=' . filemtime(ROOT . '/public/assets/css/landing.css')) ?>">
<div class="lp">
<header class="lp-nav">
  <div class="lp-wrap lp-nav-in">
    <a class="lp-brand" href="#beranda">
      <?php if (setting('logo')): ?><img src="<?= e(url(setting('logo'))) ?>" alt=""><?php else: ?><span class="mark"><i class="fa-solid fa-utensils"></i></span><?php endif; ?>
      <span><?= e($siteName) ?></span>
    </a>
    <nav class="lp-links" id="lpLinks" aria-label="Navigasi utama">
      <a href="#statistik">Statistik</a><a href="#anggaran">Anggaran</a><a href="#distribusi">Distribusi</a><a href="#sekolah">Sekolah</a><a href="#dapur">Dapur</a>
    </nav>
    <div class="lp-nav-cta">
      <span class="live-pill" id="livePill" data-state="on"><i></i>Live</span>
      <?php if ($showLogin): ?><a class="lp-btn lp-btn-pri lp-login" href="<?= e($dashHref) ?>"><i class="fa-solid fa-right-to-bracket"></i> <span><?= e($dashText) ?></span></a><?php endif; ?>
      <button type="button" class="lp-burger" id="lpBurger" aria-label="Buka menu" aria-expanded="false" aria-controls="lpLinks"><i class="fa-solid fa-bars"></i></button>
    </div>
  </div>
</header>

<main id="liveRoot" data-live="1" data-live-refresh="15000">
  <!-- ========== HERO ========== -->
  <section class="lp-hero" id="beranda">
    <div class="lp-wrap lp-hero-grid">
      <div>
        <span class="lp-eyebrow"><i class="fa-solid fa-bowl-food"></i> Program Makan Bergizi</span>
        <h1><?= e(setting('site_title') ?: $siteName) ?></h1>
        <p>Sistem manajemen dapur yang mencatat produksi, pengantaran, stok, dan anggaran secara terbuka, sehingga distribusi makanan ke sekolah dapat dipantau langsung. Hanya angka agregat yang ditampilkan, tanpa data pribadi.</p>
        <div class="lp-hero-cta">
          <a class="lp-btn lp-btn-pri" href="#distribusi"><i class="fa-solid fa-truck-fast"></i> Lihat pengantaran</a>
          <a class="lp-btn lp-btn-out" href="#anggaran"><i class="fa-solid fa-wallet"></i> Transparansi anggaran</a>
        </div>
      </div>
      <aside class="lp-hero-card" aria-label="Progres pengantaran hari ini">
        <h2>Pengantaran selesai hari ini</h2>
        <div class="big" data-count="<?= (int)$today['percent'] ?>" data-suffix="%"><?= (int)$today['percent'] ?>%</div>
        <div class="lp-prog"><i style="width:<?= (int)$today['percent'] ?>%"></i></div>
        <small><?= num($today['completed']) ?> dari <?= num($today['total']) ?> pengiriman · <?= e(tgl_panjang(date('Y-m-d'))) ?></small>
      </aside>
    </div>
  </section>

  <!-- ========== STATISTIK ========== -->
  <section class="lp-stats" id="statistik">
    <div class="lp-wrap"><div class="lp-stat-grid" data-reveal-group>
      <div class="lp-stat"><b data-count="<?= (int)$dist['schools_active'] ?>"><?= num($dist['schools_active']) ?></b><span>Sekolah aktif</span></div>
      <div class="lp-stat"><b data-count="<?= (int)$dist['daily_portions'] ?>"><?= num($dist['daily_portions']) ?></b><span>Penerima manfaat (siswa)</span></div>
      <div class="lp-stat"><b data-count="<?= (int)$s['emp_active'] ?>"><?= num($s['emp_active']) ?></b><span>Karyawan aktif</span></div>
      <div class="lp-stat"><b data-count="<?= (int)$dist['all_deliveries'] ?>"><?= num($dist['all_deliveries']) ?></b><span>Total pengiriman</span></div>
      <div class="lp-stat"><b data-count="<?= (int)$dist['couriers'] ?>"><?= num($dist['couriers']) ?></b><span>Petugas pengantaran</span></div>
    </div></div>
  </section>

  <!-- ========== ANGGARAN ========== -->
  <section class="lp-sec" id="anggaran"><div class="lp-wrap">
    <div class="lp-sec-head" data-reveal><div><h2>Transparansi Anggaran</h2><p><?= e($budget['week_label']) ?> · <?= dmy($budget['week_start']) ?> s/d <?= dmy($budget['week_end']) ?></p></div></div>
    <div class="lp-cards" data-reveal-group>
      <div class="lp-card"><h3>Anggaran masuk</h3><div class="val"><?= e(rupiah($budget['total_in'])) ?></div></div>
      <div class="lp-card"><h3>Anggaran keluar</h3><div class="val"><?= e(rupiah($budget['total_out'])) ?></div></div>
      <div class="lp-card"><h3>Saldo tersisa</h3><div class="val"><?= e(rupiah($budget['balance'])) ?></div></div>
    </div>
    <div class="lp-card lp-use">
      <div class="row"><span>Penggunaan anggaran</span><b><?= $used ?>%</b></div>
      <div class="lp-prog" role="progressbar" aria-valuenow="<?= $used ?>" aria-valuemin="0" aria-valuemax="100"><i style="width:<?= $used ?>%"></i></div>
      <div class="lp-cards" style="margin-top:1rem">
        <div><h3>Keluar untuk gaji</h3><div class="val" style="font-size:1.1rem"><?= e(rupiah($budget['out_payroll'])) ?></div></div>
        <div><h3>Keluar untuk bahan/barang</h3><div class="val" style="font-size:1.1rem"><?= e(rupiah($budget['out_stock'])) ?></div></div>
        <div><h3>Masuk / keluar minggu ini</h3><div class="val" style="font-size:1.1rem"><?= e(rupiah($budget['week_in'])) ?> / <?= e(rupiah($budget['week_out'])) ?></div></div>
      </div>
    </div>
    <div class="lp-two">
      <section class="panel"><h2 class="panel-title u-mb">Dana masuk per minggu</h2>
        <?php $d = []; foreach ($budgetWeeks as $w) $d[dmy($w['start'])] = $w['in']; echo bar_chart($d, 'var(--c-primary)', true); ?>
      </section>
      <section class="panel"><h2 class="panel-title u-mb">Pengeluaran per minggu</h2>
        <?php $d = []; foreach ($budgetWeeks as $w) $d[dmy($w['start'])] = $w['out']; echo bar_chart($d, 'var(--c-primary)', true); ?>
      </section>
    </div>
  </div></section>

  <!-- ========== PENGANTARAN / DISTRIBUSI ========== -->
  <section class="lp-sec" id="distribusi"><div class="lp-wrap">
    <div class="lp-sec-head"><div><h2>Pengantaran &amp; Distribusi</h2><p>Status pengiriman hari ini · <?= e(tgl_panjang(date('Y-m-d'))) ?></p></div></div>
    <div class="lp-steps" data-reveal-group>
      <div class="lp-step"><b><?= num($today['preparing']) ?></b><span>Dalam pembuatan</span></div>
      <div class="lp-step"><b><?= num($today['transit']) ?></b><span>Dalam pengantaran</span></div>
      <div class="lp-step"><b><?= num($today['arrived']) ?></b><span>Sampai di sekolah</span></div>
      <div class="lp-step"><b><?= num($today['received']) ?></b><span>Makanan diterima</span></div>
    </div>
    <div class="lp-cards" style="margin-top:1rem">
      <div class="lp-card"><h3>Pengiriman hari ini</h3><div class="val"><?= num($today['total']) ?></div></div>
      <div class="lp-card"><h3>Porsi terkirim hari ini</h3><div class="val"><?= num($today['sent']) ?> <small class="u-muted">/ <?= num($dist['planned_today']) ?></small></div></div>
      <div class="lp-card"><h3>Terlambat</h3><div class="val"<?= $today['late'] > 0 ? ' style="color:#b3261e"' : '' ?>><?= num($today['late']) ?></div></div>
      <div class="lp-card"><h3>Seluruh pengiriman diterima</h3><div class="val"><?= num($dist['all_received']) ?> <small class="u-muted">/ <?= num($dist['all_deliveries']) ?></small></div></div>
    </div>
    <?php $trackSchool = 0; $__tm = ROOT . '/resources/views/partials/track_map.php'; ?>
    <div class="u-mt"><?php require $__tm; ?></div>
  </div></section>

  <!-- ========== SEKOLAH ========== -->
  <section class="lp-sec" id="sekolah"><div class="lp-wrap">
    <section class="panel panel-flush">
      <div class="panel-head">
        <h2 class="panel-title u-grow">Daftar Sekolah</h2>
        <input class="input input-sm input-auto" type="search" id="schoolSearch" placeholder="Cari nama sekolah / alamat" aria-label="Cari sekolah" style="min-width:min(14rem,100%)">
      </div>
      <div class="table-wrap"><table class="tbl pub-school-tbl lp-rtbl"><thead><tr><th>Sekolah</th><th class="num">Jumlah siswa</th><th>Menu hari ini</th><th>Status pengantaran hari ini</th></tr></thead><tbody id="schoolRows">
        <?php foreach ($schools as $sc): ?>
        <tr data-search="<?= e(mb_strtolower($sc['name'] . ' ' . $sc['address'])) ?>">
          <td data-label="Sekolah"><a class="school-name" href="<?= e(url('/sekolah/' . $sc['id'])) ?>"><?= e($sc['name']) ?></a><span class="sub"><?= e($sc['address']) ?></span></td>
          <td class="num" data-label="Jumlah siswa"><?= num($sc['daily_portions']) ?></td>
          <td data-label="Menu hari ini"><?= isset($menusToday[$sc['id']]) ? e(implode(', ', $menusToday[$sc['id']])) : '<span class="u-muted">Belum ada menu</span>' ?></td>
          <td data-label="Status hari ini"><?= delivery_day_summary($statToday[$sc['id']] ?? []) ?></td>
        </tr>
        <?php endforeach; if (!$schools): ?><tr><td class="empty" colspan="4">Belum ada sekolah terdaftar.</td></tr><?php endif; ?>
        <tr id="schoolNone" hidden><td class="empty" colspan="4">Tidak ada sekolah yang cocok dengan pencarian.</td></tr>
      </tbody></table></div>
      <div class="panel-foot"><span>Klik nama sekolah untuk melihat menu dan status pengantaran per hari.</span></div>
    </section>
  </div></section>

  <!-- ========== DAPUR ========== -->
  <section class="lp-sec" id="dapur"><div class="lp-wrap">
    <div class="lp-sec-head"><div><h2>Informasi Dapur</h2><p>Profil dapur dan ringkasan operasional.</p></div></div>
    <div class="lp-kitchen" data-reveal-group>
      <div class="lp-card"><span class="ic"><i class="fa-solid fa-kitchen-set"></i></span><h3>Nama dapur</h3><p><?= e(setting('company_name', $siteName)) ?></p></div>
      <?php if (setting('company_address')): ?><div class="lp-card"><span class="ic"><i class="fa-solid fa-location-dot"></i></span><h3>Alamat</h3><p><?= e(setting('company_address')) ?></p></div><?php endif; ?>
      <div class="lp-card"><span class="ic"><i class="fa-solid fa-boxes-stacked"></i></span><h3>Inventaris</h3><p><?= num($s['items']) ?> jenis barang · total stok <?= num($s['stock']) ?></p></div>
      <div class="lp-card"><span class="ic"><i class="fa-solid fa-utensils"></i></span><h3>Menu</h3><p><?= num($dist['menus_today']) ?> menu hari ini · <?= num($dist['planned_today']) ?> porsi direncanakan</p></div>
      <div class="lp-card"><span class="ic"><i class="fa-solid fa-scale-balanced"></i></span><h3>Nilai persediaan</h3><p><?= e(rupiah($s['value'])) ?> · <?= num($s['low_stock']) ?> barang mencapai batas stok minimum</p></div>
      <div class="lp-card"><span class="ic"><i class="fa-solid fa-clipboard-list"></i></span><h3>Permintaan stok</h3><p><?= num($s['requests']) ?> permintaan · <?= num($s['requests_waiting']) ?> menunggu · <?= num($s['requests_approved']) ?> disetujui · <?= num($s['requests_declined']) ?> ditolak</p></div>
      <div class="lp-card"><span class="ic"><i class="fa-solid fa-receipt"></i></span><h3>Ringkasan payroll</h3><p><?= e(rupiah($s['payroll_paid'])) ?> dibayarkan · <?= e(rupiah($s['payroll_unpaid'])) ?> belum dibayarkan</p></div>
    </div>
  </div></section>
</main>

<!-- ========== FOOTER ========== -->
<footer class="lp-foot">
  <div class="lp-wrap">
    <div class="lp-foot-grid">
      <div><h4><?= e($siteName) ?></h4><p>Transparansi distribusi makanan dan pengelolaan dapur.</p></div>
      <div><h4>Kontak</h4>
        <?php if (setting('company_address')): ?><p><?= e(setting('company_address')) ?></p><?php endif; ?>
        <?php if (setting('company_phone')): ?><p><?= e(setting('company_phone')) ?></p><?php endif; ?>
        <?php if (setting('company_email')): ?><p><?= e(setting('company_email')) ?></p><?php endif; ?>
      </div>
      <div><h4>Tautan</h4><a href="#statistik">Statistik</a><a href="#anggaran">Anggaran</a><a href="#distribusi">Distribusi</a><?php if ($showLogin): ?><a href="<?= e($dashHref) ?>"><?= e($dashText) ?></a><?php endif; ?></div>
    </div>
    <div class="lp-copy"><?= e(setting('footer_text')) ?></div>
  </div>
</footer>
</div>

<script>
(function(){
  /* Pencarian sekolah */
  var q=document.getElementById('schoolSearch'),none=document.getElementById('schoolNone');
  if(q)q.addEventListener('input',function(){var rows=document.querySelectorAll('#schoolRows tr[data-search]'),v=q.value.trim().toLowerCase(),n=0;rows.forEach(function(r){var ok=!v||r.dataset.search.indexOf(v)>-1;r.hidden=!ok;if(ok)n++;});none.hidden=n>0||!rows.length;});

  /* Menu HP + bayangan navigasi saat digulir */
  var nav=document.querySelector('.lp-nav'),b=document.getElementById('lpBurger'),l=document.getElementById('lpLinks');
  if(b&&l){
    b.addEventListener('click',function(){var o=nav.classList.toggle('open');b.setAttribute('aria-expanded',o);b.firstElementChild.className=o?'fa-solid fa-xmark':'fa-solid fa-bars';});
    l.addEventListener('click',function(e){if(e.target.closest('a')){nav.classList.remove('open');b.setAttribute('aria-expanded','false');b.firstElementChild.className='fa-solid fa-bars';}});
  }
  function onScroll(){nav&&nav.classList.toggle('scrolled',window.scrollY>8);}
  window.addEventListener('scroll',onScroll,{passive:true});onScroll();

  var reduce=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if(reduce)return;

  /* Hitung naik pada angka statistik (nilai akhir selalu sama dengan data database) */
  document.querySelectorAll('[data-count]').forEach(function(el){
    var to=parseInt(el.dataset.count,10)||0,suf=el.dataset.suffix||'',t0=null;
    if(to<=0)return;
    function step(t){if(!t0)t0=t;var p=Math.min(1,(t-t0)/1100),e=1-Math.pow(1-p,3);el.textContent=Math.round(to*e).toLocaleString('id-ID')+suf;if(p<1)requestAnimationFrame(step);}
    el.textContent='0'+suf;requestAnimationFrame(step);
  });

  /* Muncul saat digulir. Elemen tetap terlihat jika JS/Observer tidak tersedia. */
  if(!('IntersectionObserver' in window))return;
  var vh=window.innerHeight,targets=[];
  document.querySelectorAll('[data-reveal]').forEach(function(el){targets.push(el);});
  document.querySelectorAll('[data-reveal-group]').forEach(function(g){Array.prototype.forEach.call(g.children,function(c,i){c.style.setProperty('--d',(i*70)+'ms');targets.push(c);});});
  document.querySelectorAll('.lp-sec .panel').forEach(function(el){targets.push(el);});
  var io=new IntersectionObserver(function(es){es.forEach(function(en){if(en.isIntersecting){en.target.classList.add('r-in');io.unobserve(en.target);}});},{threshold:.12,rootMargin:'0px 0px -6% 0px'});
  targets.forEach(function(el){if(el.getBoundingClientRect().top<vh*.9)return;el.classList.add('r-pre');io.observe(el);});
})();
</script>
