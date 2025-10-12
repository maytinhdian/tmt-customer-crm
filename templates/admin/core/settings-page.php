<div class="wrap">
    <h1><?php esc_html_e('Cài đặt CRM', 'tmt-crm'); ?></h1>
    <form method="post" action="options.php">
        <?php
        settings_fields('tmt_crm_settings_group');
        do_settings_sections('tmt-crm-settings');
        submit_button();
        ?>
    </form>
</div>