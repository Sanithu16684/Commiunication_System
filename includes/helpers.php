<?php
// includes/helpers.php - General utility functions and audit loggers

if (!function_exists('get_settings')) {
    function get_settings($pdo) {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }
}

if (!function_exists('format_currency')) {
    function format_currency($amount, $currency = 'Rs.') {
        return $currency . ' ' . number_format((float)$amount, 2);
    }
}

if (!function_exists('log_activity')) {
    function log_activity($pdo, $user_id, $action, $module, $details = '') {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, module, details, ip_address) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $action, $module, $details, $ip]);
        } catch (Exception $e) {
            // Silently ignore logging failure to not break main flow
        }
    }
}

if (!function_exists('log_stock_audit')) {
    function log_stock_audit($pdo, $product_id, $user_id, $audit_type, $prev_qty, $new_qty, $qty_change, $reason = '') {
        try {
            $stmt = $pdo->prepare("INSERT INTO stock_audits (product_id, user_id, audit_type, previous_qty, new_qty, qty_change, reason) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$product_id, $user_id, $audit_type, $prev_qty, $new_qty, $qty_change, $reason]);
        } catch (Exception $e) {
            // Silently ignore or log error
        }
    }
}

if (!function_exists('generate_invoice_no')) {
    function generate_invoice_no($pdo) {
        $prefix = 'INV-' . date('Ymd') . '-';
        $stmt = $pdo->prepare("SELECT invoice_no FROM invoices WHERE invoice_no LIKE ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$prefix . '%']);
        $last = $stmt->fetchColumn();
        if ($last) {
            $num = (int)str_replace($prefix, '', $last);
            $next = str_pad($num + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $next = '0001';
        }
        return $prefix . $next;
    }
}

if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('set_flash')) {
    function set_flash($type, $message) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message,
            'msg' => $message
        ];
    }
}

if (!function_exists('get_flash')) {
    function get_flash() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}
