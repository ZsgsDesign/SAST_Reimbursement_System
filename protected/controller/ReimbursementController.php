<?php

class ReimbursementController extends BaseController
{
    public function actionInitiate()
    {
        if (!$this->islogin) {
            $this->jump('/account');
        }
        if (arg('type') !== null) {
            return $this->invoice_type = arg('type');
        }

        $action = arg('action');
        if ($action == 'initiate') {
            $name = arg('name');
            $remarks = arg('remarks');
            $department = arg('department');
            $money = arg('money');
            $invoice_type = arg('invoice_type');

            if (empty($name) || empty($remarks) || empty($department) || empty($money)) {
                return $this->err_info = '填写的信息不足，请再次填写并提交';
            }

            //检查金额输入
            $pattern = '/\d{1,4}/';
            if (preg_match($pattern, $money)) {
                if ($money >= 1000) {
                    return $this->err_info = '交易额度较大，拒绝受理';
                } elseif ($money >= 500) {
                    if (empty($_FILES['transaction_voucher']['name']) || empty($_FILES['declaration']['name'])) {
                        return $this->err_info = '该交易额度必须提交交易凭证以及申报单,请仔细检查之后再一次提交';
                    }
                } elseif ($money >= 200) {
                    if (empty($_FILES['transaction_voucher']['name'])) {
                        return $this->err_info = '该交易额度必须提交交易凭证,请仔细检查之后再一次提交';
                    }
                } elseif ($money <= 0) {
                    return $this->err_info = '错误的金额！';
                }
            } else {
                return $this->err_info = '非法的金额！';
            }
            //检查发票有没有好好上传
            if ($invoice_type === '0') {
                if (empty($_FILES['invoice'])) {
                    return $this->err_info = '电子发票必须上传发票文件';
                }
            } elseif ($invoice_type !== '1') {
                if (empty($_FILES['invoice'])) {
                    return $this->err_info = '非法提交,请不要皮这个系统';
                }
            } elseif (isset($_FILES['invoice'])) {
                return $this->err_info = '有非法的文件提交,请不要皮这个系统';
            }

            //检查有没有对应的部门
            $db_department = new Model('department');
            $result = $db_department->find(['name=:name', ':name' => $department]);
            if (empty($result)) {
                return $this->err_info = '该部门不存在，请仔细检查，再次填写并提交';
            }

            //查找发起人
            $db_user = new Model('users');
            $uid = $db_user->find(['OPENID=:OPENID', ':OPENID' => $this->OPENID])['uid'];

            //唔。。申报时间
            $time = date('Y-m-d H:i:s');

            $RBM = [
                'name' => $name,
                'remarks' => $remarks,
                'department' => $result['did'],
                'status' => '0',
                'money' => $money,
                'time' => $time,
                'uid' => $uid,
            ];

            //处理上传的发票文件
            if (!empty($_FILES['invoice']['name'])) {
                $filename = $_FILES['invoice']['name'];
                $extension = explode('.', $filename);
                $extension = strtolower($extension[count($extension) - 1]);
                if ($extension != 'pdf') {
                    return $this->err_info = '发票文件格式错误，请仔细检查，再次提交';
                }

                $hash = md5_file($_FILES['invoice']['tmp_name']);
                $RBM['invoice'] = $hash;
                move_uploaded_file($_FILES['invoice']['tmp_name'], APP_DIR.'/file/invoice/'.$hash.'.pdf');
            }

            //处理上传的交易凭证
            if (!empty($_FILES['transaction_voucher']['name'])) {
                $filename = $_FILES['transaction_voucher']['name'];
                $extension = explode('.', $filename);
                $extension = strtolower($extension[count($extension) - 1]);
                $allow_ext = ['jpg'];
                if (!in_array($extension, $allow_ext)) {
                    return $this->err_info = '不支持的交易凭证格式，只允许jpg上传，请重试';
                }

                $hash = md5_file($_FILES['transaction_voucher']['tmp_name']);
                $RBM['transaction_voucher'] = $hash;
                move_uploaded_file($_FILES['transaction_voucher']['tmp_name'], APP_DIR.'/file/transaction_voucher/'.$hash.'.'.$extension);
            }

            //处理上传的申报单
            if (!empty($_FILES['declaration']['name'])) {
                $filename = $_FILES['declaration']['name'];
                $extension = explode('.', $filename);
                $extension = strtolower($extension[count($extension) - 1]);
                $allow_ext = ['docx'];
                if (!in_array($extension, $allow_ext)) {
                    return $this->err_info = '不支持的交易凭证格式，只允许docx上传，请重试';
                }

                $hash = md5_file($_FILES['declaration']['tmp_name']);
                $RBM['declaration'] = $hash;
                move_uploaded_file($_FILES['declaration']['tmp_name'], APP_DIR.'/file/declaration/'.$hash.'.'.$extension);
            }
            //存入数据库
            $db_rbm = new Model('reimbursements');
            $rid = $db_rbm->create($RBM);

            $this->jump('/reimbursement/view/'.$rid);
        }

        //处理用户发起报销
        //TODO...
    }

