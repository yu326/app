<?php
//检查用户session是否存在
function checkusersession()
{
    if(!isset($_SESSION) || $_SESSION['user']==null)
    {
        return false;
    }
    else
    {
        return true;
    }
}
/**
 *
 * 根据用户session判断是否有权限使用资源
 * @param $resourceid
 */
function checkuseruseage($resourceid)
{
    global $dsql,$arrs;

    //判断session是否存在
    if(checkusersession())
    {

        if($_SESSION["user"]->usertype==3)
        {
            //获取用户角色所能使用的资源，并根据传入的资源ID判断是否有使用此资源的权限
            //一下注释部分是通过查询角色来判断用户是否有访问某个资源的权限
            /*$sql = "select * from ".DATABASE_ROLE_RESOURCE_RELATION." where roleid in(".$_SESSION["user"]->rolied.") and roletype=".$_SESSION["user"]->uertype." and resourceid = ".$resourceid;

            $qr = $dsql->ExecQuery($sql);

            if(!qr)
            {
            }
            else
            {
            if($dsql->GetTotalRow($qr)>0)
            {
            return true;
            }
            else
            {
            $arrs["result"] = false;
            $arrs["msg"] = "您没有权限访问本资源，请与管理员联系!";
            }
            }
            */

            //此部分代码是通过判断session中的resourceid来判断用户是否有拥有某资源权限
            if(!strpos($_SESSION["user"]->resourceid,$resourceid))
            {
                $arrs["result"] = false;
                $arrs["msg"] = "您没有权限访问本资源，请与管理员联系!";

            }
            else
            {
                return true;
            }
        }
        else
        {
            return true;
        }

    }
    else
    {
        $arrs["result"] = false;
        $arrs["msg"] = "您未登录或登录超时!";
    }

    return true;

}