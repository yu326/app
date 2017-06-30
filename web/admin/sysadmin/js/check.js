var directurl; //模块地址
var isuse=0; //是否可用
var uid=0; //用户ID
var rid=0; //资源ID
function checkpure(resourceid,url,type)
{
directurl = url;
var searchnameUrl =  "../model/resource_model.php?type=checkuserresource&rid="+resourceid+"$gid="+type;
	
	ajaxCommon(searchnameUrl,checkuseage, "json");
}

function checkuseage(data)
{
	if(data!=null)
	{
		if(data.flag==1)
		{	isuse = 1;
		//addtrace(uid,rid,isuse);
			window.location.href=directurl;
		}
		else
		{isuse = 0;
		addtrace(uid,rid,isuse);
			alert("您无权限使用此模块!");
			window.location.href="../login.shtml";
			
		}
	}
}

function checkuserpure(resourceid,type)
{
		var URL=config.modelUrl+"checkuser.php?type=existsession";
			
	$.ajax({
        type: "get",
        contentType: "application/json",
        dataType: "json",
        url: URL,
        success: function (data) {
			if(!data.result)
			{
				alert(data.msg);
				window.location.href=config.sitePath+"login.shtml";
				return;

			}
		}//end sucess
		   });//end ajax
}



function checkuserusage()
{
	var URL=config.modelUrl+"checkuser.php?type=existsession";
	$.ajax({
        type: "get",
        contentType: "application/json",
        dataType: "json",
        url: URL,
        success: function (data) {
			if(!data.result){
				alert(data.msg);
				window.location.href=config.sitePath+"login.shtml";
				return;
			}
		}//end sucess
	});//end ajax
}
