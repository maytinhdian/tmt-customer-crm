<?php

/**
 * Giao diện Dashboard CRM
 * Path: templates/admin/dashboard.php
 */

defined('ABSPATH') || exit;

/** @var int $total_customers */
/** @var int $new_this_month */
/** @var int $total_projects */
?>

<div class="wrap tmt-crm-dashboard">
    <h1 class="wp-heading-inline">TMT CRM - Dashboard</h1>

    <div class="tmt-crm-stats">
        <div class="tmt-crm-card">
            <h2><?php echo esc_html($total_customers); ?></h2>
            <p>Tổng khách hàng</p>
        </div>
        <div class="tmt-crm-card">
            <h2><?php echo esc_html($new_this_month); ?></h2>
            <p>Khách hàng mới trong tháng</p>
        </div>
        <div class="tmt-crm-card">
            <h2><?php echo esc_html($total_projects); ?></h2>
            <p>Dự án đang theo dõi</p>
        </div>
    </div>

    <h2>Hoạt động gần đây</h2>
    <ul class="tmt-crm-activities">
        <li>Khách hàng A vừa được thêm.</li>
        <li>Dự án "Lắp đặt camera" vừa cập nhật tiến độ.</li>
        <li>Khách hàng B đã ký hợp đồng.</li>
    </ul>
</div>

<style>
    .tmt-crm-stats {
        display: flex;
        gap: 20px;
        margin: 20px 0;
    }

    .tmt-crm-card {
        flex: 1;
        background: #fff;
        padding: 20px;
        border: 1px solid #ccd0d4;
        border-radius: 8px;
        text-align: center;
    }

    .tmt-crm-card h2 {
        font-size: 28px;
        margin: 0 0 10px;
    }
</style>