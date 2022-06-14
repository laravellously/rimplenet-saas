<?php

class RimplenetUpdateUserApi
{
    public $validation_error = [];
    public $user_id;

    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_api_routes'));
    }

    public function register_api_routes()
    {
        register_rest_route(
            'rimplenet/v1', '/users',
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_user']
            ]
        );
    }

    public function update_user(WP_REST_Request $request)
    {
        $headers = getallheaders();
        $access_token = $headers['Authorization'];

        $user = new RimplenetUpdateUser();
        $update_user = $user->update_user(
            $access_token,
            $request->get_param('user_id'),
            $request->get_param('user_email'),
            [
                "old_user_pass" => $request->get_param('old_user_pass'),
                "new_user_pass" => $request->get_param('new_user_pass')
            ],
            [
                "first_name" => $request->get_param('first_name'),
                "last_name" => $request->get_param('last_name')
            ]
        );
        
        return new WP_REST_Response($update_user);
        
    }

}

$RimplenetUpdateUserApi = new RimplenetUpdateUserApi();