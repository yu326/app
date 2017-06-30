var isall=0;
function bindgroup(type,id)
{ isall=type;
	var searchnameUrl = config.modelUrl+"resource_model.php?type=getgroupnopage&gid="+id;
	ajaxCommon(searchnameUrl, searchgroup, "json");
}


function searchgroup(data)
{
	if(isall==1)
	{
		//$('#groupid').append( new Option("请选择",0));
		$("<option value=0>请选择</option>").appendTo("#groupid");
	}
	else
	{
		//$('#groupid').append( new Option("全部",0));
		$("<option value=0>全部</option>").appendTo("#groupid");
	}
		if(data!=null)
	{
		$.each(data.children, function(i){
		//$('#groupid').append( new Option(data.children[i].label,data.children[i].groupid) );
			$("<option value="+data.children[i].groupid+">"+data.children[i].label+"</option>").appendTo("#groupid");						   
	});
	}
	
}

