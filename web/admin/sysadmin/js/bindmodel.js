var pid;
//id:用户ID
//pageid:页面ID
function bindmodel(id)
{
	//pid = pageid;
	var url="../model/cnavigatecontroller.php?type=getnavbytid&id="+id;
	ajaxCommon(url, getmodel, "json");
	
}
//获取用户导航列表
function getmodel(data)
{
	var obj= $(window.parent.document).find("#modelvalue");
	pid = obj.attr("value");
	var str='';
	if(data!=null)
	{
		$.each(data.children, function(i){
		if(pid==data.children[i].id)
		{
			//str="<div style='background:#333'><a href='"+data.children[i].url+"'>"+data.children[i].label+"</a></div>";
			str="<div style='background:#333'><a href='"+data.children[i].id+".shtml'>"+data.children[i].label+"</a></div>";
		}
		else
		{
			str="<div class='css2'><a href='"+data.children[i].id+".shtml'>"+data.children[i].label+"</a></div>";
		}
		$("#nav_bar").append(str);							   
	});
	}
	
}
