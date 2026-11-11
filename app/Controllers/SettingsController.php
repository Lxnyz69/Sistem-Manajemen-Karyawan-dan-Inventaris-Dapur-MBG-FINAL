<?php
class SettingsController {
    const TEXT = ['site_name', 'site_title', 'footer_text', 'company_name', 'company_address', 'company_phone', 'company_email', 'employee_code_prefix'];
    const COLORS = ['color_primary', 'color_secondary', 'color_sidebar', 'color_button'];

    private function put(string $k, string $v): void {
        DB::exec('INSERT INTO website_settings (skey, svalue) VALUES (?,?) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)', [$k, $v]);
    }
    function form(): void { render('settings/form', ['title' => 'Setting Website']); }
    function save(): void {
        $err = []; $vals = [];
        foreach (self::TEXT as $k) $vals[$k] = mb_substr(post($k), 0, 255);
        if ($vals['site_name'] === '') $err[] = 'Nama website wajib diisi.';
        if (!preg_match('/^[A-Z]{2,6}$/', $vals['employee_code_prefix'])) $err[] = 'Prefix ID karyawan: 2-6 huruf kapital.';
        if ($vals['company_email'] !== '' && !filter_var($vals['company_email'], FILTER_VALIDATE_EMAIL)) $err[] = 'Email perusahaan tidak valid.';
        foreach (self::COLORS as $k) {
            $c = post($k);
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $c)) $err[] = "Warna $k tidak valid."; else $vals[$k] = $c;
        }
        $oc = post('office_coords'); $vals['office_lat'] = ''; $vals['office_lng'] = '';
        if ($oc !== '') { $pc = parse_coords($oc); if ($pc) { $vals['office_lat'] = (string)$pc[0]; $vals['office_lng'] = (string)$pc[1]; } else $err[] = 'Titik lokasi kantor tidak valid. Gunakan format "lat, lng", contoh: -6.2088, 106.8456.'; }
        $vals['maintenance'] = !empty($_POST['maintenance']) ? '1' : '0';
        $vals['show_login_button'] = !empty($_POST['show_login_button']) ? '1' : '0';
        $vals['registration_open'] = !empty($_POST['registration_open']) ? '1' : '0';
        [$logo, $lerr] = save_image($_FILES['logo'] ?? [], 'site', 2097152);
        if ($lerr) $err[] = $lerr;
        if ($err) { delete_upload($logo); flash('error', implode(' ', $err)); redirect('/admin/pengaturan'); }

        $oldLogo = setting('logo');
        try { DB::tx(function () use ($vals, $logo) { foreach ($vals as $k => $v) $this->put($k, $v); if ($logo) $this->put('logo', $logo); }); }
        catch (Throwable $e) { delete_upload($logo); throw $e; }
        if ($logo && $oldLogo) delete_upload($oldLogo);
        audit('pengaturan_simpan', 'Setting website diperbarui');
        flash('success', 'Pengaturan disimpan.');
        redirect('/admin/pengaturan');
    }
}