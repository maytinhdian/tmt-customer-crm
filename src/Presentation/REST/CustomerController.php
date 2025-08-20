<?php
namespace TMT\CRM\Presentation\REST;

use TMT\CRM\Application\DTO\CustomerDTO;

final class CustomerController {
    public static function index(\WP_REST_Request $req) {
        return rest_ensure_response(['items' => [], 'total' => 0]);
    }
    public static function store(\WP_REST_Request $req) {
        $data = $req->get_json_params() ?: [];
        $dto  = new CustomerDTO(
            $data['full_name'] ?? '',
            $data['phone'] ?? '',
            $data['email'] ?? ''
        );
        // TODO: gọi service create
        return rest_ensure_response(['ok' => true]);
    }
}