    public function actionApproval()
    {
        if (!$this->islogin) {
            $this->jump('/account');
        }
        if (!$this->is_admin && !$this->is_judge) {
            $this->jump('/');
        }
        $db_user = new Model('users');
        $db_reimbursements = new Model('reimbursements');
        $db_change_log = new Model('change_log');
        $this->wait_approval_list = $db_reimbursements->findAll(['status=0 AND status!=3']);
        $action = arg('action');
        if ($action == 'approval') {
            $rid = arg('rid');
            $remarks = arg('remarks');
            $result = arg('result');
            if (empty($remarks) || $result === null || empty($rid)) {
                return $this->err_info = '参数缺失';
            }
            if ($result == 1) {
                $result = 1;
            } else {
                $result = 2;
            }

            $rbm = $db_reimbursements->find(['rid=:rid', ':rid' => $rid]);
            if (empty($rbm)) {
                return $this->err_info = '找不到该报销项目';
            }

            if ($rbm['status'] != 0) {
                return $this->err_info = '该项目的状态不正确，只可以处理待审核的项目';
            }

            if ($rbm['status'] == 3) {
                return $this->err_info = '该项目的状态不正确，被挂起的项目不允许进行编辑以及审批';
            }

            $user_info = $db_user->find(['OPENID=:OPENID', ':OPENID' => $this->OPENID]);
            $uid = $user_info['uid'];

            $update_row_rbm = [
                'status' => $result,
            ];
            $row_change_log = [
                'uid' => $uid,
                'rid' => $rid,
                'before_status' => $rbm['status'],
                'change_type' => 0,
                'remarks' => $remarks,
                'time' => date('Y-m-d H:i:s'),
            ];
            $db_reimbursements->update(['rid=:rid', ':rid' => $rid], $update_row_rbm);
            $db_change_log->create($row_change_log);
            $this->success_info = '审批提交成功,2秒后跳转到该项目';
            $this->rid = $rid;
        }
    }

