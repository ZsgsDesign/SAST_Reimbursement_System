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
        $this->doing = 0;
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
        if (!empty($user_info['portrait'])) {
            $this->portrait = $user_info['portrait'];
        }
        $this->real_name = empty($user_info['real_name']) ? '未设置' : $user_info['real_name'];
        if (!empty($user_info['department'])) {
            $db_department = new Model('department');
            $department = $user_info['department'];
            $dptm = $db_department->find(['did=:did', ':did' => $department]);
            if (!empty($dptm)) {
                $this->department_name = $dptm['name'];
            }
        }
        $this->authority = $auth_info['auth'];
        if ($auth_info['forever'] == 0) {
            $this->authority_until = $auth_info['until'];
        }
    }

    //用户资料编辑
    public function actionEditProfile()
    {
        $db_user = new Model('users');
        $db_department = new Model('department');
        $user_info = $db_user->find(['OPENID=:OPENID', ':OPENID' => $this->OPENID]);
        $this->name = $user_info['name'];
        if ($user_info['department'] != -1) {
            $dptm = $db_department->find(['did=:did', ':did' => $user_info['department']]);
            if (!empty($dptm)) {
                $this->department_name = $dptm['name'];
            }
        }
        if (!empty($user_info['real_name'])) {
            $this->real_name = $user_info['real_name'];
        }
        if (!empty($user_info['portrait'])) {
            $this->portrait = $user_info['portrait'];
        }
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

            $result = $db_user->find(['OPENID=:OPENID', ':OPENID' => $this->OPENID]);
            $old_pw = $old_pw.'SASTSAST+'.(string) date_create_from_format('Y-m-d H:i:s', $result['rtime'])->getTimestamp().'s';
            $OPENID = sha1(strtolower($result['email']).'@SAST+1s'.md5($old_pw));

            $result_check = $db_user->find(array('OPENID=:OPENID', ':OPENID' => $OPENID));
            if (empty($result_check)) {
                return $this->cpw_err = '密码错误!';
            } else {
                $new_pw = $new_pw.'SASTSAST+'.(string) date_create_from_format('Y-m-d H:i:s', $result['rtime'])->getTimestamp().'s';
                $OPENID = sha1(strtolower($result['email']).'@SAST+1s'.md5($new_pw));
                $db_user->update(['OPENID=:OPENID', ':OPENID' => $this->OPENID], [
                    'password' => md5($new_pw),
                    'OPENID' => $OPENID,
                ]);
                session_destroy();
                $this->jump('/account?cpw=1');
            }
        } elseif ($action == 'upload_portrait') {
            if (empty($_FILES['portrait']['name'])) {
                return $this->err_info = '没有上传文件,请不要皮这个系统';
            }

            $filename = $_FILES['portrait']['name'];
            $extension = explode('.', $filename);
            $extension = $extension[count($extension) - 1];
            if ($extension != 'jpg') {
                return $this->err_info = '上传头像只支持jpg文件';
            }
            $hash = md5_file($_FILES['portrait']['tmp_name']);
            $db_user->update(['OPENID=:OPENID', ':OPENID' => $this->OPENID], [
                'portrait' => $hash,
            ]);
            move_uploaded_file($_FILES['portrait']['tmp_name'], APP_DIR.'/file/img/'.$hash.'.jpg');
            $this->success_info = '头像已修改成功';
            $this->portrait = $hash;
        } elseif ($action == 'edit_profile') {
            $update_row = array();
            $name = arg('name');
            $real_name = arg('real_name');
            $department = arg('department');
            if (empty($name)) {
                return $this->err_info = '用户名为必填项!';
            }

            if ($name != $this->name) {
                $update_row['name'] = $name;
            }
            if ($real_name != $this->real_name) {
                $update_row['real_name'] = $real_name;
            }
            if (!empty($department) && $department != $this->department_name) {
                $result = $db_department->find(['name=:name', ':name' => $department]);
                if (!empty($result)) {
                    $update_row['department'] = $result['did'];
                } else {
                    $this->err_info = '没有找到该部门，其他信息将会继续修改';
                }
            }
            if (!empty($update_row)) {
                $db_user->update(['uid=:uid', ':uid' => $user_info['uid']], $update_row);
            } else {
                return $this->err_info = '没有进行任何修改';
            }
            $this->name = $name;
            $this->real_name = $real_name;
            $this->department_name = $department;
            $this->success_info = '信息修改成功';
        }
    }

    //退出登录
    public function actionLogout()
    {
        session_destroy();
        $this->jump('/');
    }

    public function actionPassReset()
    {
        if ($this->islogin) {
            $this->jump('/');
        }

        $ret = arg('ret');
        $uid = arg('uid');

        if (empty($ret) || empty($uid)) {
            $this->step = 0;
            $email = arg('email');
            if (!empty($email)) {
                $db_user = new Model('users');
                $user_info = $db_user->find(['email=:email', ':email' => $email]);
                if (!empty($user_info)) {
                    $uid = $user_info['uid'];
                    $OPENID = $user_info['OPENID'];
                    if (!isset($_SESSION['last_send']) || time() - $_SESSION['last_send'] >= 300) {
                        $_SESSION['last_send'] = time();
                        if (sendRetrievePasswordEmail($email, $uid, $OPENID, $this->ATSAST_DOMAIN)) {
                            return $this->success_info = '找回密码的邮件已经发往指定的邮箱，请查看';
                        } else {
                            return $this->err_info = '邮件发送失败，请联系管理员';
                        }
                    } else {
                        return $this->err_info = '邮件发送过于频繁！';
                    }
                } else {
                    return $this->err_info = '用户不存在';
                }
            }
        } else {
            $db_user = new Model('users');
            $user_info = $db_user->find(['uid=:uid', ':uid' => $uid]);
            if (empty($user_info)) {
                return $this->err_info = '不存在的用户';
            }

            $OPENID = $user_info['OPENID'];

            if (sha1($OPENID.$uid) !== $ret) {
                return $this->err_info = '请使用收到的邮件内的链接访问!';
            }

            $new_pass = arg('new_password');
            $confirm_pass = arg('confirm_password');
            if (empty($new_pass) || empty($confirm_pass)) {
                $this->uid = $uid;
                $this->step = 1;
                $this->ret = $ret;
            } else {
                if ($new_pass == $confirm_pass) {
                    $new_pass = $new_pass.'SASTSAST+'.(string) date_create_from_format('Y-m-d H:i:s', $user_info['rtime'])->getTimestamp().'s';
                    $OPENID = sha1(strtolower($user_info['email']).'@SAST+1s'.md5($new_pass));
                    $db_user->update(['OPENID=:OPENID', ':OPENID' => $user_info['OPENID']], [
                            'password' => md5($new_pass),
                            'OPENID' => $OPENID,
                        ]);
                    session_destroy();
                    $this->jump('/account?cpw=1');
                } else {
                    $this->uid = $uid;
                    $this->step = 1;
                    $this->ret = $ret;
                    $this->err_info = '两次密码输入不一致，请重新输入';
                }
            }
        }
    }

    //We can do more.
}
