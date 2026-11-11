<?php
/**
 * Layanan anggaran terpusat. Ledger tunggal = tabel budget_transactions.
 *   Saldo = SUM(masuk) - SUM(keluar)   (dihitung dari ledger, tidak pernah disimpan di tempat lain)
 * Semua halaman (landing, dashboard, halaman anggaran) membaca lewat kelas ini sehingga angkanya selalu sama.
 *
 * Aturan:
 *  - Transaksi tidak pernah di-UPDATE/DELETE. Kesalahan dikoreksi dengan reversal (source_type='reversal').
 *  - Pengeluaran otomatis idempoten: UNIQUE(source_type, source_id) + pemeriksaan di dalam kunci.
 *  - Semua penulisan memegang kunci budget_lock (SELECT ... FOR UPDATE) agar saldo tidak bisa negatif
 *    meski dua transaksi berjalan bersamaan.
 */
class Budget {
    const CAT_IN = 'Dana masuk';
    const CAT_PAYROLL = 'Gaji karyawan';
    const CAT_STOCK = 'Pembelian bahan/barang';
    const MAX_AMOUNT_CENTS = 99999999999999; // DECIMAL(14,2)

    /** Dana masuk "efektif": reversal atas pengeluaran (IN) tidak dihitung sebagai dana masuk baru. */
    const SQL_IN  = "CASE WHEN type='in' AND COALESCE(source_type,'')<>'reversal' THEN amount WHEN type='out' AND source_type='reversal' THEN -amount ELSE 0 END";
    /** Dana keluar "efektif": reversal atas dana masuk (OUT) tidak dihitung sebagai pengeluaran. */
    const SQL_OUT = "CASE WHEN type='out' AND COALESCE(source_type,'')<>'reversal' THEN amount WHEN type='in' AND source_type='reversal' THEN -amount ELSE 0 END";
    const SQL_NET = "CASE WHEN type='in' THEN amount ELSE -amount END";

    // ------------------------------------------------------------ waktu (Senin–Minggu, Asia/Jakarta)
    static function today(): string { return date('Y-m-d'); }
    /** @return array{0:string,1:string} [Senin, Minggu] dari minggu yang memuat $date */
    static function weekRange(?string $date = null): array {
        $t = strtotime(($date ?: self::today()) . ' 12:00:00');
        $mon = strtotime('-' . ((int)date('N', $t) - 1) . ' day', $t);
        return [date('Y-m-d', $mon), date('Y-m-d', strtotime('+6 day', $mon))];
    }
    /** "Minggu ke-2 November 2026": nomor minggu = urutan Senin dalam bulan (Senin pertama = minggu ke-1). */
    static function weekLabel(?string $date = null): string {
        [$mon] = self::weekRange($date);
        $t = strtotime($mon . ' 12:00:00');
        return 'Minggu ke-' . (int)ceil((int)date('j', $t) / 7) . ' ' . bulan_id((int)date('n', $t)) . ' ' . date('Y', $t);
    }
    static function monthRange(?string $date = null): array {
        $t = strtotime(($date ?: self::today()) . ' 12:00:00');
        return [date('Y-m-01', $t), date('Y-m-t', $t)];
    }

    // ------------------------------------------------------------ uang (sen = integer, tanpa float)
    static function cents($v): int { return (int)round(((float)$v) * 100); }
    static function dec(int $cents): string { return number_format($cents / 100, 2, '.', ''); }

    // ------------------------------------------------------------ pembacaan
    static function balanceCents(): int {
        return (int)round((float)DB::val('SELECT COALESCE(SUM(' . self::SQL_NET . '),0)*100 FROM budget_transactions'));
    }
    static function balance(): float { return self::balanceCents() / 100; }

