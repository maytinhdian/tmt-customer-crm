<?php

/**
 * @var string $q
 * @var array  $items
 * @var int    $total
 * @var string $add_url
 */

use TMT\CRM\Modules\License\Presentation\Admin\ListTable\CredentialListTable;

defined('ABSPATH') || exit;

$table = new CredentialListTable();
$table->set_data($items, $total);
$table->prepare_items();
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html__('Licenses', 'tmt-crm'); ?></h1>
    <a href="<?php echo esc_url($add_url); ?>" class="page-title-action"><?php _e('Add New', 'tmt-crm'); ?></a>
    <hr class="wp-header-end" />

    <form method="get">
        <input type="hidden" name="page" value="tmt-crm-licenses" />
        <p class="search-box">
            <label class="screen-reader-text" for="license-search-input"><?php _e('Search Licenses', 'tmt-crm'); ?></label>
            <input type="search" id="license-search-input" name="s" value="<?php echo esc_attr($q); ?>" />
            <input type="submit" id="search-submit" class="button" value="<?php _e('Search', 'tmt-crm'); ?>">
        </p>
    </form>

    <form method="post">
        <?php $table->display(); ?>
    </form>
</div>