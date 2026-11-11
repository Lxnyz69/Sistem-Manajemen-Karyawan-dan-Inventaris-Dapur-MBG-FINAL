<?php
class InventoryController {
    const UNITS = ['pcs','kg','gram','liter','ml','dus','box','pack','lusin','meter','set','unit','kodi','rim','karung','botol'];

    private function item(int $id): array {
        $i = DB::one('SELECT * FROM inventory_items WHERE id=?', [$id]);
        if (!$i) abort(404, 'Barang tidak ditemukan.');
        return $i;
    }
    private function filters(): array {
        $w = []; $p = [];
        $q = trim($_GET['q'] ?? '');
        if ($q !== '') { $w[] = '(name LIKE ? OR code LIKE ?)'; array_push($p, "%$q%", "%$q%"); }
        if (($_GET['low'] ?? '') === '1') $w[] = 'stock <= min_stock';
        if (in_array($_GET['unit'] ?? '', self::UNITS, true)) { $w[] = 'unit = ?'; $p[] = $_GET['unit']; }
        return [$w ? 'WHERE ' . implode(' AND ', $w) : '', $p];
    }
    function index(): void {
        [$where, $params] = $this->filters();
        $pg = DB::paginate('*, (stock*price) AS total_value', 'inventory_items', $where, $params, 'id DESC', (int)($_GET['page'] ?? 1));
        render('inventory/index', ['pg' => $pg, 'title' => 'Stok Barang']);
    }
    function export(): void {
        [$where, $params] = $this->filters();
        $rows = DB::all("SELECT code, name, unit, price, stock, (stock*price) tv, updated_at FROM inventory_items $where ORDER BY code", $params);
        audit('export_stok', count($rows) . ' baris');
        csv_download('stok-' . date('Ymd') . '.csv', ['Kode', 'Nama', 'Satuan', 'Harga', 'Stok', 'Total Nilai', 'Diperbarui'], array_map('array_values', $rows));
    }
    function createForm(): void { render('inventory/form', ['i' => null, 'title' => 'Tambah Barang']); }

