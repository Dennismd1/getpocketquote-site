<?php
// PocketQuote waitlist: saves sign-ups outside the web folder and emails Dennis.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /'); exit; }
if (!empty($_POST['website'])) { header('Location: /?ok=1'); exit; } // spam bot trap
$clean = function ($v, $max) {
    $v = trim(substr((string)$v, 0, $max));
    $v = str_replace(["\r", "\n"], ' ', $v);
    if ($v !== '' && strpos('=+-@', $v[0]) !== false) { $v = "'" . $v; } // block spreadsheet formulas
    return $v;
};
$email = trim((string)($_POST['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 200) { header('Location: /?e=1'); exit; }
$name = $clean($_POST['name'] ?? '', 100);
$company = $clean($_POST['company'] ?? '', 100);
$row = [date('c'), $name, $company, $email, $_SERVER['REMOTE_ADDR'] ?? ''];
$file = '/home/domainpl/pq-waitlist.csv';
$new = !file_exists($file);
$fh = fopen($file, 'a');
if ($fh) {
    flock($fh, LOCK_EX);
    if ($new) { fputcsv($fh, ['time', 'name', 'company', 'email', 'ip']); }
    fputcsv($fh, $row);
    flock($fh, LOCK_UN);
    fclose($fh);
}
@mail('dennis.dowding@runlevelsystems.com', 'New PocketQuote waitlist sign-up',
    "Name: $name\nCompany: $company\nEmail: $email\nTime: {$row[0]}\n",
    'From: PocketQuote Waitlist <waitlist@getpocketquote.com>');
header('Location: /?ok=1');
