<?php

class BaseController extends Controller
{
    public $layout = 'layout.html';

    public function init()
    {
        if (!session_id()) {
            session_start();
        }

        header('Content-type: text/html; charset=utf-8');
        require APP_DIR.'/protected/include/functions.php';

        $this->ATSAST_CDN = CONFIG::GET('ATSAST_CDN');
        $this->ATSAST_DOMAIN = $GLOBALS['http_scheme'].$_SERVER['HTTP_HOST'];

        $this->islogin = is_login();
        $this->is_admin = false;
        $this->allow_judge = false;

        if ($this->islogin) {
            $db = new Model('users');
            $user_info = $db->find(['OPENID' => $_SESSION['OPENID']]);
            if ($user_info['p_level'] == '2') {
                $this->is_admin = true;
                $this->allow_judge = true;
            } elseif ($user_info['p_level'] == '1') {
                $this->allow_judge = true;
            }
            $this->display_name = empty($user_info['real_name']) ? $user_info['name'] : $user_info['real_name'];
        }
    }

    public function jump($url, $delay = 0)
    {
        echo "<html><head><meta http-equiv='refresh' content='{$delay};url={$url}'></head><body></body></html>";
        exit;
    }
}
