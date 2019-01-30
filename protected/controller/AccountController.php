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
        //判断是不是已经登陆了，是的话跳转到个人资料
        if ($this->islogin) {
            $this->jump('/account/profile');
        }
        if (arg('cpw')) {
            $this->cpw = 1;
            $this->doing = 0;
        }
        $action = arg('action');
        //判断行为 登陆还是注册呢 都不是就不用处理
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
            $db_authority = new Model('authority');
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
                'ip' => $reg_ip,
            ];
            $uid = $db->create($user);
            $authority = [
                'uid' => $uid,
                'auth' => 0,
                'forever' => 1,
                'until' => '',
            ];
            $db_authority->create($authority);
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
        if (!$this->islogin) {
            $this->jump('/account');
        }
        $db_user = new Model('users');
        $db_auth = new Model('authority');
        $user_info = $db_user->find(['OPENID=:OPENID', ':OPENID' => $this->OPENID]);
        $auth_info = $db_auth->find(['uid=:uid', ':uid' => $user_info['uid']]);
        $this->SID = $user_info['SID'];
        $this->name = $user_info['name'];
        $this->real_name = empty($user_info['real_name']) ? '未设置' : $user_info['real_name'];
        if (empty($user_info['department'])) {
            $this->department = $user_info['department'];
        }
        $this->authority = $auth_info['auth'];
        if ($auth_info['forever'] == 0) {
            $this->authority_until = $auth_info['until'];
        }
    }

    public function actionEditProfile()
    {
        $action = arg('action');
        if ($action == 'change_password') {
            $old_pw = arg('old-password');
            $new_pw = arg('new-password');
            $confirm_pw = arg('confirm-password');
            if (empty($old_pw) || empty($new_pw) || empty($confirm_pw)) {
                return $this->cpw_err = '有空参数!';
            }
            if ($new_pw !== $confirm_pw) {
                return $this->cpw_err = '两次密码不一样!';
            }

            $db = new Model('users');
            $result = $db->find(['OPENID=:OPENID', ':OPENID' => $this->OPENID]);
            $old_pw = $old_pw.'SASTSAST+'.(string) date_create_from_format('Y-m-d H:i:s', $result['rtime'])->getTimestamp().'s';
            $OPENID = sha1(strtolower($result['email']).'@SAST+1s'.md5($old_pw));

            $result_check = $db->find(array('OPENID=:OPENID', ':OPENID' => $OPENID));
            if (empty($result_check)) {
                return $this->cpw_err = '密码错误!';
            } else {
                $new_pw = $new_pw.'SASTSAST+'.(string) date_create_from_format('Y-m-d H:i:s', $result['rtime'])->getTimestamp().'s';
                $OPENID = sha1(strtolower($result['email']).'@SAST+1s'.md5($new_pw));
                $db->update(['OPENID=:OPENID', ':OPENID' => $this->OPENID], [
                    'password' => md5($new_pw),
                    'OPENID' => $OPENID,
                ]);
                session_destroy();
                $this->jump('/account?cpw=1');
            }
        } elseif ($action == 'upload_portrait') {
        } elseif ($action == 'edit_profile') {
            $name = arg('name');
            $real_name = arg('real_name');
        }
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
