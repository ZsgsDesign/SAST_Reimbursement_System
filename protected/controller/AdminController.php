<?php

class AdminController extends BaseController
{
    public function actionIndex()
    {
        $this->action = arg('action', 'umanage');
        if ($this->action == 'umanage') {
            $this->page = arg('p', 1);
            $db_user = new Model('users');
            $result = $db_user->findAll(null, null, '*', [$this->page, 20, 7]);
            $db_auth = new Model('authority');
            $pager = $db_user->page;
            $this->max_page = $pager['total_page'];
            $this->count = $pager['total_count'];
            $this->first_page = $pager['first_page'];
            $this->last_page = $pager['last_page'];
            $this->prev_page = $pager['prev_page'];
            $this->next_page = $pager['next_page'];
            $this->user_list = $result;
            foreach ($this->user_list as &$value) {
                $auth = $db_auth->find(['uid=:uid', ':uid' => $value['uid']])['auth'];
                $value['real_name'] = empty($value['real_name']) ? '未设置' : $value['real_name'];
                $value['auth'] = ($auth == 0) ? '普通用户' : (($auth == 1) ? '审批员' : (($auth == 2) ? '管理员' : '错误'));
            }
            $this->page_list = $pager['all_pages'];
        } elseif ($this->action == 'usearch') {
            $key = arg('search_key');
            $db_user = new Model('users');
            $db_auth = new Model('authority');

            $this->user_list = array_merge($db_user->findAll(['SID=:SID', ':SID' => $key]),
                                            $db_user->findAll(['name=:name', ':name' => $key]),
                                            $db_user->findAll(['real_name=:real_name', ':real_name' => $key]));
            $temp = array();  //一个数组，用于存放学号 用于二维数组去重
            foreach ($this->user_list as $key => &$value) {
                if (in_array($value['SID'], $temp)) {
                    unset($this->user_list[$key]);
                    continue;
                }
                $auth = $db_auth->find(['uid=:uid', ':uid' => $value['uid']])['auth'];
                $value['real_name'] = empty($value['real_name']) ? '未设置' : $value['real_name'];
                $value['auth'] = ($auth == 0) ? '普通用户' : (($auth == 1) ? '审批员' : (($auth == 2) ? '管理员' : '错误'));
                array_push($temp, $value['SID']);
            }
            $this->count = count($this->user_list);
        }
    }

    public function actionStat()
    {
        //总用户统计相关的功能
        //TODO...
    }

    public function actionUserManage()
    {
        if (!$this->islogin) {
            $this->jump('/account');
        }

        if (!$this->is_admin) {
            return $this->err_info_all = '权限不足！';
        }

        $this->uid = arg('uid');
        if ($this->uid == null) {
            $this->jump('/admin');
        }

        $db_user = new Model('users');
        $db_auth = new Model('authority');
        $db_department = new Model('department');
        $action = arg('action');

        if ($action == 'change_auth') {
            $auth = arg('auth');
            $until = arg('until', null);
            if ($auth != 0 && $auth != 1 && $auth != 2) {
                return $this->change_auth_err_info = '传入参数异常,请不要乱来';
            }
            if ($until === null) {
                $forever = 1;
                $until = '';
            } else {
                $forever = 0;
                $pattern = "/\d{4}(-\d{2}){2}/";
                if (!preg_match($pattern, $until)) {
                    return $this->change_auth_err_info = '传入参数异常,请不要乱来';
                }
            }

            $db_auth->update(['uid=:uid', ':uid' => $this->uid], [
                'auth' => $auth,
                'forever' => $forever,
                'until' => $until,
            ]);
            $this->change_auth_info = '权限更改成功';
        }

        $user_info = $db_user->find(['uid=:uid', ':uid' => $this->uid]);
        $this->SID = $user_info['SID'];
        $this->name = $user_info['name'];
        $this->real_name = empty($user_info['real_name']) ? '未设置' : $user_info['real_name'];
        $department = $user_info['department'];
        $d = $db_department->find(['did=:did', ':did' => $department]);
        if (!empty($d)) {
            $this->department_name = $d['name'];
        }
        $auth = $db_auth->find(['uid=:uid', ':uid' => $user_info['uid']]);
        $this->authority = $auth['auth'];
        if ($auth['forever'] != 1) {
            $this->authority_until = $auth['until'];
        }

        $this->rtime = $user_info['rtime'];
        $this->ip = $user_info['ip'];
        if (!empty($user_info['portrait'])) {
            $this->portrait = $user_info['portrait'];
        }
    }

    //We can do more.
}
