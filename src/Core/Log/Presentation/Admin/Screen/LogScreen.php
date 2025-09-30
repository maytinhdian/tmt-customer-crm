<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Log\Presentation\Admin\Screen;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Domain\Repositories\LogRepositoryInterface;

final class LogScreen
{
    public const PAGE_SLUG = 'tmt-crm-logs';

    public static function register_menu(): void
    {
        add_action('admin_menu', static function () {
            add_submenu_page(
                'tmt-crm', // parent slug của plugin admin
                __('Logs', 'tmt-crm'),
                __('Logs', 'tmt-crm'),
                'manage_options',
                self::PAGE_SLUG,
                [self::class, 'render_page']
            );
        });
    }

    public static function render_page(): void
    {
        $repo = Container::get(LogRepositoryInterface::class);
        $level = isset($_GET['level']) ? sanitize_text_field((string)$_GET['level']) : null;
        $channel = isset($_GET['channel']) ? sanitize_text_field((string)$_GET['channel']) : null;
        $q = isset($_GET['s']) ? sanitize_text_field((string)$_GET['s']) : null;
        $page = max(1, (int)($_GET['paged'] ?? 1));
        $per_page = 20;

        $result = $repo->search($level, $channel, $q, $page, $per_page);
        $items = $result['items'];
        $total = $result['total'];
        $total_pages = max(1, (int)ceil($total / $per_page));

        echo '<div class="wrap"><h1>CRM Logs</h1>';
        echo '<form method="get"><input type="hidden" name="page" value="'.esc_attr(self::PAGE_SLUG).'"/>';
        echo '<p><input type="text" name="s" value="'.esc_attr($q ?? '').'" placeholder="Search message..." /> ';
        echo '<select name="level"><option value="">All levels</option>';
        foreach (['debug','info','warning','error','critical'] as $lv) {
            printf('<option value="%1$s"%2$s>%1$s</option>', esc_attr($lv), selected($lv, $level, false));
        }
        echo '</select> ';
        echo '<input type="text" name="channel" value="'.esc_attr($channel ?? '').'" placeholder="channel..." /> ';
        submit_button(__('Filter'), 'secondary', '', false);
        echo '</p></form>';

        echo '<table class="widefat fixed striped"><thead><tr>';
        echo '<th>ID</th><th>Time (UTC)</th><th>Level</th><th>Channel</th><th>Message</th><th>Context</th>';
        echo '</tr></thead><tbody>';

        if (!$items) {
            echo '<tr><td colspan="6">No logs.</td></tr>';
        } else {
            foreach ($items as $dto) {
                /** @var \TMT\CRM\Core\Log\Application\DTO\LogEntryDTO $dto */
                echo '<tr>';
                echo '<td>'.(int)$dto->id.'</td>';
                echo '<td>'.esc_html($dto->created_at).'</td>';
                echo '<td>'.esc_html(strtoupper($dto->level)).'</td>';
                echo '<td>'.esc_html($dto->channel).'</td>';
                echo '<td>'.esc_html($dto->message).'</td>';
                echo '<td><code style="white-space:pre-wrap;">'.esc_html($dto->context ? wp_json_encode($dto->context, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : '').'</code></td>';
                echo '</tr>';
            }
        }
        echo '</tbody></table>';

        // Pagination
        if ($total_pages > 1) {
            echo '<div class="tablenav"><div class="tablenav-pages">';
            $base = add_query_arg(['page' => self::PAGE_SLUG, 'level' => $level, 'channel' => $channel, 's' => $q, 'paged' => '%#%'], admin_url('admin.php'));
            echo paginate_links([
                'base' => $base,
                'format' => '',
                'current' => $page,
                'total' => $total_pages,
            ]);
            echo '</div></div>';
        }

        echo '</div>';
    }
}