    public function actionView()
    {
        $this->needDiv = true;

        if (!$this->islogin) {
            $this->jump('/account');
        }

        $db_user = new Model('users');
        $db_department = new Model('department');
        $db_reimbursements = new Model('reimbursements');
        $db_auth = new Model('authority');
        $user_info = $db_user->find(['OPENID=:OPENID', ':OPENID' => $this->OPENID]);
        $uid = $user_info['uid'];

        $rid = arg('rid');
        if ($rid == null) {
            $this->display_type = 'list';
            $page = arg('p', 1);
            $search_key = arg('search_key');

            $auth_info = $db_auth->find(['uid=:uid', ':uid' => $uid]);

            if (empty($search_key)) {
                if ($auth_info['auth'] != 0) {
                    $reim_list = $db_reimbursements->findAll(null, 'rid desc', '*', [$page, 20, 7]);
                } else {
                    $reim_list = $db_reimbursements->findAll(['uid=:uid', ':uid' => $uid], 'rid desc', '*', [$page, 20, 7]);
                }
            } else {
                //看看有没有相关的部门
                $search_condition = '';
                $search_condition_parm = array();
                $dptm_result = $db_department->find(['name=:name', ':name' => $search_key]);
                if (!empty($dptm_result)) {
                    $did_search = $dptm_result['did'];
                    $search_condition .= '(department=:did';
                    $search_condition_parm[':did'] = $did_search;
                }
                //找相关的用户
                $user_result = $db_user->find(['name=:name OR SID=:SID OR real_name=:real_name', ':name' => $search_key, ':SID' => $search_key, ':real_name' => $search_key]);
                if (!empty($user_result)) {
                    $uid_search = $user_result['uid'];
                    //判断前面有没有相关部门
                    if (!empty($search_condition)) {
                        $search_condition .= ' OR uid=:uid_search';
                    } else {
                        $search_condition .= '(uid=:uid_search';
                    }
                    $search_condition_parm[':uid_search'] = $uid_search;
                }

                //看看有没有相关的用户和部门决定语句结构
                if (!empty($search_condition)) {
                    $search_condition .= ' OR name=:name)';
                } else {
                    $search_condition .= 'name=:name';
                }
                $search_condition_parm[':name'] = $search_key;

                if ($auth_info['auth'] != 0) {
                    array_push($search_condition_parm, $search_condition);
                    $reim_list = $db_reimbursements->findAll($search_condition_parm, 'rid desc', '*', [$page, 20, 7]);
                } else {
                    $search_condition .= 'AND uid=:uid';
                    $search_condition_parm[':uid'] = $uid;
                    array_push($search_condition_parm, $search_condition);
                    $reim_list = $db_reimbursements->findAll(['uid=:uid', ':uid' => $uid], 'rid desc', '*', [$page, 20, 7]);
                }
            }

            if (!empty($db_reimbursements->page)) {
                $this->pager = $db_reimbursements->page;
            }

            $this->count = count($reim_list);

            $dptm_info = $db_department->findAll();
            $temp = array();
            $status_list = [
                '0' => '待审批',
                '1' => '已通过',
                '2' => '被驳回',
                '3' => '被挂起',
            ];
            if (!empty($reim_list) && is_array($reim_list)) {
                foreach ($reim_list as &$value) {
                    foreach ($dptm_info as $v) {
                        if ($value['department'] == $v['did']) {
                            $value['department'] = $v['name'];
                            break;
                        }
                    }
                    if (is_int($value['department'])) {
                        $value['department'] = '未知部门';
                    }
                    if (!array_key_exists($value['uid'], $temp)) {
                        $user_info = $db_user->find(['uid=:uid', ':uid' => $value['uid']]);
                        $value['u_name'] = !empty($user_info['real_name']) ? $user_info['real_name'] : $user_info['SID'];
                        $temp['uid'] = $value['u_name'];
                    } else {
                        $value['u_name'] = $temp['uid'];
                    }
                    $value['status'] = $status_list[$value['status']];
                }
            }

            $this->list = $reim_list;
        } else {
            $this->display_type = 'single';

            //这个action也用于审批的时候显示项目的信息，这个变量($isApproval)用于控制需不需要外层div和应用layout
            $isApproval = arg('approval');
            if ($isApproval) {
                $this->needDiv = false;
                $this->layout = null;
            }

            $reimDetails = $db_reimbursements->find(['rid=:rid', ':rid' => $rid]);

            if (empty($reimDetails)) {
                $this->jump('/reimbursement/view');
            }

            $dptm_info = $db_department->find(['did=:did', ':did' => $reimDetails['department']]);
            $reimDetails['department'] = empty($dptm_info) ? '未知部门' : $dptm_info['name'];
            $user_info = $db_user->find(['uid=:uid', ':uid' => $reimDetails['uid']]);
            $reimDetails['u_name'] = !empty($user_info['real_name']) ? $user_info['real_name'] : $user_info['SID'];
            $status_list = [
                '0' => '待审批',
                '1' => '已通过',
                '2' => '被驳回',
                '3' => '被挂起',
            ];
            $reimDetails['status'] = $status_list[$reimDetails['status']];

            $db_change_log = new Model('change_log');
            $this->change_log = $db_change_log->findAll(['rid=:rid', ':rid' => $rid]);
            $temp = array();
            foreach ($this->change_log as &$value) {
                if (array_key_exists($value['uid'], $temp)) {
                    $value['operator'] = $temp[$value['uid']];
                } else {
                    $user_info = $db_user->find(['uid=:uid', ':uid' => $value['uid']]);
                    $value['operator'] = !empty($user_info['real_name']) ? $user_info['real_name'] : $user_info['SID'];
                    $temp[$value['uid']] = $value['operator'];
                }

                $value['before_status'] = $status_list[$value['before_status']];
                $change_type_list = [
                    '0' => '审批',
                    '1' => '修改',
                    '2' => '管理员编辑',
                    '3' => '挂起',
                    '4' => '解除挂起',
                ];
                $value['change_type'] = array_key_exists($value['change_type'], $change_type_list) ? $change_type_list[$value['change_type']] : '-';
            }

            if (($user_info['OPENID'] == $this->OPENID && $reimDetails['status'] != 1) || $this->is_admin) {
                $this->displayEdit = true;
            }

            $this->reim = $reimDetails;
        }
    }

