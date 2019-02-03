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
                move_uploaded_file($_FILES['invoice']['tmp_name'], APP_DIR.'/protected/file/invoice/'.$hash.'.pdf');
            }

            //处理上传的交易凭证
            if (!empty($_FILES['transaction_voucher']['name'])) {
                $filename = $_FILES['transaction_voucher']['name'];
                $extension = explode('.', $filename);
                $extension = strtolower($extension[count($extension) - 1]);
                $allow_ext = ['jpg', 'png'];
                if (!in_array($extension, $allow_ext)) {
                    return $this->err_info = '不支持的交易凭证格式，只允许jpg，png上传，请重试';
                }

                $hash = md5_file($_FILES['transaction_voucher']['tmp_name']);
                $RBM['transaction_voucher'] = $hash;
                move_uploaded_file($_FILES['transaction_voucher']['tmp_name'], APP_DIR.'/protected/file/transaction_voucher/'.$hash.$extension);
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
                move_uploaded_file($_FILES['declaration']['tmp_name'], APP_DIR.'/protected/file/declaration/'.$hash.$extension);
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
        //处理审批员审批报销,这里可以做成ajax的样子吗？
        //TODO...
    }

    public function actionViewDetail()
    {
        //这个action可以改成actionView了。。。
        if (!$this->islogin) {
            $this->jump('/account');
        }

        $db_user = new Model('users');
        $user_info = $db_user->find(['OPENID=:OPENID', ':OPENID' => $this->OPENID]);
        $uid = $user_info['uid'];

        $db_reimbursements = new Model('reimbursements');
        $reimDetails = $db_reimbursements->findAll();

        //我们一般不在一些参数不确定的情况下直接findAll吧应该。。性能花销会有点大呢?
        //这里可以先获取rid 获取不到看权限 权限够再findAll 权限不够就findAll(['uid=:uid',':uid'=>$uid]);

        $rid = arg('rid');
        if ($rid == null) {
            //如果rid是空的的话 ，我们要让用户看到一个他们可以查看的所有报销记录的列表，然后让他们点进一个报销记录里面
            //这个可以看master分支的用户管理那一块,今晚要把分页的功能完善了,如果报销记录太多的话就有必要分页处理,不然一个页面显示太多的话就很难受
            //然后,如果让用户看到报销记录的列表的话只需要一些基本的信息就可以了，比如报销内容,报销人,报销时间什么什么的
            if (valid_auth($uid)) {
            } else {
                foreach ($reimDetails as $value) {
                    if ($value['uid'] != $uid) {
                        array_splice($value);
                        //这个函数的用法有问题哦,想要去掉的话可以直接unset(),每个函数的用法都可以直接百度函数名,可以看到的
                        //这里的话 foreach 写成 as $key => $value 的样字,然后unset可以直接 unset($reimDetails[$key]);
                        //然后php里面似乎也有值传递和引用传值,foreach默认的是值传递相当于形参改了不影响原来的
                        //要想引用传值的话要这么写 foreach($reimDetails as &$value)
                    }
                }
            }
            $i = 0;
            $j = 0;
            //唔...这里直接返回上面处理完的数组就可以啦
            //对了 让前端可以看到的方式是 $this->var =  xxxx;
            //直接 $var = xxxx; 前端是什么都看不到的呢

            //还有这里的i和j,php里边一般可以不这么写
            //因为键值对的数组的话，我们可以直接在下面写$value['hasInvoice'] = true;这样，前提是要引用传值,171-172行的内容就是
            //也可以百度哦 2333
            //这样就少了不必要的变量,都放在一个reimDetails这个二维数组里，每一个成员就是一条记录的报销信息
            //这样理解起来和前端处理起来也会方便很多的呢
            //虽然这么写也不是不行啦
            foreach ($reimDetails as $value) {
                if ($value['invoice'] == null) {
                    $hasInvoice[i] = 0;
                    ++$i;
                } else {
                    $hasInvoice[i] = 1;
                    ++$i;
                }
                if ($value['transaction_voucher'] == null) {
                    $hasTransactionVoucher[j] = 0;
                    ++$j;
                } else {
                    $hasTransactionVoucher[j] = 1;
                    ++$j;
                }
            }
            //↓ 这里直接empty($reimDetails)就可以啦,虽然结果是差不多的
        } elseif (!empty($reimDetails['rid'])) {
            foreach ($reimDetails as $value) {
                if ($value['rid'] != $rid) {
                    array_splice($value);
                    //唔 这里用法。。跟上面的一样
                }
            }
            if ($reimDetails['invoice'] == null) {
                $hasInvoice = false;
            } else {
                $hasInvoice = true;
            }
            if ($reimDetails['transaction_voucher'] == null) {
                $hasTransactionVoucher = false;
            } else {
                $hasTransactionVoucher = true;
            }

            if ($reimDetails == null) {
                return $this->err_info = '当前并没有报销记录哦';
            } else {
                return $this->list = array($reimDetails, $hasInvoice, $hasTransactionVoucher);
            }
        } else {
            return $this->err_info = '无相关记录';
        }
        //查看某条报销的详情
        //TODO...
    }

    public function actionViewChangelog()
    {
        //唔。。 这一部分代码应该移到actionView里面去,是我当时设计的不好 我的锅
        //你可以去看看master分支的查看报销记录,直接看finance.winter.mundb.xyz
        //前端是怎么想的直接去看界面可以看的很清楚呢 2333 然后针对该有的变量在后端往前面传值
        //或者自己看着修改,前端可以对应地改东西什么的

        //这一段写的还是比较优秀的呢,只是传值的方法不是很对劲
        //$this->var = xxxx;这样 2333 前面写了的
        if (!$this->islogin) {
            $this->jump('/account');
        }

        $db_user = new Model('users');
        $user_info = $db_user->find(['OPENID=:OPENID', ':OPENID' => $this->OPENID]);
        $currentUID = $user_info['uid'];

        if (valid_auth($currentUID) == true) {
            $rid = arg('rid');
            $db_change_log = new Model('change_log');
            $Change_Log = $db_change_log->findAll(['rid=:rid', ':rid' => $rid]);
        } else {
            $rid = arg('rid');
            $db_reimbursements = new Model('reimbursements');
            $uid = $db_reimbursements->find(['rid=:rid', ':rid' => $rid]);
            if ($currentUID == $uid) {
                $db_change_log = new Model('change_log');
                $Change_Log = $db_change_log->findAll(['rid=:rid', ':rid' => $rid]);
            } else {
                return $this->err_info = '抱歉，不是你发起的报销，无权查看';
            }
        }

        return $this->liset = $Change_Log;
        //查看某条报销的操作记录
    }

    public function actionEdit()
    {
        //处理编辑报销详情
        //TODO...
    }

    public function actionStatisticsTotality()
    {
        //处理总体统计详情有关的东西,比如总支出什么的
        //status:
        //0 -> 待审核
        //1 -> 已通过
        //2 -> 被驳回
        //3 -> 被挂起
        //TODO...
    }

    public function actionDepartmentStatistics()
    {
        //查看某个部门的支出报销详情
        //TODO...
    }

    //We can do more.
}
