<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Password\Presentation\Admin\Assets;

final class PasswordAssets
{
    public static function register(): void
    {
        add_action('admin_enqueue_scripts', [self::class, 'enqueue']);
    }

    public static function enqueue(): void
    {
        // Dashicons đã sẵn trong admin, nhưng gọi lại cho chắc nếu màn hình tùy biến
        wp_enqueue_style('dashicons');

        // CSS nhỏ: đặt nút mắt trong input
        $css = <<<CSS
                .tmt-password-field{position:relative;display:inline-block}
                .tmt-password-field .regular-text{padding-right:42px}
                .tmt-password-toggle{
                position:absolute; right:6px; top:50%; transform:translateY(-50%);
                border:0; background:transparent; padding:4px; cursor:pointer; line-height:1
                }
                .tmt-password-toggle:focus{outline:2px solid #2271b1; outline-offset:1px; border-radius:3px}
                CSS;
        wp_add_inline_style('dashicons', $css);

        // JS toggle: đổi type + icon
        $js = <<<JS
                (function(){
                function toggleEye(btn){
                    var id = btn.getAttribute('data-target');
                    var input = id ? document.getElementById(id) : null;
                    if(!input) return;

                    var showing = input.getAttribute('type') === 'text';
                    input.setAttribute('type', showing ? 'password' : 'text');

                    // đổi icon
                    var icon = btn.querySelector('.dashicons');
                    if(icon){
                    icon.classList.toggle('dashicons-visibility', showing);
                    icon.classList.toggle('dashicons-hidden', !showing);
                    }

                    // đổi nhãn hỗ trợ screen-reader
                    btn.setAttribute('aria-label', showing ? btn.dataset.labelShow || 'Hiện mật khẩu' : btn.dataset.labelHide || 'Ẩn mật khẩu');
                }

                document.addEventListener('click', function(e){
                    var btn = e.target.closest('.tmt-password-toggle');
                    if(!btn) return;
                    e.preventDefault();
                    toggleEye(btn);
                }, false);
                })();
                JS;
                
        wp_register_script('tmt-crm-password-eye', '', [], false, true);
        wp_add_inline_script('tmt-crm-password-eye', $js);
        wp_enqueue_script('tmt-crm-password-eye');
    }
}
