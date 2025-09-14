<?php
// src/Presentation/Support/ScreenUrl.php
namespace TMT\CRM\Presentation\Admin\Support;

use TMT\CRM\Presentation\Admin\Screen\CompanyContactsScreen;

final class ScreenUrl
{
    /** URL tới admin.php?page=... + merge state (view, tab, paged, order…) */
    public static function page(string $page_slug, array $args = [], array $state = []): string
    {
        $base = admin_url('admin.php');
        $query = array_filter(array_merge(['page' => $page_slug], $args, self::normalize_state($state)), 'strlen');
        return add_query_arg($query, $base);
    }

    /** CompanyContactsScreen */
    public static function company_contacts(int $company_id, array $state = []): string
    {
        return self::page(CompanyContactsScreen::PAGE_SLUG, [
            'company_id' => (int) $company_id,
        ], $state);
    }

    /** CompanyContactsScreen: mở form sửa */
    public static function company_contacts_edit(int $company_id, int $contact_id, array $state = []): string
    {
        $state = array_merge(['view' => 'edit', 'contact_id' => (int) $contact_id], $state);
        return self::company_contacts($company_id, $state);
    }

    private static function normalize_state(array $state): array
    {
        // Chỉ nhận các key an toàn/thường dùng
        $allowed = ['view', 'tab', 'paged', 'orderby', 'order', 's', 'contact_id', 'active_only', 'role'];
        return array_intersect_key($state, array_flip($allowed));
    }
}