    /** Ringkasan tunggal untuk landing page, dashboard, dan halaman anggaran. */
    static function summary(?string $today = null): array {
        $today = $today ?: self::today();
        [$w1, $w2] = self::weekRange($today);
        [$m1, $m2] = self::monthRange($today);
        $in = self::SQL_IN; $out = self::SQL_OUT;
        $r = DB::one("SELECT
            COALESCE(SUM(" . self::SQL_NET . "),0) balance,
            COALESCE(SUM($in),0) total_in,
            COALESCE(SUM($out),0) total_out,
            COALESCE(SUM(CASE WHEN transaction_date BETWEEN ? AND ? THEN $in ELSE 0 END),0) week_in,
            COALESCE(SUM(CASE WHEN transaction_date BETWEEN ? AND ? THEN $out ELSE 0 END),0) week_out,
            COALESCE(SUM(CASE WHEN transaction_date BETWEEN ? AND ? THEN $out ELSE 0 END),0) month_out,
            COALESCE(SUM(CASE WHEN category=? THEN $out ELSE 0 END),0) out_payroll,
            COALESCE(SUM(CASE WHEN category=? THEN $out ELSE 0 END),0) out_stock,
            COUNT(*) n
            FROM budget_transactions", [$w1, $w2, $w1, $w2, $m1, $m2, self::CAT_PAYROLL, self::CAT_STOCK]) ?: [];
        $r = array_map('floatval', $r);
        $r['week_start'] = $w1; $r['week_end'] = $w2; $r['week_label'] = self::weekLabel($today);
        $r['out_other'] = $r['total_out'] - $r['out_payroll'] - $r['out_stock'];
        return $r;
    }

    /** Dana masuk/keluar per minggu (Senin–Minggu) untuk $n minggu terakhir, minggu kosong tetap tampil. Terlama → terbaru. */
    static function weeks(int $n = 6, ?string $today = null): array {
        $today = $today ?: self::today();
        [$cur] = self::weekRange($today);
        $start = date('Y-m-d', strtotime("-" . (($n - 1) * 7) . " day", strtotime($cur . ' 12:00:00')));
        $rows = DB::all('SELECT transaction_date d, SUM(' . self::SQL_IN . ') i, SUM(' . self::SQL_OUT . ') o FROM budget_transactions WHERE transaction_date >= ? GROUP BY transaction_date', [$start]);
        $out = [];
        for ($k = 0; $k < $n; $k++) {
            $mon = date('Y-m-d', strtotime("+" . ($k * 7) . " day", strtotime($start . ' 12:00:00')));
            $out[$mon] = ['start' => $mon, 'end' => date('Y-m-d', strtotime('+6 day', strtotime($mon . ' 12:00:00'))), 'label' => self::weekLabel($mon), 'in' => 0.0, 'out' => 0.0];
        }
        foreach ($rows as $r) { [$mon] = self::weekRange($r['d']); if (isset($out[$mon])) { $out[$mon]['in'] += (float)$r['i']; $out[$mon]['out'] += (float)$r['o']; } }
        return array_values($out);
    }
    /** Dana masuk/keluar per bulan untuk $n bulan terakhir. Terlama → terbaru. */
    static function months(int $n = 6, ?string $today = null): array {
        $today = $today ?: self::today();
        $t = strtotime($today . ' 12:00:00');
        $out = [];
        for ($k = $n - 1; $k >= 0; $k--) {
            $m = strtotime(date('Y-m-01', $t) . " -$k month");
            $out[date('Y-m', $m)] = ['label' => bulan_id((int)date('n', $m)) . ' ' . date('Y', $m), 'in' => 0.0, 'out' => 0.0];
        }
        $first = array_key_first($out) . '-01';
        foreach (DB::all('SELECT transaction_date d, SUM(' . self::SQL_IN . ') i, SUM(' . self::SQL_OUT . ') o FROM budget_transactions WHERE transaction_date >= ? GROUP BY transaction_date', [$first]) as $r) {
            $k = substr($r['d'], 0, 7);
            if (isset($out[$k])) { $out[$k]['in'] += (float)$r['i']; $out[$k]['out'] += (float)$r['o']; }
        }
        return array_values($out);
    }

    // ------------------------------------------------------------ penulisan
    /** Kunci serialisasi ledger. WAJIB dipanggil di dalam DB::tx, sebelum membaca saldo. */
    static function lock(): void {
        if (!DB::pdo()->inTransaction()) throw new LogicException('Budget::lock() harus di dalam transaksi database.');
        if (DB::one('SELECT id FROM budget_lock WHERE id=1 FOR UPDATE') === null) throw new BudgetException('Konfigurasi anggaran belum lengkap (tabel budget_lock). Jalankan migration anggaran.');
    }

    private static function insertRow(string $type, string $cat, string $desc, int $cents, string $date, ?string $label, ?string $st, ?int $sid, ?string $idem): void {
        if ($cents <= 0 || $cents > self::MAX_AMOUNT_CENTS) throw new BudgetException('Nominal tidak valid.');
        DB::exec('INSERT INTO budget_transactions (type, category, description, period_label, amount, transaction_date, created_by, source_type, source_id, idem_key) VALUES (?,?,?,?,?,?,?,?,?,?)',
            [$type, $cat, mb_substr($desc, 0, 180), $label, self::dec($cents), $date, Auth::user()['id'] ?? null, $st, $sid, $idem]);
    }
    private static function existsBySource(string $st, int $sid): bool {
        return DB::val('SELECT id FROM budget_transactions WHERE source_type=? AND source_id=?', [$st, $sid]) !== null;
    }

    /**
     * Pengeluaran otomatis dari sumber (payroll / stock_request). Idempoten: sumber yang sama hanya menghasilkan 1 transaksi.
     * Harus dipanggil di dalam DB::tx yang sudah mengambil Budget::lock().
     * @return string 'created' | 'exists'
     * @throws BudgetException bila saldo tidak mencukupi (kirim ROLLBACK ke pemanggil)
     */
    static function spend(string $cat, string $desc, $amount, string $st, int $sid, string $auditAction, ?string $date = null): string {
        if (!DB::pdo()->inTransaction()) throw new LogicException('Budget::spend() harus di dalam transaksi database.');
        $cents = self::cents($amount);
        if ($cents <= 0) throw new BudgetException('Nominal pengeluaran tidak valid.');
        if (self::existsBySource($st, $sid)) return 'exists';
        $bal = self::balanceCents();
        if ($bal < $cents) throw new BudgetException('Saldo anggaran tidak mencukupi. Saldo ' . rupiah($bal / 100) . ', dibutuhkan ' . rupiah($cents / 100) . '.');
        try {
            self::insertRow('out', $cat, $desc, $cents, $date ?: self::today(), null, $st, $sid, null);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000' && self::existsBySource($st, $sid)) return 'exists';   // jaring pengaman: UNIQUE uq_budget_source
            throw $e;
        }
        audit($auditAction, mb_substr($desc . '; anggaran -' . $cents / 100, 0, 500));
        return 'created';
    }

    /**
     * Tambah dana masuk (hanya Admin Utama — diperiksa di controller DAN di sini).
     * @return string 'created' | 'duplicate'
     */
    static function addFunds($amount, string $date, ?string $label, string $desc, string $token): string {
        if (!Auth::isSuper()) throw new BudgetException('Hanya Admin Utama yang dapat menambah anggaran.');
        $cents = self::cents($amount);
        if ($cents <= 0) throw new BudgetException('Nominal harus lebih dari 0.');
        if ($cents > self::MAX_AMOUNT_CENTS) throw new BudgetException('Nominal terlalu besar.');
        if (!valid_date($date)) throw new BudgetException('Tanggal tidak valid.');
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) throw new BudgetException('Sesi formulir tidak valid. Muat ulang halaman.');
        $idem = sha1('addfunds|' . (Auth::user()['id'] ?? 0) . '|' . $token);
        $label = $label !== null && trim($label) !== '' ? mb_substr(trim($label), 0, 60) : self::weekLabel($date);
        $desc = trim($desc) !== '' ? trim($desc) : 'Anggaran Mingguan';
        return DB::tx(function () use ($cents, $date, $label, $desc, $idem) {
            self::lock();
            if (DB::val('SELECT id FROM budget_transactions WHERE idem_key=?', [$idem]) !== null) return 'duplicate';
            try {
                self::insertRow('in', self::CAT_IN, $desc, $cents, $date, $label, null, null, $idem);
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') return 'duplicate';
                throw $e;
            }
            audit('anggaran_tambah', 'IN Dana masuk ' . rupiah($cents / 100) . ' — ' . $label . ' — ' . $desc);
            return 'created';
        });
    }

