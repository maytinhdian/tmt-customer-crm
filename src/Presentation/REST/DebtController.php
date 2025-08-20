<?php

namespace TMT\CRM\Presentation\REST;

use TMT\CRM\Application\Services\DebtService;

final class DebtController
{
    public function __construct(private DebtService $service) {}

    public function create(\WP_REST_Request $req)
    {
        $data = $req->get_json_params();
        $id = $this->service->create((int)$data['invoice_id'], (float)$data['amount'], $data['due_date']);
        return rest_ensure_response(['id' => $id]);
    }

    public function mark_paid(\WP_REST_Request $req)
    {
        $id = (int)$req['id'];
        $ok = $this->service->mark_paid($id);
        return rest_ensure_response(['ok' => $ok]);
    }
}
