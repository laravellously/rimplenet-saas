<?php

class UpdateMlmMatrix extends RimplenetUpdateMlmMatrix
{
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_api_routes']);
    }

    public function register_api_routes()
    {
        register_rest_route('/rimplenet/v1', 'mlm-matrix', [
            'methods' => 'PUT',
            'callback' => [$this, 'api_update_mlm_matrix']
        ]);
    }

    public function api_update_mlm_matrix(WP_REST_Request $req)
    {
        $req  = [
            'matrix_name' => sanitize_text_field($req['matrix_name'] ?? ''),
            'matrix_description' => sanitize_text_field($req['matrix_description'] ?? ''),
            'matrix_id' => sanitize_text_field($req['matrix_id'] ?? ''),
        ];

        $this->updateMlmMatrix($req);    
        return new WP_REST_Response(self::$response, self::$response['status_code']);
    }
}

$UpdateMlmMatrix = new UpdateMlmMatrix;