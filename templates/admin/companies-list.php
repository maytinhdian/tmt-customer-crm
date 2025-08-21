<?php
/** @var array $items */
/** @var int $total */
/** @var string $baseUrl */
?>
<div class="wrap">
<h1 class="wp-heading-inline">Công ty</h1>
<a href="<?php echo esc_url( add_query_arg(['page'=>'tmt-crm-companies','action'=>'edit'], admin_url('admin.php')) ); ?>" class="page-title-action">Thêm mới</a>
<hr class="wp-header-end">


<form method="get">
<input type="hidden" name="page" value="tmt-crm-companies" />
<p class="search-box">
<label class="screen-reader-text" for="company-search-input">Tìm kiếm:</label>
<input type="search" id="company-search-input" name="s" value="<?php echo esc_attr($_GET['s'] ?? ''); ?>" />
<input type="submit" id="search-submit" class="button" value="Tìm" />
</p>
</form>


<table class="widefat fixed striped">
<thead>
<tr>
<th>Id</th><th>Tên công ty</th><th>MST</th><th>Phone</th><th>Email</th><th>Cập nhật</th>
</tr>
</thead>
<tbody>
<?php if (empty($items)): ?>
<tr><td colspan="6">Chưa có dữ liệu</td></tr>
<?php else: foreach ($items as $c): ?>
<tr>
<td><?php echo (int)$c->id; ?></td>
<td>
<a href="<?php echo esc_url( add_query_arg(['page'=>'tmt-crm-companies','action'=>'edit','id'=>$c->id], admin_url('admin.php')) ); ?>">
<?php echo esc_html($c->name); ?>
</a>
</td>
<td><?php echo esc_html($c->taxCode); ?></td>
<td><?php echo esc_html($c->phone); ?></td>
<td><?php echo esc_html($c->email); ?></td>
<td><?php echo esc_html($c->updatedAt); ?></td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>