    /**
     * Koreksi = reversal. Transaksi asal tidak diubah/dihapus; dibuat transaksi kebalikan yang merujuk ke asal.
     * Satu transaksi hanya bisa di-reversal sekali (UNIQUE source_type='reversal', source_id=id asal).
     * Reversal dana masuk (OUT) tunduk pada aturan saldo tidak boleh negatif.
     */
    static function reverse(int $id, string $reason): string {
        if (!Auth::isSuper()) throw new BudgetException('Hanya Admin Utama yang dapat melakukan koreksi anggaran.');
        $reason = trim($reason);
        if (mb_strlen($reason) < 5) throw new BudgetException('Alasan koreksi wajib diisi (min. 5 karakter).');
        return DB::tx(function () use ($id, $reason) {
            self::lock();
            $o = DB::one('SELECT * FROM budget_transactions WHERE id=? FOR UPDATE', [$id]);
            if (!$o) throw new BudgetException('Transaksi tidak ditemukan.');
            if (($o['source_type'] ?? '') === 'reversal') throw new BudgetException('Transaksi koreksi tidak dapat dikoreksi lagi.');
            if (self::existsBySource('reversal', $id)) throw new BudgetException('Transaksi ini sudah pernah dikoreksi.');
            $cents = self::cents($o['amount']);
            $type = $o['type'] === 'in' ? 'out' : 'in';
            if ($type === 'out' && self::balanceCents() < $cents)
                throw new BudgetException('Saldo anggaran tidak mencukupi untuk koreksi ini. Saldo ' . rupiah(self::balanceCents() / 100) . ', dibutuhkan ' . rupiah($cents / 100) . '.');
            $desc = 'Reversal transaksi #' . $id . ' (' . $o['description'] . '): ' . $reason;
            self::insertRow($type, $o['category'], $desc, $cents, self::today(), $o['period_label'], 'reversal', $id, null);
            audit('anggaran_koreksi', 'Reversal #' . $id . ' ' . strtoupper($o['type']) . ' ' . rupiah($cents / 100) . ' — ' . $reason);
            return 'created';
        });
    }

    // ------------------------------------------------------------ tampilan
    /** Teks sumber transaksi dari hasil join (lihat BudgetController::index). Tidak pernah "misterius". */
    static function sourceLabel(array $r): string {
        switch ($r['source_type'] ?? null) {
            case 'payroll':       return 'Gaji Karyawan — ' . ($r['payroll_invoice'] ?: ('payroll #' . $r['source_id']));
            case 'stock_request': return 'Pembelian ' . ($r['item_name'] ?: 'barang') . ' — ' . ($r['request_no'] ?: ('request #' . $r['source_id']));
            case 'reversal':      return 'Koreksi atas transaksi #' . $r['source_id'];
            default:              return $r['type'] === 'in' ? 'Anggaran Mingguan' . (!empty($r['period_label']) ? ' — ' . $r['period_label'] : '') : 'Pengeluaran manual';
        }
    }
}
