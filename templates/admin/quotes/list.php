<?php

use TMT\CRM\Modules\Quotation\Presentation\Admin\Screen\QuoteScreen;
use TMT\CRM\Modules\Quotation\Presentation\Admin\ListTable\QuoteListTable;
use TMT\CRM\Shared\Container\Container;

/** @var \TMT\CRM\Modules\Quotation\Domain\Repositories\QuoteQueryRepositoryInterface $repo */
$repo  = Container::get('quote-query-repo');
$table = new QuoteListTable($repo);
$table->prepare_items();

$list_url = admin_url('admin.php?page=' . QuoteScreen::PAGE_SLUG);
$new_url  = add_query_arg(['page' => QuoteScreen::PAGE_SLUG, 'action' => 'new'], admin_url('admin.php'));
?>
<div class="wrap tmtcrm">
    <h1 class="wp-heading-inline"><?php esc_html_e('Báo giá', 'tmt-crm'); ?></h1>
    <a href="<?php echo esc_url($new_url); ?>" class="page-title-action"><?php _e('Tạo báo giá', 'tmt-crm'); ?></a>
    <hr class="wp-header-end" />

    <?php echo $table->views(); ?>
    <form method="get">
        <input type="hidden" name="page" value="<?php echo esc_attr(QuoteScreen::PAGE_SLUG); ?>" />
        <?php $table->search_box(__('Tìm kiếm', 'tmt-crm'), 'tmt-crm-quotes'); ?>
        <?php $table->display(); ?>
    </form>
</div>