    public function actionEdit()
    {
        if (!$this->islogin) {
            $this->jump('/account');
        }

        $db_user = new Model('users');
        $db_department = new Model('department');
        $db_reimbursements = new Model('reimbursements');
        $db_change_log = new Model('change_log');

        $action = arg('action');

        $this->rid = arg('rid');
        if ($this->rid === null) {
            $this->jump('/reimbursement/view');
        }

        $rbm = $db_reimbursements->find(['rid=:rid', ':rid' => $this->rid]);
        if (empty($rbm)) {
            $this->jump('/reimbursement/view');
        }
        $user_info = $db_user->find(['uid=:uid', ':uid' => $rbm['uid']]);
        if (!$this->is_admin) {
            if ($user_info['OPENID'] != $this->OPENID) {
                return $this->error = '你没有权限编辑该项目';
            }
        }
        $this->invoice_type = empty($rbm['invoice']) ? 1 : 0;

        if ($rbm['status'] == 3) {
            if ($action == 'refresh') {
                if (!$this->is_admin) {
                    return $this->error = '权限不足';
                }

                if ($rbm['status'] != 3) {
                    return $this->error = '该项目状态不正确，只能对被挂起的项目执行此操作';
                }

                $update_row_rbm = [
                    'status' => 0,
                ];
                $row_change_log = [
                    'uid' => $user_info['uid'],
                    'rid' => $this->rid,
                    'before_status' => $rbm['status'],
                    'change_type' => 4,
                    'remarks' => '-',
                    'time' => date('Y-m-d H:i:s'),
                ];
                $db_reimbursements->update(['rid=:rid', ':rid' => $this->rid], $update_row_rbm);
                $db_change_log->create($row_change_log);

                return $this->error = '该项目的状态已恢复，可以被审核或编辑';
            }

            if ($this->is_admin) {
                $this->allow_refresh = 1;
            }

            return $this->error = '该交易已被挂起！';
        }

        if ($rbm['status'] != 0 && $rbm['status'] != 2) {
            if (!$this->is_admin) {
                return $this->error = '只允许编辑 待审批 与 被驳回 的项目';
            }
        }

        $dptm_info = $db_department->find(['did=:did', ':did' => $rbm['department']]);
        $rbm['department'] = empty($dptm_info) ? '未知部门' : $dptm_info['name'];
        $this->reim = $rbm;

        if ($action == 'edit') {
            $name = arg('name');
            $remarks = arg('remarks');
            $remarks_edit = arg('remarks_edit');
            $department = arg('department');
            $money = arg('money');

            if (empty($name) || empty($remarks) || empty($remarks_edit) || empty($department) || empty($money)) {
                return $this->err_info = '填写的信息不足，请再次填写并提交';
            }

            $update_row = [
                'name' => $name,
                'remarks' => $remarks,
            ];
            //检查金额输入
            $pattern = '/\d{1,4}/';
            if (preg_match($pattern, $money)) {
                if ($money >= 1000) {
                    return $this->err_info = '交易额度较大，拒绝受理';
                } elseif ($money <= 0) {
                    return $this->err_info = '错误的金额！';
                }
            } else {
                return $this->err_info = '非法的金额！';
            }
            $update_row['money'] = $money;

            //检查部门是否存在
            if ($department != $rbm['department']) {
                $dptm = $db_department->find(['name=:name', ':name' => $department]);
                if (empty($dptm)) {
                    return $this->err_info = '该部门不存在，请仔细检查，再次填写并提交';
                } else {
                    $update_row = $dptm['did'];
                }
            }

            //处理上传的发票文件
            if ($this->invoice_type == 1 && !empty($_FILES['invoice']['name'])) {
                $filename = $_FILES['invoice']['name'];
                $extension = explode('.', $filename);
                $extension = strtolower($extension[count($extension) - 1]);
                if ($extension != 'pdf') {
                    return $this->err_info = '发票文件格式错误，只允许pdf格式上传，请仔细检查';
                }

                $hash = md5_file($_FILES['invoice']['tmp_name']);
                $update_row['invoice'] = $hash;
                move_uploaded_file($_FILES['invoice']['tmp_name'], APP_DIR.'/file/invoice/'.$hash.'.pdf');
            }

            //处理上传的交易凭证
            if (!empty($_FILES['transaction_voucher']['name'])) {
                $filename = $_FILES['transaction_voucher']['name'];
                $extension = explode('.', $filename);
                $extension = strtolower($extension[count($extension) - 1]);
                $allow_ext = ['jpg'];
                if (!in_array($extension, $allow_ext)) {
                    return $this->err_info = '不支持的交易凭证格式，只允许jpg上传';
                }

                $hash = md5_file($_FILES['transaction_voucher']['tmp_name']);
                $update_row['transaction_voucher'] = $hash;
                move_uploaded_file($_FILES['transaction_voucher']['tmp_name'], APP_DIR.'/file/transaction_voucher/'.$hash.'.'.$extension);
            }

            //处理上传的申报单
            if (!empty($_FILES['declaration']['name'])) {
                $filename = $_FILES['declaration']['name'];
                $extension = explode('.', $filename);
                $extension = strtolower($extension[count($extension) - 1]);
                $allow_ext = ['docx'];
                if (!in_array($extension, $allow_ext)) {
                    return $this->err_info = '不支持的交易凭证格式，只允许docx上传';
                }

                $hash = md5_file($_FILES['declaration']['tmp_name']);
                $update_row['declaration'] = $hash;
                move_uploaded_file($_FILES['declaration']['tmp_name'], APP_DIR.'/file/declaration/'.$hash.'.'.$extension);
            }

            //判断编辑的类型
            $change_type = 2;
            if ($rbm['status'] == 0 || $rbm['status'] == 2) {
                if ($user_info['OPENID'] == $this->OPENID) {
                    $update_row['status'] = 0;
                    $change_type = 1;
                }
            }

            $row_change_log = [
                'uid' => $user_info['uid'],
                'rid' => $this->rid,
                'before_status' => $rbm['status'],
                'change_type' => $change_type,
                'remarks' => $remarks_edit,
                'time' => date('Y-m-d H:i:s'),
            ];

            $db_change_log->create($row_change_log);
            $db_reimbursements->update(['rid=:rid', ':rid' => $this->rid], $update_row);
            $this->success_info = '该项目资料编辑成功,2秒后跳转';
        } elseif ($action == 'freeze') {
            $remarks = arg('remarks');
            if (empty($remarks)) {
                return $this->err_info = '缺少参数';
            }

            if (!$this->is_admin) {
                return $this->err_info = '权限不足';
            }

            if ($rbm['status'] == 1) {
                return $this->err_info = '该项目状态不正确，不可以对已经审批通过的项目执行此操作';
            }

            $update_row_rbm = [
                'status' => 3,
            ];
            $row_change_log = [
                'uid' => $user_info['uid'],
                'rid' => $this->rid,
                'before_status' => $rbm['status'],
                'change_type' => 3,
                'remarks' => $remarks,
                'time' => date('Y-m-d H:i:s'),
            ];
            $db_reimbursements->update(['rid=:rid', ':rid' => $this->rid], $update_row_rbm);
            $db_change_log->create($row_change_log);

            $this->success_info = '该项目已经冻结，可以查看资料但是不能审批以及编辑';
        }

        //处理编辑报销详情
        //TODO...
    }

    public function actionStatisticsTotality()
    {
        //处理总体统计详情有关的东西,比如总支出什么的
        //TODO..
    }

    public function actionDepartmentStatistics()
    {
        //查看某个部门的支出报销详情
        //TODO...
    }

    //We can do more.
}
//rbm status:
//0 -> 待审核
//1 -> 已通过
//2 -> 被驳回
//3 -> 被挂起

//change_log change_type
//0 -> 审批
//1 -> 编辑
//2 -> 管理员操作
//3 -> 挂起
//4 -> 取消挂起
