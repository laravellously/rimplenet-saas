<?php
require_once(ABSPATH.'wp-admin/includes/user.php');

class RimplenetDeleteUser
{
    public $validation_error = [];

    public function __construct()
    {
        add_shortcode('rimplenet-delete-user', array($this, 'delete_user_test'));
    }

    public function delete_user_test() {
        ob_start();
        var_dump($this->delete_user(47));
        return ob_get_clean();
    }

    public function delete_user($user_id, $access_token = null)
    {
        if(!empty($this->validation_error)) return $this->response(400, "failed", "Validation error", [], $this->validation_error);
    
        if (empty($this->validation_error)) {

            $request = [
                "user_id" => $user_id
            ];
            
            do_action('rimplenet_hooks_and_monitors_on_started', $action='rimplenet_delete_users', $auth=null ,$request);

            // if(!$this->authorization(get_current_user_id())) return $this->response(403, "failed", "Permission denied", [], ["unauthorize"=>"caller_id is not authorized"]);

            $deleted = wp_delete_user($user_id);

            if ($deleted) return $this->response(200, true, "User deleted successfully", [], []);

            return $this->response(404, "Failed", "User not found", [], []);

        }


    }

    public function validate($user_id)
    {

        $user_id_error = [];

        if ($user_id == '') {
            $user_id_error[] = 'user_id is required';
        }
        if (!empty($user_id_error)) {
            $this->validation_error[] = ['user_id' => $user_id_error];
        }

    }

    public function response($status_code, $status, $message, $data=[], $error=[])
    {
        return [
            "status_code" => $status_code,
            "status" => $status,
            "message" => $message,
            "data" => $data,
            "error" =>$error
        ];
    }

    public function authorization($caller_id)
    {
        $user = get_user_by('ID', $caller_id);

        if (user_can($user, 'administrator')) {
            
            return true;

        }

        return false;
    }
    
}

$RimplenetDeleteUser = new RimplenetDeleteUser();