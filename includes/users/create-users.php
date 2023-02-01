<?php

class RimplenetCreateUser
{
    public $validation_error = [];

    public function __construct()
    {
        add_shortcode('rimplenet-create-user', array($this, 'create_user_test'));
    }

    public function create_user_test() {
        ob_start();
        var_dump($this->create_user("taiwo1@gmail.com", "taiwo", "abc123",["somename"=>"ttttt","somename1"=>"aaaaaaaa"]));
        return ob_get_clean();
    }

    public function create_user($user_email, $user_login, $user_pass, $metas=[])
    {
        
        $validation = $this->validate($user_email, $user_login, $user_pass);
        if(!empty($this->validation_error)) return $this->response(400, "failed", "Validation error", [], $this->validation_error);
            
        if (empty($this->validation_error)) {

            $request = [
                "user_email" => $user_email,
                "user_login" => $user_login,
                "user_password" => $user_pass,
                "metas" => $metas
            ];
            
            do_action('rimplenet_hooks_and_monitors_on_started', $action='rimplenet_create_users', $auth=null ,$request);

            $new_user = wp_insert_user(['user_email'=>$user_email, 'user_login'=>$user_login, 'user_pass'=>$user_pass]);

            if(!empty($metas)) {
                
                foreach($metas as $meta_key=>$meta_value) {
                    
                    add_user_meta($new_user, $meta_key, $meta_value);
                }

            }

            return $this->response(201, true, "User created successfully", ["id"=>$new_user], $this->validation_error);
        }

    }

    public function validate($user_email, $user_login, $user_pass)
    {
        $user_login_error = [];
        $user_email_error = [];
        $user_pass_error = [];


        $user['user_login'] = strtolower(sanitize_text_field($user_login));
	    $user['user_email'] = strtolower(sanitize_text_field($user_email));
	    $user['user_pass'] = $user_pass;

        if ($user['user_login'] == '') {
            $user_login_error[] = 'username is required';
        }
        if (preg_match('/\s/', $user['user_login']) != 0) {
            $user_login_error[] = 'username must not contain space';
        }
        if (strlen($user['user_login']) < 4) {
            $user_login_error[] = 'username must be atleast 4 chars';
        }
        if (username_exists($user['user_login'])) {
            $user_login_error[] = 'username already taken';
        }
        if (!empty($user_login_error)) {
            $this->validation_error[] = ['user_login' => $user_login_error];
        }

        if ($user['user_email'] == '') {
            $user_email_error[] = 'user_email is required';
        }
        if ($user['user_email'] && !is_email($user['user_email'])) {
            $user_email_error[] = 'Invalid user_email';
        }
        if (email_exists($user['user_email'])) {
            $user_email_error[] = 'user_email already taken';
        }
        if (!empty($user_email_error)) {
            $this->validation_error[] = ['user_email' => $user_email_error];
        }

        if ($user['user_pass'] == '') {
            $user_pass_error[] = 'user_password is required';
        }
        if (strlen($user['user_pass']) < 6) {
            $user_pass_error[] = 'Please enter at least 6 characters for the user_password';
        }
        // if (preg_match('/.*[a-z]+.*/i', $user['user_pass']) == 0) {
        //     $user_pass_error[] = 'user_pass needs at least one letter';
        // }
        // if (preg_match('/.*\d+.*/i', $user['user_pass']) == 0) {
        //     $user_pass_error[] = 'user_pass needs at least one number';
        // }
        if (!empty($user_pass_error)) {
            $this->validation_error[] = ['user_pass' => $user_pass_error];
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

$RimplenetCreateUser = new RimplenetCreateUser();