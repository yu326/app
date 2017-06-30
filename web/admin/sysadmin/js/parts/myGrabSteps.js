/*
 * 2016-4-5
 * 任务抓取步骤
 * 依赖jQuery 实现
 * */
var myGrabSteps;
if (!myGrabSteps) {
    myGrabSteps = {};
}
(function () {
    function initTemplate(){
        //初始化模板
        if(config.allSpiderConfig.length == 0){
            getSpiderConfig(function(data){
                if(data){
                    config.allSpiderConfig = data;
                }
            });
        }
	    $.each(config.allSpiderConfig, function(i,v){
            if(v.pagestyletype != undefined){
                switch(parseInt(v.pagestyletype,10)){
                    case 1: //列表模板
                        $("select[name=template]").append('<option value="'+v.id+'">'+v.name+'</option>')
                        break;
                    case 2: //文章模板
                        $("select[name=template]").append('<option value="'+v.id+'">'+v.name+'</option>')
                        break
                    case 3:
                        $("select[name=template]").append('<option value="'+v.id+'">'+v.name+'</option>')
                        break;
                    default:
                        break;
                }
            }
	    });           
        //初始化化模板end
    };
    if(typeof myGrabSteps.init !== 'function'){
        myGrabSteps.init = function(options){
            $("#grabstepbox").remove();
            var showName = '设置模板步骤';
            var grabstephtml = "";
            grabstephtml += "<div><span>选择模板:</span>"; //select start
            grabstephtml += "<select name='template' id='gs_template'>";
            grabstephtml += "</select>";
            grabstephtml += "</div>"; //select end
            grabstephtml += "<fieldset>";
            grabstephtml += "<legend>"+showName+"</legend>";
            grabstephtml += "<div>";
            grabstephtml += "<div>"; //const start;
            //步骤ID
            grabstephtml += "<div>";
            grabstephtml += "<span>步骤ID：</span>";
            grabstephtml += "<input type='text' id='gs_stepid_text'/>";
            grabstephtml += "</div>";
            //步骤名称
            grabstephtml += "<div>";
            grabstephtml += "<span>步骤模板：</span>";
            grabstephtml += "<input type='text' id='gs_steptpl_text'/>";
            grabstephtml += "</div>";
            //规则
            grabstephtml += "<div>";
            grabstephtml += "<span>分析抓取url正则表达式：</span>";
            grabstephtml += "<textarea rows='2' style='width:475px;' wrap='off' style='overflow:scroll;'  id='gs_urlregex_text' name='gs_urlregex_text'></textarea>";
            grabstephtml += "</div>";
            grabstephtml += "</div>"; //const end;
            grabstephtml += "<div>";
            grabstephtml += "<span><input type='button' value='添加步骤' id='gs_addstep_btn' /></span>";
            grabstephtml += "</div>";
            grabstephtml += "<div id='gs_addedstep_div'>";

            grabstephtml += "</div>";

            grabstephtml += "</div>";
            grabstephtml += "</fieldset>";
            $("<div id='grabstepbox' title='"+showName+"列表'></div>").insertAfter("body");
            $("#grabstepbox").append(grabstephtml);
            initTemplate();
            if(options.checkedVal){
                var spanhtml = "";
                for(var i=0,ilen=options.checkedVal.length;i<ilen;i++){
                    var urltpl = options.checkedVal[i];
                    $("#gs_template").val(urltpl.tpl);
                    spanhtml += "<span class='selwordsbox'>";
                    spanhtml += "<span name='step_span' tpl='"+urltpl.tpl+"'><span><span>步骤ID：</span><span name='grabstep_id' >"+urltpl.stepid+"</span></span><br/><span><span>步骤模板：</span><span name='grabstep_tpl' >"+urltpl.steptpl+"</span></span><br/><span><span>当前步骤校验规则：</span><span name='grabstep_urlregex'>"+urltpl.urlregex+"</span></span></span>";
                    spanhtml += "<a class='cancleitem'>×</a>";
                    spanhtml += "</span>";
                }
                $("#gs_addedstep_div").empty().append(spanhtml);
                $("#gs_addedstep_div").find("a[class=cancleitem]").unbind("click");
                $("#gs_addedstep_div").find("a[class=cancleitem]").bind("click", function(){
                    $(this).parent().remove();
                });
            }
            $("#grabstepbox").dialog({
                autoOpen: true,
                modal:true,
                width:550,
                buttons:{
                    "确定":function(){
                        var retArr = [];
                        $("#gs_addedstep_div").find("span[name=step_span]").each(function(i, item){
                            var tmpobj = {};
                            tmpobj.tpl = $(item).attr("tpl");
                            tmpobj.stepid = $(item).find('span[name=grabstep_id]').text();
                            tmpobj.steptpl = $(item).find('span[name=grabstep_tpl]').text();
                            tmpobj.urlregex = $(item).find('span[name=grabstep_urlregex]').text();
                            retArr.push(tmpobj);
                        });
                        options.successResult(retArr);
                        $("#grabstepbox").dialog("close");
                    },
                "取消":function(){
                    $("#grabstepbox").dialog("close");
                }
                },
                close:function(){
                }
            });
            $("#gs_template").unbind("change");
            $("#gs_template").bind("change", function(){
                $("#gs_addedstep_div").empty();
            });
            //事件处理
            $("#gs_addstep_btn").unbind("click");
            $("#gs_addstep_btn").bind("click", function(){
                var tpl = $("#gs_template").val();
                var urltpl = {};
                $.each(config.allSpiderConfig, function(ci, citem){
                    if(citem.id == tpl){
                        urltpl = citem;
                        return false;
                    }
                });
                var stepid = $("#gs_stepid_text").val();
                var steptpl = $("#gs_steptpl_text").val();
                var urlregex = $("#gs_urlregex_text").val();
                var spanhtml = "";
                spanhtml += "<span class='selwordsbox'>";
                spanhtml += "<span name='step_span' tpl='"+tpl+"'><span><span>步骤ID：</span><span name='grabstep_id'>"+stepid+"</span></span><br/><span><span>步骤模板：</span><span name='grabstep_tpl'>"+steptpl+"</span></span><br/><span><span>当前步骤校验规则：</span><span name='grabstep_urlregex'>"+urlregex+"</span></span></span>";
                spanhtml += "<a class='cancleitem'>×</a>";
                spanhtml += "</span>";
                $("#gs_addedstep_div").append(spanhtml);
                $("#gs_addedstep_div").find("a[class=cancleitem]").unbind("click");
                $("#gs_addedstep_div").find("a[class=cancleitem]").bind("click", function(){
                    $(this).parent().remove();
                });
            });
        };
    }
}());
