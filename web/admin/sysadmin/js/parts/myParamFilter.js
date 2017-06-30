/*
 * 2016-1-29 bert
 * 爬虫任务过滤条件赋值
 * 依赖jQuery 实现
 * */
var myParamFilter;
if (!myParamFilter) {
    myParamFilter = {};
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
    if(typeof myParamFilter.init !== 'function'){
        myParamFilter.init = function(paramfilter, successResult){
            if(!paramfilter){
                return;
            }
            $("#paramfilterbox").remove();
            var showName = '定义所需参数';
            var paramfilterhtml = "";
            paramfilterhtml += "<fieldset>";
            paramfilterhtml += "<legend>"+showName+"</legend>";
            paramfilterhtml += "<div>";

            paramfilterhtml += "<div>"; //const start;
            paramfilterhtml += "<div>";
            paramfilterhtml += "<span title='模板中仅存过滤条件ID,此处为过滤条件的具体实现'>过滤条件ID:</span>";
            paramfilterhtml += "<select id='filter_id_select'>";
            paramfilterhtml += "<option value='0'>0</option>";
            paramfilterhtml += "<option value='1'>1</option>";
            paramfilterhtml += "<option value='2'>2</option>";
            paramfilterhtml += "</select>";
            paramfilterhtml += "</div>";
            //条件运算符
            paramfilterhtml += "<div id='filter_exp_div'>";
            paramfilterhtml += "<span title='模板中根据规则获取的值为左操作数,此处配置值的为右操作数,以此选择条件运算符'>条件运算符:</span>";
            paramfilterhtml += "<select id='filter_exp_select'>";
            paramfilterhtml += "<option value='='>等于</option>";
            paramfilterhtml += "<option value='>'>大于</option>";
            paramfilterhtml += "<option value='<'>小于</option>";
            paramfilterhtml += "</select>";
            paramfilterhtml += "</div>";
            
            //操作数的类型
            paramfilterhtml += "<div>";
            paramfilterhtml += "<span>过滤条件类型:</span>";
            paramfilterhtml += "<select id='filter_operand_select'>";
            paramfilterhtml += "<option value='num'>数值</option>";
            paramfilterhtml += "<option value='time'>时间</option>";
            paramfilterhtml += "</select>";
            paramfilterhtml += "</div>";
            //数值过滤值
            paramfilterhtml += '<div id="filter_num_div">';
            paramfilterhtml += '<span class="tdleft">数值：</span>';
            paramfilterhtml += '<span>';
            paramfilterhtml += '<input id="filter_num" name="filter_num" type="text" style="width:150px;" /><span id="filter_num_var" valueType="cons" style="cursor:pointer;color:blue;">来自参数</span><span id="filter_num_val" ></span>';
            paramfilterhtml += '</span>';
            paramfilterhtml += '</div>';

            //时间过滤值
            paramfilterhtml += '<div id="filter_time_div" style="display:none;">';
            paramfilterhtml += '<span class="tdleft">时间：</span>';
            paramfilterhtml += '<span>';
            paramfilterhtml += '<input id="filter_time" name="filter_time" class="Wdate" type="text" readonly="readonly" style="width:150px;" /><span id="filter_time_var" valueType="cons" style="cursor:pointer;color:blue;">来自参数</span><span id="filter_time_val" ></span>';
            paramfilterhtml += '</span>';
            paramfilterhtml += '</div>';
            paramfilterhtml += "</div>"; //const end;

            paramfilterhtml += "</div>";
            paramfilterhtml += "</fieldset>";
            $("<div id='paramfilterbox' title='"+showName+"列表'></div>").insertAfter("body");
            $("#paramfilterbox").append(paramfilterhtml);
            $("#paramfilterbox").dialog({
                autoOpen: true,
                modal:true,
                width:550,
                buttons:{
                    "确定":function(){
                        var tmpObj = {};
                        tmpObj.fileterId = $("#filter_id_select").val();
                        var operand_val = $("#filter_operand_select").val();
                        switch(operand_val){
                            case "num":
                                tmpObj.cmp = $("#filter_exp_select").val();
                                tmpObj.valueCfg = {};
                                var valueType = $("#filter_num_var").attr("valueType"); 
                                tmpObj.valueCfg.valueType = valueType;
                                if(valueType == "cons"){
                                    var end = $("#filter_num").val();
                                    tmpObj.valueCfg.value = end;
                                }
                                else{
                                    var pathStr = $("#filter_num_var").attr("parampath");
                                    var pathObj = pathStr ? jQuery.parseJSON(pathStr) : null; 
                                    tmpObj.valueCfg.paramSource = pathObj.paramSource;
                                    tmpObj.valueCfg.paramPathId = pathObj.paramPathId;
                                    tmpObj.valueCfg.paramPath = pathObj.paramPath;
                                }
                                break;
                            case "time":
                                tmpObj.cmp = $("#filter_exp_select").val();
                                tmpObj.valueCfg = {};
                                var valueType = $("#filter_time_var").attr("valueType"); 
                                tmpObj.valueCfg.valueType = valueType;
                                if(valueType == "cons"){
                                    var endtime = $("#filter_time").val();
                                    var end = getTimeSec(endtime);
                                    tmpObj.valueCfg.value = end;
                                }
                                else{
                                    var pathStr = $("#filter_time_var").attr("parampath");
                                    var pathObj = pathStr ? jQuery.parseJSON(pathStr) : null; 
                                    tmpObj.valueCfg.paramSource = pathObj.paramSource;
                                    tmpObj.valueCfg.paramPathId = pathObj.paramPathId;
                                    tmpObj.valueCfg.paramPath = pathObj.paramPath;
                                }
                                break;
                        }
                        successResult(tmpObj);
                        $("#paramfilterbox").dialog("close");
                    },
                "取消":function(){
                    $("#paramfilterbox").dialog("close");
                }
                },
                close:function(){
                }
            });
            //事件处理
            $("#filter_operand_select").unbind("change");
            $("#filter_operand_select").bind("change", function(){
                var opval = $(this).val();
                switch(opval){
                    case "num":
                        $("#filter_exp_div").show();
                        $("#filter_num_div").show();
                        $("#filter_time_div").hide();
                        break;
                    case "time":
                        $("#filter_exp_div").show();
                        $("#filter_num_div").hide();
                        $("#filter_time_div").show();
                        break;
                    default:
                        break;
                }
            });
            $("#filter_num_var").unbind("click");
            $("#filter_num_var").bind("click", function(){
                if(paramfilter){
                    myParamPath.init(paramfilter, function(data){
                        $("#filter_num_var").attr("valueType", "var");
                        $("#filter_num_var").attr("parampath", JSON.stringify(data));
                        $("#filter_num_val").text(data.paramPathId);
                    });
                }
                else{
                    alert('请先进行参数定义');
                }
            });
            $("#filter_time").unbind("click");
            $("#filter_time").bind("click", function(){
                WdatePicker({dateFmt:'yyyy-MM-dd HH:mm:ss'});
            });
            $("#filter_time_var").unbind("click");
            $("#filter_time_var").bind("click", function(){
                if(paramfilter){
                    myParamPath.init(paramfilter, function(data){
                        $("#filter_time_var").attr("valueType", "var");
                        $("#filter_time_var").attr("parampath", JSON.stringify(data));
                        $("#filter_time_val").text(data.paramPathId);
                    });
                }
                else{
                    alert('请先进行参数定义');
                }
            });
        };
    }
}());
