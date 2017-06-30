/*
 * 2016-1-29 bert
 * 爬虫任务参数的路径
 * 依赖jQuery 实现
 * */
var myParamPath;
if (!myParamPath) {
    myParamPath = {};
}
var SE_TYPE_INT32 = 5; /* mapping to APR_DBD_TYPE_INT, int32 */
var SE_TYPE_STRING = 13; /* mapping to APR_DBD_TYPE_STRING, char* */
var SE_TYPE_OBJECT = 23; /* This is used for object data type of NoSQL column only */
var SE_TYPE_ARRAY = 24; /* This is used for array data type of NoSQL column only */
var SE_TYPE_TIMESTAMP = 18;      /* mapping to APR_DBD_TYPE_TIMESTAMP, char*, represented as string */

var SE_NRNAME_TYPE_ARRARY = 1;
var SE_NRNAME_TYPE_OBJECT = 2;

(function(){
    function ct2Cvt(type){
        var cvt = "";
        switch(parseInt(type, 10)){
            case SE_TYPE_INT32:
                cvt = "i32_val";
                break;
            case SE_TYPE_STRING:
            case SE_TYPE_TIMESTAMP:
                cvt = "str";
                break;
            case SE_TYPE_ARRAY:
                cvt = "col_link";
                break;
            case SE_TYPE_OBJECT:
                cvt = "col_map";
                break;
            default:
                break;
        }
        return cvt;
    }
    function selectPath(paramdef){
        if(paramdef == null){
            alert("请先定义参数！");
            return false;
        }
        var predis = "";
        if (paramdef.col_type == SE_TYPE_ARRAY || paramdef.col_type == SE_TYPE_OBJECT) {
            predis = '<i class="glyphicon glyphicon glyphicon-plus-sign" name="parent">+</i>';
        } else {
            predis = '<i class="glyphicon glyphicon glyphicon-minus-sign">-</i>';
        }
        //存储元信息
        var metaObj = {};
        metaObj = paramdef;
        metaObj.level = 0;
        var cnamehtml = "";
        if (metaObj.col_type == SE_TYPE_ARRAY) {
            cnamehtml += "<span name='meta_idx_span'><input type='text' name='fieldindex' title='输入索引1~n,或-1:任一,-2:任多个' style='width:30px;' /></span>";
        }
        var slco = "style='color:#000000;cursor:pointer;' class='label'";
        var rethtml = "<span style='width:150px;display:inline-block;'>" + predis + "<span " + slco + " name='meta_span' meta='" + JSON.stringify(metaObj) + "' >" + paramdef.label+ "</span>" + cnamehtml + "</span>";
        return rethtml; 
    }
    if(typeof myParamPath.init !== 'function'){
        myParamPath.init = function(paramdef, successResult, needname){
            if(!paramdef){
                return;
            }
            $("#parampathbox").remove();
            var showName = '定义所需参数';
            var parampathhtml = "";
            parampathhtml += "<fieldset>";
            parampathhtml += "<legend>"+showName+"</legend>";
            parampathhtml += "<div>";
            if(needname){
                parampathhtml += "<div>";
                parampathhtml += "路径名称：<input type='text' id='param_name' />";
                parampathhtml += "</div>";
            }
            parampathhtml += "<div><select id='paramtype_select'>";
            var firstItem = ""
            for(var item in paramdef){
                if(!firstItem && paramdef[item] != null){
                    firstItem = item;
                }
                if(paramdef[item] != null){
                    switch(item){
                        case "constants_def":
                            parampathhtml += "<option value='constants_def'>常量</option>";
                            break;
                        case "paramsDef_def":
                            parampathhtml += "<option value='paramsDef_def'>参数定义</option>";
                            break;
                        case "runTimeParam_def":
                            parampathhtml += "<option value='runTimeParam_def'>运行时参数</option>";
                            break;
                        case "parentParam_def":
                            parampathhtml += "<option value='parentParam_def'>父参数</option>";
                            break;
                        case "outData_def":
                            parampathhtml += "<option value='outData_def'>抓取记录</option>";
                            break;
                        case "g_global_def":
                            parampathhtml += "<option value='g_global_def'>g_global获取</option>";
                            break;
                        case "URLCache_def":
                            parampathhtml += "<option value='URLCache_def'>URL cache获取</option>";
                            break;
                        case "TaskCache_def":
                            parampathhtml += "<option value='TaskCache_def'>Task Cache获取</option>";
                            break;
                        case "AppCache_def":
                            parampathhtml += "<option value='AppCache_def'>App Cache获取</option>";
                            break;
                        case "g_collect_def":
                            parampathhtml += "<option value='g_collect_def'>g_collect获取</option>";
                            break;
                        case "CurrPageCache_def":
                            parampathhtml += "<option value='CurrPageCache_def'>当前页面缓存获取</option>";
                            break;
                        case "g_current_def":
                            parampathhtml += "<option value='g_current_def'>g_current获取</option>";
                            break;
                        default:
                            break;
                    }
                }
            }
            parampathhtml += "</select></div>";
            parampathhtml += "<div id='param_div'>"; //const start;
            parampathhtml += selectPath(paramdef[firstItem]);
            parampathhtml += "</div>"; //const end;
            parampathhtml += "</div>";
            parampathhtml += "</fieldset>";
            $("<div id='parampathbox' title='"+showName+"列表'></div>").insertAfter("body");
            $("#parampathbox").append(parampathhtml);
            $("#parampathbox").dialog({
                autoOpen: true,
                modal:true,
                width:550/*,
                buttons:{
                    "确定":function(){
                        var retarr = [];
                        $("#const_added_div").find("span[name=const_span]").each(function(i, item){
                            retarr.push(jQuery.parseJSON($(item).attr("code")));
                        });
                        successResult(retarr);
                        $("#parampathbox").dialog("close");
                    },
                "取消":function(){
                    $("#parampathbox").dialog("close");
                }
                },
                close:function(){
                }
                */
            });
            $("#paramtype_select").unbind("change");
            $("#paramtype_select").bind("change", function(){
                var chkval = $(this).val();
                var rethtml = selectPath(paramdef[chkval]);
                $("#param_div").html(rethtml ? rethtml : "");
            });
            $("#parampathbox").undelegate("span[name=meta_span]", "mouseenter");
            $("#parampathbox").delegate("span[name=meta_span]", "mouseenter", function(){
                var metaStr = $(this).attr("meta");
                var metaObj = metaStr ? jQuery.parseJSON(metaStr) : null;
                if(metaObj.col_type != SE_TYPE_OBJECT && metaObj.col_type != SE_TYPE_ARRAY){
                    return;
                }
                var arrchildren = [];
                if(metaObj.col_type == SE_TYPE_OBJECT){
                    arrchildren = GetObjChildren(metaObj);
                }else{			
                    //如果当前列为数组且选择了数组中的元素
                    if($(this).parent("span").children("span[name=meta_idx_span]").children("input").val()!=""){
                        var arridx = parseInt($(this).parent("span").children("span[name=meta_idx_span]").children("input").val(),10);
                        var reg = new RegExp(/(^(\d|-?)\d*\.\d+$)|(^(\d|-?)\d+$)/);
                        var str = commonFun.trim(arridx);
                        if(!reg.test(str)){//
                            return;
                        }
                        var childrenObj = deepClone(metaObj);
                        var idx = arridx-1;
                        if(arridx<0){
                            idx = arridx;
                        }
                        childrenObj.name_ex = Getwholename_exObj(childrenObj.name_ex,Getpartname_exObj(SE_NRNAME_TYPE_ARRARY,idx));
                        childrenObj.child_col_type_ex = {};
                        if(metaObj.child_col_type_ex){
                            childrenObj.child_col_type_ex = metaObj.child_col_type_ex.ele_col.col_type_ex;
                            childrenObj.col_type = metaObj.child_col_type_ex.ele_col.col_type;
                        }else{
                            childrenObj.child_col_type_ex = metaObj.col_type_ex.ele_col.col_type_ex;
                            childrenObj.col_type = metaObj.col_type_ex.ele_col.col_type;
                        }
                        if(arridx == -2){
                            childrenObj.col_type = metaObj.col_type;
                            childrenObj.child_col_type_ex = null;
                        }
                        childrenObj.idx = arridx;
                        childrenObj.dsp_col_name = metaObj.name+"["+arridx+"]";
                        childrenObj.chld_col_name = childrenObj.dsp_col_name;
                        childrenObj.col_type_ex = metaObj.col_type_ex;
                        childrenObj.level = metaObj.level+1;
                        arrchildren.push(childrenObj);

                    }else{
                        return;
                    }				
                }
                if(arrchildren.length>0){
                    var level = metaObj.level;
                    var colhtml = '';
                    colhtml += '<div style="border:1px solid red;margin-left:10px;">';
                    colhtml += '<div id="parentField'+level+'">';
                    colhtml += '</div>';
                    colhtml += '</div>';
                    if($("#parentField"+level+"").length == 0){
                        $(this).parent().append(colhtml);
                    }
                    /*
                       $(childself).popover({
                       trigger:'manual',  //触发
                       template:'<div class="popover" role="tooltip" style="margin-top:0;max-width:630px;"><div class="popover-content"></div></div>',
                       placement:'bottom',
                       html:true,
                       content:function(){
                       var colhtml = '';
                       colhtml += '<div id="parentField'+level+'">';
                       colhtml += '</div>';
                       return colhtml;
                       },
                       });
                       $(childself).popover('show');
                       */
                    var childhtml = "";
                    $.each(arrchildren, function(ci, chitem){
                        childhtml += childFieldHtml(chitem, level);
                    });
                    $("#parentField"+level).empty().append(childhtml);

                    $("#parentField"+level).bind("mouseleave", function(){
                        $("#parentField"+level).parent().remove();
                    });
                }
            });
            $("#parampathbox").undelegate("span[name=meta_span]", "click");
            $("#parampathbox").delegate("span[name=meta_span]", "click", function(){
                var metaObj = jQuery.parseJSON($(this).attr("meta"));
                var rootMetaObj = jQuery.parseJSON($("#param_div").children().children('span[name=meta_span]').attr("meta"));
                $("div[id^=parentField]").parent().parent("div[role=tooltip]").remove();
                var resultObj = {};
                var pId = '';
                if(needname){
                    pId = $("#param_name").val() ? $("#param_name").val() : "path"+Math.floor((Math.random()*1000)+1);
                }
                else{
                    pId = "path"+Math.floor((Math.random()*1000)+1);
                }
                resultObj.paramPathId = pId;
                var psel = $("#paramtype_select").val();
                resultObj.paramPath = {};
                if(psel == "g_collect_def"){
                    resultObj.paramPath.col_name = rootMetaObj.name;
                    resultObj.paramPath.col_type = rootMetaObj.col_type;
                    resultObj.paramPath.col_name_ex = metaObj.name_ex;
                }
                else{
                    resultObj.paramPath.col_name = metaObj.name_ex.data.chld_col_name;
                    resultObj.paramPath.col_type = metaObj.col_type;
                    resultObj.paramPath.col_name_ex = metaObj.name_ex.name_ex;
                }
                switch(psel){
                    case "constants_def":
                        resultObj.paramPath.paramSource = 1;
                        break;
                    case "paramsDef_def":
                        resultObj.paramPath.paramSource = 2;
                        break;
                    case "runTimeParam_def":
                        resultObj.paramPath.paramSource = 3;
                        break;
                    case "parentParam_def":
                        resultObj.paramPath.paramSource = 4;
                        break;
                    case "outData_def":
                        resultObj.paramPath.paramSource = 5;
                        break;
                    case "g_global_def":
                        resultObj.paramPath.paramSource = 6;
                        break;
                    case "URLCache_def":
                        resultObj.paramPath.paramSource = 7;
                        break;
                    case "TaskCache_def":
                        resultObj.paramPath.paramSource = 8;
                        break;
                    case "AppCache_def":
                        resultObj.paramPath.paramSource = 9;
                        break;
                    case "g_collect_def":
                        resultObj.paramPath.paramSource = 10;
                        break;
                    case "CurrPageCache_def":
                        resultObj.paramPath.paramSource = 11;
                        break;
                    case "g_current_def":
                        resultObj.paramPath.paramSource = 12;
                        break;
                    default:
                        break;
                }
                successResult(resultObj);
                $("#parampathbox").dialog("close");
            });
        };
    }
    function childFieldHtml(fieldObj, level){
        var childhtml = "";
        var curlevel = parseInt(level,10)+1;
        var predis = "";
        if((fieldObj.col_type == SE_TYPE_ARRAY && fieldObj.idx != -2) || fieldObj.col_type == SE_TYPE_OBJECT){
            predis = '<i class="glyphicon glyphicon glyphicon-plus-sign" name="parent">+</i>';
        }
        else{
            predis = '<i class="glyphicon glyphicon glyphicon-minus-sign">-</i>';
        }
        fieldObj.level = curlevel;
        var cnamehtml = "";
        if(fieldObj.col_type == SE_TYPE_ARRAY && fieldObj.idx != -2){
            cnamehtml += "<span name='meta_idx_span'><input type='text' name='fieldindex'  title='输入索引1~n,或-1:任一,-2:任多个' style='width:30px;'/></span>";
        }
        childhtml += "<span style='width:150px;display:inline-block;'>"+predis+"<span class='label' name='meta_span' style='color:#000000;cursor:pointer;' meta='"+JSON.stringify(fieldObj)+"'>"+fieldObj.dsp_col_name+"</span>"+cnamehtml+"</span>";
        return childhtml;
    };

//生成col_name_ex的片段
/*这里使用data.arr_data和data.chld_col_name的结构*/
function Getpartname_exObj(nrname_type, child){
	var col_name_ex = {};
	if(nrname_type == SE_NRNAME_TYPE_ARRARY){
		col_name_ex.type = SE_NRNAME_TYPE_ARRARY;
		col_name_ex.data = {};
		col_name_ex.data.arr_data = {};
		col_name_ex.data.arr_data.arr_idx = child || 0;
		col_name_ex.data.arr_data.min_comb_eles = null;
		col_name_ex.data.arr_data.max_comb_eles= null;
	}
	else{
		col_name_ex.type = SE_NRNAME_TYPE_OBJECT;
        col_name_ex.data = {};
		col_name_ex.data.chld_col_name = child || "";
	}
	col_name_ex.name_ex = null;
	return col_name_ex;
};

//生成name_ex
function Getwholename_exObj(wholenameex,partname_ex){
	if(wholenameex){
		var tempObj = deepClone(wholenameex);
		var curarray = [];
		while(tempObj.name_ex){
			curarray.push(tempObj);
			tempObj = deepClone(tempObj.name_ex);
		}
		var len = curarray.length;
		if(len>0){
			wholenameex = tempObj;
			wholenameex.name_ex = partname_ex;
			for(i=0;i<len;i++){
				tempObj = curarray.pop();
				tempObj.name_ex = wholenameex;
				wholenameex = deepClone(tempObj);
			}
		}else{
			wholenameex.name_ex = partname_ex;
		}
	}else{
		wholenameex = partname_ex;
	}
	return wholenameex;
};
function GetObjChildren(metaObj){
	//根据元数据的col_type_ex获取对象的孩子节点并返回
	var arrchildren = [];
	var col_type_ex = {};
	if(metaObj.child_col_type_ex){
		col_type_ex = metaObj.child_col_type_ex;
	}else{
		col_type_ex = metaObj.col_type_ex;
	}
	for(var key in col_type_ex.sc_map){
		var name_ex = {};
		var childobj = {};
		childobj.name_ex = {};
		childobj.col_type_ex = metaObj.col_type_ex;
		//childobj.folder_id = metaObj.folder_id;
		//childobj.log_col_id = metaObj.log_col_id;
		//childobj.table_id = metaObj.table_id;
		//childobj.col_name = metaObj.col_name;
		childobj.dsp_col_name = key;
		childobj.chld_col_name = key;
		childobj.ds_type = metaObj.ds_type;
		childobj.col_type = col_type_ex.sc_map[key].col_type;
		if(col_type_ex.sc_map[key].col_type_ex){
			childobj.child_col_type_ex = {};
			childobj.child_col_type_ex = col_type_ex.sc_map[key].col_type_ex;//只为了界面选择时便于查找使用的结构，后台元数据看不到
		}
		name_ex = Getpartname_exObj(SE_NRNAME_TYPE_OBJECT,key);
		if(metaObj.name_ex){
			childobj.name_ex = Getwholename_exObj(deepClone(metaObj.name_ex),name_ex);
		}else{
			childobj.name_ex = name_ex;
		}
		if(metaObj.level){
			childobj.level = metaObj.level+1;
		}else{
			childobj.level = 1;
		}
		arrchildren.push(childobj);
	}
	return arrchildren;
};
}());