    private function validate(?int $id): array {
        $code = strtoupper(post('code')); $name = post('name'); $unit = post('unit');
        $price = parse_money(post('price')); $min = max(0, (int)post('min_stock'));
        $err = [];
        if (!preg_match('/^[A-Z0-9._-]{2,40}$/', $code)) $err[] = 'Kode barang 2-40 karakter (huruf/angka/-/_/.).';
        if (mb_strlen($name) < 2) $err[] = 'Nama barang wajib diisi.';
        if (!in_array($unit, self::UNITS, true)) $err[] = 'Satuan tidak valid.';
        if ($code && DB::val('SELECT id FROM inventory_items WHERE code=? AND id<>?', [$code, $id ?? 0])) $err[] = 'Kode barang sudah dipakai.';
        return [$err, compact('code', 'name', 'unit', 'price', 'min')];
    }
    function store(): void {
        [$err, $d] = $this->validate(null);
        $stock = (int)post('stock');
        if ($stock < 0) $err[] = 'Stok awal tidak boleh negatif.';
        [$photo, $perr] = save_image($_FILES['photo'] ?? [], 'items');
        if ($perr) $err[] = $perr;
        if ($err) { delete_upload($photo); with_old($_POST); flash('error', implode(' ', $err)); redirect('/admin/stok/barang/baru'); }
        try {
            DB::tx(function () use ($d, $stock, $photo) {
                DB::exec('INSERT INTO inventory_items (code,name,description,unit,price,stock,min_stock,photo) VALUES (?,?,?,?,?,?,?,?)',
                    [$d['code'], $d['name'], post('description'), $d['unit'], $d['price'], $stock, $d['min'], $photo]);
                $id = DB::id();
                if ($stock > 0) DB::exec("INSERT INTO stock_movements (item_id,type,qty,balance_after,ref,note,created_by) VALUES (?,'in',?,?,'AWAL','Stok awal',?)", [$id, $stock, $stock, Auth::user()['id']]);
            });
        } catch (Throwable $e) { delete_upload($photo); throw $e; }
        audit('barang_tambah', "{$d['code']} {$d['name']}");
        Notify::checkLowStock((int)DB::val('SELECT id FROM inventory_items WHERE code=?', [$d['code']]));
        flash('success', 'Barang ditambahkan.');
        redirect('/admin/stok');
    }
    function show(string $id): void {
        $i = $this->item((int)$id);
        $mv = DB::all('SELECT m.*, u.full_name FROM stock_movements m LEFT JOIN users u ON u.id=m.created_by WHERE m.item_id=? ORDER BY m.id DESC LIMIT 30', [$i['id']]);
        render('inventory/show', ['i' => $i, 'mv' => $mv, 'title' => 'Detail Barang']);
    }
    function editForm(string $id): void { render('inventory/form', ['i' => $this->item((int)$id), 'title' => 'Edit Barang']); }
    function update(string $id): void {
        $i = $this->item((int)$id);
        [$err, $d] = $this->validate((int)$id);
        [$photo, $perr] = save_image($_FILES['photo'] ?? [], 'items');
        if ($perr) $err[] = $perr;
        if ($err) { delete_upload($photo); with_old($_POST); flash('error', implode(' ', $err)); redirect("/admin/stok/barang/$id/edit"); }
        try {
            DB::exec('UPDATE inventory_items SET code=?, name=?, description=?, unit=?, price=?, min_stock=?, photo=? WHERE id=?',
                [$d['code'], $d['name'], post('description'), $d['unit'], $d['price'], $d['min'], $photo ?: $i['photo'], $i['id']]);
        } catch (Throwable $e) { delete_upload($photo); throw $e; }
        if ($photo && $i['photo']) delete_upload($i['photo']); // hapus lama hanya setelah yang baru sukses
        Notify::checkLowStock((int)$id);   // batas minimum bisa berubah
        audit('barang_edit', "{$d['code']} {$d['name']}");
        flash('success', 'Barang diperbarui.');
        redirect('/admin/stok');
    }
    function move(string $id): void {
        $type = post('type'); $qty = (int)post('qty'); $note = mb_substr(post('note'), 0, 255);
        if (!in_array($type, ['out', 'adjust'], true) || $qty < 0 || ($type === 'out' && $qty < 1)) { flash('error', 'Input tidak valid.'); back('/admin/stok'); }
        if (mb_strlen($note) < 3) { flash('error', 'Catatan wajib diisi.'); back("/admin/stok/barang/$id"); }
        $res = DB::tx(function () use ($id, $type, $qty, $note) {
            $i = DB::one('SELECT * FROM inventory_items WHERE id=? FOR UPDATE', [(int)$id]);
            if (!$i) return 'Barang tidak ditemukan.';
            $new = $type === 'out' ? $i['stock'] - $qty : $qty;
            if ($new < 0) return 'Stok tidak mencukupi.';
            $delta = $new - $i['stock'];
            DB::exec('UPDATE inventory_items SET stock=? WHERE id=?', [$new, $i['id']]);
            DB::exec('INSERT INTO stock_movements (item_id,type,qty,balance_after,note,created_by) VALUES (?,?,?,?,?,?)', [$i['id'], $type, $delta, $new, $note, Auth::user()['id']]);
            audit('stok_' . $type, "{$i['code']}: {$i['stock']} -> $new");
            return true;
        });
        $res === true ? flash('success', 'Stok diperbarui.') : flash('error', $res);
        if ($res === true) Notify::checkLowStock((int)$id);
        back("/admin/stok/barang/$id");
    }
    function delete(string $id): void {
        $i = $this->item((int)$id);
        $mv = (int)DB::val("SELECT COUNT(*) FROM stock_movements WHERE item_id=? AND ref<>'AWAL'", [$i['id']]);
        $rq = (int)DB::val('SELECT COUNT(*) FROM stock_requests WHERE item_id=?', [$i['id']]);
        if ($mv || $rq) { flash('error', 'Barang punya riwayat transaksi, tidak bisa dihapus. Set stok ke 0 lewat penyesuaian.'); back('/admin/stok'); }
        DB::tx(function () use ($i) {
            DB::exec('DELETE FROM stock_movements WHERE item_id=?', [$i['id']]);
            DB::exec('DELETE FROM inventory_items WHERE id=?', [$i['id']]);
        });
        delete_upload($i['photo']);
        audit('barang_hapus', "{$i['code']} {$i['name']}");
        flash('success', 'Barang dihapus.');
        back('/admin/stok');
    }
}