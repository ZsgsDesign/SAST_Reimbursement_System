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
        $this->is_judge = false;

        if ($this->islogin) {
            $db = new Model('users');
            $user_info = $db->find(['OPENID' => $_SESSION['OPENID']]);
            $uid = $user_info['uid'];
            $db_auth = new Model('authority');
            $auth_info = $db_auth->find(['uid=:uid', ':uid' => $user_info['uid']]);
            if ($auth_info['auth'] != 0 && $auth_info['forever'] != 1) {
                $until = strtotime($auth_info['until']);
                $currentTime = time();
                if ($currentTime >= $until) {
                    $db_auth->update(['uid=:uid', ':uid' => $uid], ['auth' => '0', 'forever' => '1']);
                    $auth_info = $db_auth->find(['uid=:uid', ':uid' => $user_info['uid']]);
                }
            }

            if ($auth_info['auth'] == '2') {
                $this->is_admin = true;
                $this->is_judge = true;
            } elseif ($auth_info['auth'] == '1') {
                $this->is_judge = true;
            }
            $this->display_name = empty($user_info['real_name']) ? $user_info['name'] : $user_info['real_name'];
            $this->OPENID = $user_info['OPENID'];
        }
    }

    public function jump($url, $delay = 0)
    {
        echo "<html><head><meta http-equiv='refresh' content='{$delay};url={$url}'></head><body></body></html>";
        exit;
    }
}
