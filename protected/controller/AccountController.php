<?php

class AccountController extends BaseController
{
    public function error($err_code, $doing)
    {
        $err_list = [
            '8000' => '八千!',
            '8001' => '邮箱或密码不能为空',
            '8002' => '密码长度为6到20位',
            '8003' => '请确认学号正确',
            '8004' => '暂时只允许使用学校提供的邮箱注册',
            '8005' => '该邮箱已注册',
            '8006' => '邮箱格式不正确',
            '8007' => '未知操作！',
            '8008' => '用户名或密码不正确',
        ];
        $this->err_info = array_key_exists($err_code, $err_list) ? $err_list[$err_code] : '????';
        $this->doing = $doing;

        return;
    }

    //actionIndex方法处理了登陆注册相关的内容
    public function actionIndex()
    {
        $this->doing = 1;
        if ($this->islogin) {
            $this->jump('/account/profile');
        }
        $action = arg('action');
        if ($action == 'register') {
            $email = arg('email');
            $password = arg('password');
            if (empty($email) || empty($password)) {
                return $this->error('8001', 1);
            }

            $pattern = "/^(\w){6,20}$/";
            if (!preg_match($pattern, $password)) {
                return $this->error('8002', 1);
            }

            if (filter_var($email, FILTER_VALIDATE_EMAIL) == false) {
                return $this->error('8006', 1);
            }

            $SID = explode('@', $email)[0];
            $email_domain = explode('@', $email)[1];
            $pattern = "/^[A-Za-z][\d]{8}$/";
            if (!preg_match($pattern, $SID)) {
                return $this->error('8003', 1);
            }
            if (strtolower($email_domain) != 'njupt.edu.cn') {
                return $this->error('8004', 1);
            }

            $db = new Model('users');
            $result = $db->find(['email=:email', ':email' => $email]);
            if (!empty($result)) {
                return $this->error('8005', 1);
            }

            $reg_ip = getIP();
            $reg_time = date('Y-m-d H:i:s');
            $password = $password.'SASTSAST+'.(string) time().'s';
            $OPENID = sha1(strtolower($email).'@SAST+1s'.md5($password));
            $user = [
                'name' => $SID,
                'real_name' => '',
                'email' => $email,
                'password' => md5($password),
                'OPENID' => $OPENID,
                'SID' => $SID,
                'rtime' => $reg_time,
                'reg_ip' => $reg_ip,
                'last_login_ip' => '',
                'p_level' => 0,
                'forever' => 0,
                'until' => $reg_time,
            ];
            $db->create($user);
            $_SESSION['OPENID'] = $OPENID;
            $this->jump($this->ATSAST_DOMIAN.'/');
        } elseif ($action == 'login') {
            $email = arg('email');
            $password = arg('password');

            if (empty($password) || empty($email)) {
                return $this->error('8001', 0);
            }

            $db = new Model('users');
            $result = $db->find(['email=:email', ':email' => $email]);
            if (empty($result)) {
                return $this->error('8008', 0);
            }
            $password = $password.'SASTSAST+'.(string) date_create_from_format('Y-m-d H:i:s', $result['rtime'])->getTimestamp().'s';
            $OPENID = sha1(strtolower($email).'@SAST+1s'.md5($password));

            $result = $db->find(array('OPENID=:OPENID', ':OPENID' => $OPENID));
            if (empty($result)) {
                return $this->error('8008', 0);
            } else {
                $_SESSION['OPENID'] = $OPENID;
                $this->jump($this->ATSAST_DOMAIN.'/');
            }
        }
    }

    public function actionProfile()
    {
        //用户资料界面
        //TODO...
    }

    public function actionEditProfile()
    {
        //用户资料编辑 呐...
        //TODO...
    }

    //退出登录
    public function actionLogout()
    {
        session_destroy();
        $this->jump('/');
    }

    //We can do more.
}
