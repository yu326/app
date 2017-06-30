function timertaskcanvas(){
	var _this = this;
	this.currEditElement;
	this.currModelLayer;
	var jsPlumbInstance = null;
	this.collen = 0;
	this.rowlen = 0;
    this.taskInstance = {};
    _this.taskInstance.tasks = [];
    this.maxTaskID = 0;
	this.setCurrModelLayer = function(sender){
		_this.currModelLayer = $(sender);
		$("#modellayersdiv div[id^=modellayer_]").removeAttr("active");
		_this.currModelLayer.attr("active", true);
	};
    this.copyData = [];
    this.copyHtml = "";
    this.taskCopy = function(targetdiv){
		var showelearr = [];
		var htm = "";
        if($(targetdiv).attr("modellayerindex") !== undefined){
            var origidx = $(targetdiv).attr("id");
            $(targetdiv).attr("id", "copy_"+origidx);
			htm = getFHHtml($(targetdiv));
			$(targetdiv).attr("id",origidx);//改回去
			if(htm != ""){
				 htm += "</div>";
			}
			showelearr.push(targetdiv);
        }
		if(htm == ""){
			return false;
		}
		_this.copyHtml = htm;
		for(var i=0; i< showelearr.length; i++){
            if($(showelearr[i]).attr("modellayerindex") !== undefined){
                var copyinc = {tasks : []};
                var taskid = $(showelearr[i]).attr("modellayerindex");
                var oriele = {};
                for(var i=0;i<_this.taskInstance.tasks.length;i++){
                    var currele = _this.taskInstance.tasks[i];
                    if(currele.taskID == taskid){
                        oriele = currele;
                        break;
                    }
                }
                var ele = {param:deepClone(oriele.param), oldTaskID:oriele.taskID};
                copyinc.tasks.push(ele);
            }
            _this.copyData.push(copyinc);
		}
        return true;
	};
    this.addModelLayer = function(item){
        var taskid;
        taskid = item.taskID;
        var curlayid = 'modellayer_'+taskid+'';
        var mlen = $("div[modellayerindex]").length;
        var left = 290 * mlen + 35;
        var top = 80;
        if(left > 740){
            if(_this.rowlen == 0){
                _this.collen = mlen;
            }
            left = 290 * (mlen - _this.collen * _this.rowlen) + 35; //第n行的第n个
            _this.rowlen = _this.rowlen + 1;
            top = 80 * _this.rowlen;
        }
        //var imgtag = "";
        var imgurl = config.imagePath+"osf_list.png";
        var shortname = "任务"+item.taskID+"";
        var modelnameAttr = "";
        if(shortname.length > 15){
            shortname = shortname.substring(0,15)+"...";
            modelnameAttr = "modelname='任务"+item.taskID+"'";
        }
        var lhtml = '';
        lhtml += '<div  id="'+curlayid+'" modellayerindex='+taskid+' style="border:1px solid rgb(217, 237, 247);display:inline-block;position:absolute;left:'+left+'px;top:'+top+'px;">';
        lhtml += '<span style="dispaly:inline-block;margin-top:0px; height:23px; padding:0px;margin-bottom:0px;100%;text-align:center;">';
        lhtml += '<label style="width:100%;background-color:rgb(217, 237, 247);" id="modellayer_name'+taskid+'" title="点击修改名称" '+modelnameAttr+'>'+shortname+'</label>';
        lhtml += '<a style= href="javascript:void(0)" title="删除模型"><a onclick="javascript:window.taskcanvasobj.delElement(this)" style="float:right;border:0px;position:absolute;right:2px;width: 16px;height: 16px;background-repeat: no-repeat;background-size: 100% 100%;background-image: url('+config.imagePath+'sdel.gif);"></a></a>';
        lhtml += '</span>';
        lhtml += '<div canvas_taskid="'+taskid+'" style="cursor:move;">';
        /*
        lhtml += '<div style="float:left;width:130px;margin:8px 4px 4px 4px;">'; //左侧选项
        lhtml += '<span style="display:block;"><span style="width:80px;display:inline-block;text-align:left;margin-right:3px;">仪表盘显示</span><input type="checkbox" id="modellayer_dashboarddisplay'+taskid+'" checked="true" /></span>';
        lhtml += '<span style="display:block;margin-top:3px;"><span style="width:80px;display:inline-block;text-align:left;margin-right:3px;">保存快照</span><input type="checkbox" id="modellayer_snapshotsaved'+taskid+'" checked="true"/></span>';
        lhtml += '</div>';//左侧选项结束
        */
        lhtml += '<div class="canvaselement" modellayer_num="'+taskid+'" title="点击进行编辑" style="float: left;width: 116px;height: 100px;background-repeat: no-repeat;background-size: 100% 100%;background-image: url('+imgurl+');margin:8px 4px 4px 4px;">';//图片
        //lhtml += imgtag;
        lhtml += '<span id="modellayer_dashboarddisplay'+taskid+'_hook" class="hook" title="拖动此处和其他模型连接"></span>';
        lhtml += '</div>';//图片end
        lhtml += '<div style="position:absolute;left:124px;display:none;">';//右侧功能
        lhtml += '<span style="display:inline-block;"><input type="button" style="font-size:10px;float:right;" id="modellayer_canvascopy'+taskid+'" value="创建副本" /></span>';
        lhtml += '</div>';//右侧功能结束
        lhtml += '</div>';
        lhtml += '</div>';
        $("#modellayersdiv").append(lhtml);
        //鼠标滑过显示左侧功能按钮
        $("div[class=canvaselement]").unbind("mouseenter");
        $("div[class=canvaselement]").bind("mouseenter", function(){
            var modellayer_num = $(this).attr("modellayer_num");
            var sltor = $("#modellayer_canvascopy"+modellayer_num+"").parent().parent();
            sltor.show();
            sltor.unbind("mouseleave");
            sltor.bind("mouseleave", function(){
                $(this).hide();
            })
        });
        $("div[class=canvaselement]").unbind("mouseleave");
        $("div[class=canvaselement]").bind("mouseleave", function(event){
            var modellayer_num = $(this).attr("modellayer_num");
            //判断鼠标所在地区域, 在展开div区域内时不收起
            var ex,ey,targetwidth,targetheight,targetx,targety;
            //鼠标当前的位置
            ex = event.pageX;
            ey = event.pageY;

            //计算弹出div所占区域
            var sltor = $("#modellayer_canvascopy"+modellayer_num+"").parent().parent();
            var thisoffset =  sltor.offset(); 
            if(thisoffset != null){
                targetx = thisoffset.left-5;
                targety = thisoffset.top; //从文字移动到div缓冲区域
                targetwidth = sltor.outerWidth(true);
                targetheight = sltor.outerHeight(true);
                if(ex>targetx && ex<=(targetx+targetwidth) && ey > targety && ey <=(targety+targetheight)){
                    return false;
                }
            }
            sltor.hide();
        });
        $("#modellayer_name"+taskid+"").unbind("click");
        $("#modellayer_name"+taskid+"").bind("click", function(){
            var inputlen = $(this).find("input").length;
            if(inputlen == 0){
                var mname = $(this).text();
                var nameinput = "<input type='text' value='"+mname+"' style='width:100px;height:22px;' />";
                $(this).html(nameinput);
            }
        });
        $("#modellayer_name"+taskid+"").undelegate("input");
        $("#modellayer_name"+taskid+"").delegate("input", "blur", function(){
            var mname = $(this).val();
            var shortname = mname;
            if(mname.length > 15){
                $("#modellayer_name"+taskid+"").attr("modelname", mname);
                shortname = mname.substring(0,15)+"...";
            }
            $("#modellayer_name"+taskid+"").empty().text(shortname);
			item.taskName = mname;
        });
        $("#modellayer_canvascopy"+taskid+"").unbind("click");
        $("#modellayer_canvascopy"+taskid+"").bind("click", function(event){
            var targetdiv = $(this).parents("div[id=modellayer_"+taskid+"]");
            if(_this.taskCopy(targetdiv)){
                //创建副本后直接粘贴
                var newele = {};
                for(var i=0,ilen=_this.copyData.length;i<ilen; i++){
                    var inc = _this.copyData[i];
                    var curele = inc.tasks[0];
                    var cureleName = '模型'+Math.floor((Math.random()*1000)+1);
                    var newele = {};
                    newele.taskID = _this.maxTaskID;
                    newele.param = curele.param;
                    //_this.taskInstance.tasks.push(newele);
                    _this.completeAddElement(newele);
                    _this.copyData = [];
                }
            }
            else{
                alert('创建副本失败');
            }
        });
        _this.setCurrModelLayer("#"+curlayid);
        var modellayer = jsPlumb.getSelector("div[modellayerindex]");
        // initialise draggable elements.
        jsPlumbInstance.draggable(modellayer, { handle: "div[canvas_taskid]" });
        jsPlumbInstance.makeSource(modellayer, {
            filter: ".hook", //指定连接点，否则整个div当作连接点，其上的删除按钮会失效。
            anchor: "Continuous",
            connectorStyle: { strokeStyle: "#5c96bc", lineWidth: 2, outlineColor: "transparent", outlineWidth: 4 },
            connector : "Straight",	//设置连线为直线
            maxConnections: 5,
            onMaxConnections: function (info, e) {
                alert("Maximum connections (" + info.maxConnections + ") reached");
            }
        });
        jsPlumbInstance.makeTarget(modellayer, {
            dropOptions: { hoverClass: "dragHover" },
            anchor: "Continuous",
            allowLoopback: true
        });
        $("#divaddcommonspt").dialog('close');
    };
    this.initElement = function(taskele){
        $("#divaddcommonspt").remove();//模型字段编辑div
        $.fn.zTree.destroy();
        var title = "新增任务";
        var taskid = "";
        if(taskele){
            title = "编辑任务";
            taskid = "taskid='"+taskele.taskID+"'";
        }
        var spthtml = '';
        spthtml += '<div id="divaddcommonspt" title="'+title+'" '+taskid+' class="openwindow" style="display:none;">';
        spthtml += '<form id="formaddspider"><input type="hidden" name="tasktype" value="2" />';
        spthtml += '<table class="formtable" style="width:100%">';
        //任务类型
        spthtml += '<tr id="tp12_0" style="text-align:center;">';
        spthtml += '<td colspan="3">任务类型<div class="divhr"></div></td>';
        spthtml += '</tr>';

        spthtml += '<tr id="tp12_1">';
        spthtml += '<td class="tdleft">任务类型：</td>';
        spthtml += '<td>';
        spthtml += '<input type="radio" id="tp_isroottask1" value="1" name="tp_isroottask" checked="checked"><label for="tp_isroottask1">根任务</label>';
        spthtml += '<input type="radio" id="tp_isroottask0" value="0" name="tp_isroottask"><label for="tp_isroottask0">子任务</label>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip">当前配置的是否为根任务</td>';
        spthtml += '</tr>';

        spthtml += '<tr id="tp12_2" style="display:none;">';
        spthtml += '<td class="tdleft">指定父任务：</td>';
        spthtml += '<td>';
        spthtml += '<select id="tp_parenttask" name="parenttask">';
        spthtml += '</select>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        //是否持续抓取   针对价格持续抓取
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">是否持续抓取：</td>';
        spthtml += '<td>';
        spthtml += '<input type="radio" id="tp_creattask1" value="1" name="creattask"><label for="tp_creattask1">是</label>';
        spthtml += '<input type="radio" id="tp_creattask0" value="0" name="creattask"  checked="checked"><label for="tp_creattask0">否</label>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        //参数定义
        spthtml += '<tr style="text-align:center;">';
        spthtml += '<td colspan="3">参数定义<div class="divhr"></div></td>';
        spthtml += '</tr>';

        //常量定义 constants
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">常量定义：</td>';
        spthtml += '<td>';
        spthtml += '<input type="button" id="tp_constants_def_btn" value="常量定义" />';
        spthtml += '&nbsp;&nbsp;<input type="button" id="tp_constants_def_clear_btn" value="清除" />';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        spthtml += '<tr>';
        spthtml += '<td class="tdleft">定义：</td>';
        spthtml += '<td>';
        spthtml += "<ul id='tp_constants_def_tree' class='ztree'></ul>";
        spthtml += '<textarea rows="2" style="width:475px;display:none;" wrap="off" id="tp_constants_def_text" name="tp_constants_def_text"></textarea>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';
        //常量赋值constants
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">常量赋值：</td>';
        spthtml += '<td>';
        spthtml += '<input type="button" id="tp_constants_assign_btn" value="常量赋值" />';
        spthtml += '&nbsp;&nbsp;<input type="button" id="tp_constants_assign_clear_btn" value="清除" />';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        spthtml += '<tr>';
        spthtml += '<td class="tdleft">常量值：</td>';
        spthtml += '<td>';
        spthtml += "<ul id='tp_constants_assign_tree' class='ztree'></ul>";
        spthtml += '<textarea rows="2" style="width:475px;display:none;" wrap="off" id="tp_constants_assign_text" name="tp_constants_assign_text"></textarea>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        //任务参数 paramsDef
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">任务参数定义：</td>';
        spthtml += '<td>';
        spthtml += '<input type="button" id="tp_paramsDef_def_btn" value="任务参数定义" />';
        spthtml += '&nbsp;&nbsp;<input type="button" id="tp_paramsDef_def_clear_btn" value="清除" />';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        spthtml += '<tr>';
        spthtml += '<td class="tdleft">定义：</td>';
        spthtml += '<td>';
        spthtml += "<ul id='tp_paramsDef_def_tree' class='ztree'></ul>";
        spthtml += '<textarea rows="2" style="width:475px;display:none;" wrap="off" id="tp_paramsDef_def_text" name="tp_paramsDef_def_text"></textarea>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        //任务参数赋值 paramsDef
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">任务参数赋值：</td>';
        spthtml += '<td>';
        spthtml += '<input type="button" id="tp_paramsDef_assign_btn" value="任务参数赋值" />';
        spthtml += '&nbsp;&nbsp;<input type="button" id="tp_paramsDef_assign_clear_btn" value="清除" />';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        spthtml += '<tr>';
        spthtml += '<td class="tdleft">任务参数值：</td>';
        spthtml += '<td>';
        spthtml += "<ul id='tp_paramsDef_assign_tree' class='ztree'></ul>";
        spthtml += '<textarea rows="2" style="width:475px;display:none;" wrap="off" id="tp_paramsDef_assign_text" name="tp_paramsDef_assign_text"></textarea>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        //运行参数 runTimeParam 
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">运行参数定义：</td>';
        spthtml += '<td>';
        spthtml += '<input type="button" id="tp_runTimeParam_def_btn" value="运行参数定义" />';
        spthtml += '&nbsp;&nbsp;<input type="button" id="tp_runTimeParam_def_clear_btn" value="清除" />';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        spthtml += '<tr>';
        spthtml += '<td class="tdleft">定义：</td>';
        spthtml += '<td>';
        spthtml += "<ul id='tp_runTimeParam_def_tree' class='ztree'></ul>";
        spthtml += '<textarea rows="2" style="width:475px;display:none;" wrap="off" id="tp_runTimeParam_def_text" name="tp_runTimeParam_def_text"></textarea>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        //运行参数赋值 runTimeParam 
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">运行参数赋值：</td>';
        spthtml += '<td>';
        spthtml += '<input type="button" id="tp_runTimeParam_assign_btn" value="运行参数赋值" />';
        spthtml += '&nbsp;&nbsp;<input type="button" id="tp_runTimeParam_assign_clear_btn" value="清除" />';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        spthtml += '<tr>';
        spthtml += '<td class="tdleft">运行参数值：</td>';
        spthtml += '<td>';
        spthtml += "<ul id='tp_runTimeParam_assign_tree' class='ztree'></ul>";
        spthtml += '<textarea rows="2" style="width:475px;display:none;" wrap="off" id="tp_runTimeParam_assign_text" name="tp_runTimeParam_assign_text"></textarea>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        //父任务参数 parentParam 
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">父任务参数定义：</td>';
        spthtml += '<td>';
        spthtml += '<input type="button" id="tp_parentParam_def_btn" value="父任务参数定义" />';
        spthtml += '&nbsp;&nbsp;<input type="button" id="tp_parentParam_def_clear_btn" value="清除" />';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        spthtml += '<tr>';
        spthtml += '<td class="tdleft">定义：</td>';
        spthtml += '<td>';
        spthtml += "<ul id='tp_parentParam_def_tree' class='ztree'></ul>";
        spthtml += '<textarea rows="2" style="width:475px;display:none;" wrap="off" id="tp_parentParam_def_text" name="tp_parentParam_def_text"></textarea>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        //父任务参数 parentParam 
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">父任务参数赋值：</td>';
        spthtml += '<td>';
        spthtml += '<input type="button" id="tp_parentParam_assign_btn" value="父任务参数赋值" />';
        spthtml += '&nbsp;&nbsp;<input type="button" id="tp_parentParam_assign_clear_btn" value="清除" />';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        spthtml += '<tr>';
        spthtml += '<td class="tdleft">父任务参数值：</td>';
        spthtml += '<td>';
        spthtml += "<ul id='tp_parentParam_assign_tree' class='ztree'></ul>";
        spthtml += '<textarea rows="2" style="width:475px;display:none;" wrap="off" id="tp_parentParam_assign_text" name="tp_parentParam_assign_text"></textarea>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        //抓取记录定义 outData 
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">抓取数据定义：</td>';
        spthtml += '<td>';
        spthtml += '<input type="button" id="tp_outData_def_btn" value="抓取数据定义" />';
        spthtml += '&nbsp;&nbsp;<input type="button" id="tp_outData_def_clear_btn" value="清除" />';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        spthtml += '<tr>';
        spthtml += '<td class="tdleft">定义：</td>';
        spthtml += '<td>';
        spthtml += "<ul id='tp_outData_def_tree' class='ztree'></ul>";
        spthtml += '<textarea rows="2" style="width:475px;display:none;" wrap="off" id="tp_outData_def_text" name="tp_outData_def_text"></textarea>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        //抓取数据赋值 outData 
        /*
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">抓取数据赋值：</td>';
        spthtml += '<td>';
        spthtml += '<input type="button" id="tp_outData_assign_btn" value="抓取数据赋值" />';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        spthtml += '<tr>';
        spthtml += '<td class="tdleft">抓取数据：</td>';
        spthtml += '<td>';
        spthtml += '<textarea rows="2" style="width:475px;" wrap="off" id="tp_outData_assign_text" name="tp_outData_assign_text"></textarea>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';
        */


        //入库数据路径
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">入库数据路径：</td>';
        spthtml += '<td>';
        spthtml += '<input type="button" id="tp_outData_path_btn" value="入库数据路径" />';
        spthtml += '&nbsp;&nbsp;<input type="button" id="tp_outData_path_clear_btn" value="清除" />';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">入库数据路径：</td>';
        spthtml += '<td>';
        spthtml += "<ul id='tp_outData_path_tree'></ul>";
        spthtml += '<textarea rows="2" style="width:475px;display:none;" wrap="off" id="tp_outData_path_text" name="tp_outData_path_text"></textarea>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        //用户入库数据路径
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">用户入库数据路径：</td>';
        spthtml += '<td>';
        spthtml += '<input type="button" id="tp_userData_path_btn" value="用户入库数据路径" />';
        spthtml += '&nbsp;&nbsp;<input type="button" id="tp_userData_path_clear_btn" value="清除" />';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">用户入库数据路径：</td>';
        spthtml += '<td>';
        spthtml += "<ul id='tp_userData_path_tree'></ul>";
        spthtml += '<textarea rows="2" style="width:475px;display:none;" wrap="off" id="tp_userData_path_text" name="tp_userData_path_text"></textarea>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        spthtml += '<tr style="text-align:center;">';
        spthtml += '<td colspan="3"><div class="divhr" style="border-style:dotted"></div></td>';
        spthtml += '</tr>';

        //g_global变量中获取变量
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">获取g_global变量：</td>';
        spthtml += '<td>';
        spthtml += '<input type="button" id="tp_g_global_def_btn" value="获取g_global变量" />';
        spthtml += '&nbsp;&nbsp;<input type="button" id="tp_g_global_def_clear_btn" value="清除" />';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        spthtml += '<tr>';
        spthtml += '<td class="tdleft">定义：</td>';
        spthtml += '<td>';
        spthtml += "<ul id='tp_g_global_def_tree' class='ztree'></ul>";
        spthtml += '<textarea rows="2" style="width:475px;display:none;" wrap="off" id="tp_g_global_def_text" name="tp_g_global_def_text"></textarea>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';
        //从当前URL cache中或获取变量值(例如当前步骤号，验证码文本等)
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">获取URL cache变量：</td>';
        spthtml += '<td>';
        spthtml += '<input type="button" id="tp_URLCache_def_btn" value="获取URL cache变量" />';
        spthtml += '&nbsp;&nbsp;<input type="button" id="tp_URLCache_def_clear_btn" value="清除" />';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        spthtml += '<tr>';
        spthtml += '<td class="tdleft">定义：</td>';
        spthtml += '<td>';
        spthtml += "<ul id='tp_URLCache_def_tree' class='ztree'></ul>";
        spthtml += '<textarea rows="2" style="width:475px;display:none;" wrap="off" id="tp_URLCache_def_text" name="tp_URLCache_def_text"></textarea>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';


        //从当前Task cache中或获取变量值
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">获取Task cache变量：</td>';
        spthtml += '<td>';
        spthtml += '<input type="button" id="tp_TaskCache_def_btn" value="获取Task cache变量" />';
        spthtml += '&nbsp;&nbsp;<input type="button" id="tp_TaskCache_def_clear_btn" value="清除" />';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        spthtml += '<tr>';
        spthtml += '<td class="tdleft">定义：</td>';
        spthtml += '<td>';
        spthtml += "<ul id='tp_TaskCache_def_tree' class='ztree'></ul>";
        spthtml += '<textarea rows="2" style="width:475px;display:none;" wrap="off" id="tp_TaskCache_def_text" name="tp_TaskCache_def_text"></textarea>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        //从当前App cache中或获取变量值
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">获取App cache变量：</td>';
        spthtml += '<td>';
        spthtml += '<input type="button" id="tp_AppCache_def_btn" value="获取App cache变量" />';
        spthtml += '&nbsp;&nbsp;<input type="button" id="tp_AppCache_def_clear_btn" value="清除" />';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        spthtml += '<tr>';
        spthtml += '<td class="tdleft">定义：</td>';
        spthtml += '<td>';
        spthtml += "<ul id='tp_AppCache_def_tree' class='ztree'></ul>";
        spthtml += '<textarea rows="2" style="width:475px;display:none;" wrap="off" id="tp_AppCache_def_text" name="tp_AppCache_def_text"></textarea>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        //从当前g_collect中或获取变量值
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">获取g_collect变量：</td>';
        spthtml += '<td>';
        spthtml += '<input type="button" id="tp_g_collect_def_btn" value="获取g_collect变量" />';
        spthtml += '&nbsp;&nbsp;<input type="button" id="tp_g_collect_def_clear_btn" value="清除" />';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        spthtml += '<tr>';
        spthtml += '<td class="tdleft">定义：</td>';
        spthtml += '<td>';
        spthtml += "<ul id='tp_g_collect_def_tree' class='ztree'></ul>";
        spthtml += '<textarea rows="2" style="width:475px;display:none;" wrap="off" id="tp_g_collect_def_text" name="tp_g_collect_def_text"></textarea>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';


        //当前页面内的缓存(例如获取：当前页面URL,current_wnd_location)
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">获取CurrPageCache变量：</td>';
        spthtml += '<td>';
        spthtml += '<input type="button" id="tp_CurrPageCache_def_btn" value="获取CurrPageCache变量" />';
        spthtml += '&nbsp;&nbsp;<input type="button" id="tp_CurrPageCache_def_clear_btn" value="清除" />';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        spthtml += '<tr>';
        spthtml += '<td class="tdleft">定义：</td>';
        spthtml += '<td>';
        spthtml += "<ul id='tp_CurrPageCache_def_tree' class='ztree'></ul>";
        spthtml += '<textarea rows="2" style="width:475px;display:none;" wrap="off" id="tp_CurrPageCache_def_text" name="tp_CurrPageCache_def_text"></textarea>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        //来自当前收集的数据: g_current(用于#sep标签在遇到楼层时候增加楼号，以及把当前楼号放到g_current中去)
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">获取g_current变量：</td>';
        spthtml += '<td>';
        spthtml += '<input type="button" id="tp_g_current_def_btn" value="获取g_current变量" />';
        spthtml += '&nbsp;&nbsp;<input type="button" id="tp_g_current_def_clear_btn" value="清除" />';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        spthtml += '<tr>';
        spthtml += '<td class="tdleft">定义：</td>';
        spthtml += '<td>';
        spthtml += "<ul id='tp_g_current_def_tree' class='ztree'></ul>";
        spthtml += '<textarea rows="2" style="width:475px;display:none;" wrap="off" id="tp_g_current_def_text" name="tp_g_current_def_text"></textarea>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';


        //用于定义任务本身的属性 taskPro
        spthtml += '<tr style="text-align:center;">';
        spthtml += '<td colspan="3">任务本身属性<div class="divhr"></div></td>';
        spthtml += '</tr>';
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">任务级别：</td>';
        spthtml += '<td><select id="tp_tasklevel" name="tasklevel" class="shortselect">';
        spthtml += '<option value="1">一</option>';
        spthtml += '<option value="2">二</option>';
        spthtml += '<option value="3">三</option>';
        spthtml += '<option value="4">四</option>';
        spthtml += '<option value="5">五</option>';
        spthtml += '</select></td>';
        spthtml += '<td class="tdtip">数字越小级别越高</td>';
        spthtml += '</tr>';


        spthtml += '<tr>';
        spthtml += '<td class="tdleft">任务类型：</td>';
        spthtml += '<td><input type="text"  name="taskclassify" id="tp_taskclassify"  />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;应用于子任务：';
        spthtml += '<input type="radio" value="true" name="taskchild" id="tp_taskchild1" checked="checked" /><label for="tp_taskchild1">是</label>';
        spthtml += '<input type="radio" value="false" name="taskchild" id="tp_taskchild0" /><label for="tp_taskchild0">否</label>';
        spthtml += '</td>';
        spthtml += '</tr>';

        spthtml += '<tr>';
        spthtml += '<td class="tdleft">指定MAC：</td>';
        spthtml += '<td><input type="text" name="spcfdmac" id="tp_spcfdmac"  />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;应用于子任务：';
        spthtml += '<input type="radio" value="true" name="macchild" id="tp_macchild1" checked="checked" /><label for="tp_macchild1">是</label>';
        spthtml += '<input type="radio" value="false" name="macchild" id="tp_macchild0" /><label for="tp_macchild0">否</label>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip">例如：8C-7B-9D-43-50-89</td>';
        spthtml += '</tr>';

        spthtml += '<tr>';
        spthtml += '<td class="tdleft">立即更新：</td>';
        spthtml += '<td><input type="radio" value="1" name="iscommit" id="tp_iscommit1" checked="checked" /><label for="tp_iscommit1">是</label>';
        spthtml += '<input type="radio" value="0" name="iscommit" id="tp_iscommit0" /><label for="tp_iscommit0">否</label>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip">每次立即更新检索引擎数据</td>';
        spthtml += '</tr>';
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">是否计算轨迹：</td>';
        spthtml += '<td>';
        spthtml += '<input type="radio" id="tp_iscalctrend1" value="1" name="iscalctrend"><label for="tp_iscalctrend1">是</label>';
        spthtml += '<input type="radio" id="tp_iscalctrend0" value="0" name="iscalctrend" checked="checked"><label for="tp_iscalctrend0">否</label>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip">任务结束时是否计算轨迹</td>';
        spthtml += '</tr>';
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">摘 要：</td>';
        spthtml += '<td><textarea name="remarks" id="tp_remarks" rows="5" cols="7"></textarea></td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        /*
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">网页模板：</td>';
        spthtml += '<td>';
        spthtml += '<select id="tp_template" name="template">';
        spthtml += '</select>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';
        */

        //URL
        spthtml += '<tr id="tp13_1">';
        spthtml += '<td class="tdleft">URL生成方式：</td>';
        spthtml += '<td>';
        spthtml += '<input type="radio" id="tp_urltype0" value="consts" name="urltype" checked="checked"><label for="tp_urltype0">常量赋值</label>';
        spthtml += '<input type="radio" id="tp_urltype1" value="gen" name="urltype"><label for="tp_urltype1">根据规则生成(Enum, Object)</label>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip">URL的生成方式</td>';
        spthtml += '</tr>';
        spthtml += '<tr id="tp13_2">';
        spthtml += '<td class="tdleft">URL：</td>';
        spthtml += '<td>';
        spthtml += '<input type="text" style="width:475px" id="tp_listweburl" />';
        spthtml += '<input type="button" id="tp_datafrom_btn" value="添加变量" />';
        spthtml += '<input type="button" id="tp_datafrom_clear_btn" value="清除" />';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';
        /*
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">url规则：</td>';
        spthtml += '<td>';
        spthtml += '<span id="tp_listurlrule" style="word-break: break-all;display:inline-block;"></span>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';
        */
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">设置模板：</td>';
        spthtml += '<td>';
        spthtml += '<input type="button" id="tp_grabsteps_btn" value="设置模板" />';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">步骤：</td>';
        spthtml += '<td>';
        spthtml += '<span id="tp_grabsteps" style="word-break: break-all;display:inline-block;"></span>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';


        //添加分词方案
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">分词方案：</td>';
        spthtml += '<td>';
        spthtml += '<input type="button" value="设置" onclick="popPlan2(\'commonTask\');" id="tp_dictionaryPlan2" /></td>';
        spthtml += '<input type="hidden" name="dictionaryPlan" id="tph_dictionaryPlan2" value="[[]]"/>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip">系统对每个方案的分词结果去重后保存,未配置则用默认词典。</td>';
        spthtml += '</tr>';
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">已添加：</td>';
        spthtml += '<td>';
        spthtml += '<span class="selwordsbox" id="tp_dictionaryPlanText2">默认方案</span></td>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">任务超时：</td>';
        spthtml += '<td>';
        spthtml += '<input id="tp_duration" name="duration" type="text" value="3600" style="width:150px;" />秒';
        spthtml += '</td>';
        spthtml += '<td class="tdtip">完成任务的预期时间</td>';
        spthtml += '</tr>';
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">启动时间：</td>';
        spthtml += '<td><input id="tp_activatetime" name="cronstart" class="Wdate" type="text" readonly="readonly" style="width:150px;" onclick="WdatePicker({minDate:\'%y-%M-%d\',dateFmt:\'yyyy-MM-dd HH:mm:ss\'})" /></td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">结束时间：</td>';
        spthtml += '<td><input id="tp_endtime" name="cronend" class="Wdate" type="text" readonly="readonly" style="width:150px;" onclick="WdatePicker({minDate:\'%y-%M-%d\',dateFmt:\'yyyy-MM-dd HH:mm:ss\'})" /></td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">定时计划：</td>';
        spthtml += '<td><input id="tp_crontimebtn" name="crontimebtn" type="button"  value="定时计划" /><input id="tp_crontime" name="crontime" type="hidden"/>重复规则：<span id="tp_crontimedes"></span></td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">冲突延迟：</td>';
        spthtml += '<td>';
        spthtml += '<input id="tp_conflictdelay" name="conflictdelay" type="text" />秒';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">数据入库：</td>';
        spthtml += '<td><input type="radio" value="1" name="dataSave" id="tp_dataSave1" checked="checked" /><label for="tp_dataSave1">是</label>';
        spthtml += '<input type="radio" value="0" name="dataSave" id="tp_dataSave0" /><label for="tp_dataSave0">否</label>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip">当前任务的抓取数据是否入库</td>';
        spthtml += '</tr>';

        //spthtml += '<tr>';
        //spthtml += '<td class="tdleft">提交数据地址：</td>';
        //spthtml += '<td><select id="tp_submiturl" name="submiturl">';
        //spthtml += '<option value="0">mongodb</option>';
        //spthtml += '<option value="1">旅游商品</option>';
        //spthtml += '<option value="2">旅游商品评论</option>';
        //spthtml += '<option value="3">旅游景区</option>';
        //spthtml += '<option value="4">旅游景区评论</option>';
        //spthtml += '<option value="5">旅游游记</option>';
        //spthtml += '<option value="6">旅游游记评论</option>';
        //spthtml += '</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;应用于子任务：';
        //spthtml += '<input type="radio" value="true" name="submiturlchild" id="tp_submiturlchild1" checked="checked" /><label for="tp_submiturlchild1">是</label>';
        //spthtml += '<input type="radio" value="false" name="submiturlchild" id="tp_submiturlchild0" /><label for="tp_submiturlchild0">否</label>';
        //spthtml += '</td>';
        //spthtml += '<td class="tdtip"></td>';
        //spthtml += '</tr>';

        spthtml += '<tr>';
        spthtml += '<td class="tdleft">过滤HTML标签：</td>';
        spthtml += '<td><input type="radio" value="1" name="filPageTag" id="tp_filPageTag1" checked="checked" /><label for="tp_filPageTag1">是</label>';
        spthtml += '<input type="radio" value="0" name="filPageTag" id="tp_filPageTag0"/><label for="tp_filPageTag0">否</label>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip">每次立即更新检索引擎数据</td>';
        spthtml += '</tr>';
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">添加用户：</td>';
        spthtml += '<td><input type="radio" value="1" name="addUser" id="tp_addUser1" checked="checked" /><label for="tp_addUser1">是</label>';
        spthtml += '<input type="radio" value="0" name="addUser" id="tp_addUser0"/><label for="tp_addUser0">否</label>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip">抓取到的用户数据是否入库</td>';
        spthtml += '</tr>';

        spthtml += '<tr>';
        spthtml += '<td class="tdleft">默认生成时间字段：</td>';
        spthtml += '<td><input type="radio" value="1" name="genCreatedAt" id="tp_genCreatedAt1" checked="checked" /><label for="tp_genCreatedAt1">是</label>';
        spthtml += '<input type="radio" value="0" name="genCreatedAt" id="tp_genCreatedAt0"/><label for="tp_genCreatedAt0">否</label>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip">如果采集的数据没有created_at字段，系统会以采集时间作为文章的创建时间</td>';
        spthtml += '</tr>';

        spthtml += '<tr>';
        spthtml += '<td class="tdleft">是否派生子任务：</td>';
        spthtml += '<td><input type="radio" value="1" name="isGenChildTask" id="tp_isGenChildTask1" checked="checked" /><label for="tp_isGenChildTask1">是</label>';
        spthtml += '<input type="radio" value="0" name="isGenChildTask" id="tp_isGenChildTask0"/><label for="tp_isGenChildTask0">否</label>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';


        //过滤条件
        spthtml += '<tr style="text-align:center;">';
        spthtml += '<td colspan="3">抓取所需帐号<div class="divhr"></div></td>';
        spthtml += '</tr>';
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">帐号来源：</td>';
        spthtml += '<td><input type="button" value="选择源" id="tp_sourceid" /></td>';
        spthtml += '<td class="tdtip">先指定源再选择帐号</td>';
        spthtml += '</tr>';
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">已添加：</td>';
        spthtml += '<td colspan="2"><span id="tp_addedsourceid"></span><input type="hidden" id="tp_hdaddedsourceid" name="source" /></td>';
        spthtml += '</tr>';
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">抓取帐号：</td>';
        spthtml += '<td><input type="button" id="tp_accountid" value="添加"></td>';
        spthtml += '<td class="tdtip">对应源下可用抓取帐号,未添加帐号时需在抓取客户端输入用户名密码</td>';
        spthtml += '</tr>';
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">已添加：</td>';
        spthtml += '<td colspan="2"><span id="tp_addedaccountid"></span><input type="hidden" id="tp_hdaddedaccountid" name="accountid" /></td>';
        spthtml += '</tr>';
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">使用全局帐号：</td>';
        spthtml += '<td>';
        spthtml += '<input type="radio" id="tp_globalaccount1" value="1" name="tp_globalaccount"><label for="tp_globalaccount1">是</label>';
        spthtml += '<input type="radio" id="tp_globalaccount0" value="0" name="tp_globalaccount" checked="checked"><label for="tp_globalaccount0">否</label>';
        spthtml += '</td>';
        spthtml += '</tr>';
        spthtml += '<tr id="tp10_8">';
        spthtml += '<td class="tdleft">是否切换帐号：</td>';
        spthtml += '<td>';
        spthtml += '<input type="radio" id="tp_isswitch1" value="1" name="tp_isswitch" ><label for="tp_isswitch1">是</label>';
        spthtml += '<input type="radio" id="tp_isswitch0" value="0" name="tp_isswitch" checked="checked"><label for="tp_isswitch0">否</label>';
        spthtml += '</td>';
        spthtml += '</tr>';
        spthtml += '<tr id="tp10_12">';
        spthtml += '<td class="tdleft">退出登录：</td>';
        spthtml += '<td>';
        spthtml += '<input type="radio" id="tp_logoutfirst1" value="1" name="tp_logoutfirst"><label for="tp_logoutfirst1">是</label>';
        spthtml += '<input type="radio" id="tp_logoutfirst0" value="0" name="tp_logoutfirst" checked="checked"><label for="tp_logoutfirst0">否</label>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip">任务开始前退出当前登录账号</td>';
        spthtml += '</tr>';
        spthtml += '<tr id="tp10_9" style="display:none;">';
        spthtml += '<td class="tdleft">帐号切换策略：</td>';
        spthtml += '<td><input type="text" id="tp_switchpage" name="switchpage" value="5" class="shortinput">页/<input type="text" id="tp_switchtime" name="switchtime" value="0" class="shortinput">秒</td>';
        spthtml += '<td class="tdtip">帐号切换频率,0秒表示不限时间</td>';
        spthtml += '</tr>';
        //过滤条件
        spthtml += '<tr style="text-align:center;">';
        spthtml += '<td colspan="3">过滤条件<div class="divhr"></div></td>';
        spthtml += '</tr>';

        spthtml += '<tr>';
        spthtml += '<td class="tdleft">过滤条件赋值：</td>';
        spthtml += '<td>';
        spthtml += '<input type="button" id="tp_paramfilter_btn" value="过滤条件赋值" />';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">过滤条件：</td>';
        spthtml += '<td>';
        spthtml += '<span id="tp_paramfilter_span" style="word-break: break-all;display:inline-block;"></span>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        //派生任务规则
        spthtml += '<tr id="tp11_0" style="text-align:center;display:none;">';
        spthtml += '<td colspan="3">派生任务规则<div class="divhr"></div></td>';
        spthtml += '</tr>';

        //拆分步长
        spthtml += '<tr id="tp11_1" style="display:none;">';
        spthtml += '<td class="tdleft">拆分步长：</td>';
        spthtml += '<td>';
        spthtml += '<input id="tp_splitStep" name="tp_splitStep" type="text" />';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';
        /*
        spthtml += '<tr>';
        spthtml += '<td class="tdleft">url生成配置：</td>';
        spthtml += '<td>';
        spthtml += '<input type="text" style="width:475px;" id="tp_urlconfigrule_text" />';
        spthtml += '<input type="button" id="tp_urlconfigrule_btn" value="添加变量" />';
        spthtml += '<input type="button" id="tp_urlconfigrule_clear" value="清除" />';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';
        */
        spthtml += '<tr id="tp11_2" style="display:none;">';
        spthtml += '<td class="tdleft">参数传递：</td>';
        spthtml += '<td>';
        spthtml += '<br/>';
        spthtml += '<input id="tp_fromPath_text" name="tp_fromPath_text" type="text"  /> <input id="tp_fromPath_btn" name="tp_fromPath_btn" type="button" value="获取参数路径" />';
        spthtml += '<br/>';
        spthtml += '<input id="tp_toPath_text" name="tp_toPath_text" type="text" /> <input id="tp_toPath_btn" name="tp_toPath_btn" type="button" value="赋值参数路径" />';
        //目标类型
        spthtml += '<div id="tp_targetType_div" style="display:none;">';
        spthtml += '<input type="radio" id="tp_targetType0" value="0" name="tp_targetType" checked="checked"><label for="tp_targetType0">目标子任务</label>';
        spthtml += '<input type="radio" id="tp_targetType1" value="1" name="tp_targetType"><label for="tp_targetType1">目标父任务</label>';
        spthtml += '</div>';
        spthtml += '<br/>';
        spthtml += '<input id="tp_params_add" name="tp_params_add" type="button" value="添加" />';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        spthtml += '<tr id="tp11_3" style="display:none;">';
        spthtml += '<td class="tdleft">参数：</td>';
        spthtml += '<td>';
        spthtml += '<span id="tp_taskGenConf_params" ></span>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        spthtml += '<tr id="tp11_4" style="display:none;">';
        spthtml += '<td class="tdleft">URL生成方式：</td>';
        spthtml += '<td>';
        spthtml += '<input type="radio" id="tp_genconf_urltype0" value="consts" name="genconf_urltype" checked="checked"><label for="tp_genconf_urltype0">常量赋值</label>';
        spthtml += '<input type="radio" id="tp_genconf_urltype1" value="gen" name="genconf_urltype"><label for="tp_genconf_urltype1">根据规则生成(Enum, Object)</label>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip">URL的生成方式</td>';
        spthtml += '</tr>';
        spthtml += '<tr id="tp11_5" style="display:none;">';
        spthtml += '<td class="tdleft">URL：</td>';
        spthtml += '<td>';
        spthtml += '<input type="text" style="width:475px" id="tp_genconf_listweburl" />';
        spthtml += '<input type="button" id="tp_genconf_datafrom_btn" value="添加变量" />';
        spthtml += '<input type="button" id="tp_genconf_datafrom_clear_btn" value="清除" />';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        //参数路径
        spthtml += '<tr id="tp12_0" style="text-align:center;">';
        spthtml += '<td colspan="3">参数路径<div class="divhr"></div></td>';
        spthtml += '</tr>';

        spthtml += '<tr id="tp12_1">';
        spthtml += '<td class="tdleft">参数路径：</td>';
        spthtml += '<td>';
        spthtml += '<input id="tp_paramMap_btn" name="tp_paramMap_btn" type="button" value="添加路径" />';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        spthtml += '<tr id="tp11_3">';
        spthtml += '<td class="tdleft">参数：</td>';
        spthtml += '<td>';
        spthtml += '<span id="tp_paramMap_params" ></span>';
        spthtml += '</td>';
        spthtml += '<td class="tdtip"></td>';
        spthtml += '</tr>';

        spthtml += '</table>';
        spthtml += '</form>';
        spthtml += '</div>';
        $("body").append(spthtml);

        $("#divaddcommonspt").dialog({
            autoOpen: false,
            modal:true,
            height:900,
            width:950
        });
        $("#divaddcommonspt").dialog("open");
        $("#divaddcommonspt").dialog("option", "buttons", {
            "确定": function () {
                var taskid = $("#divaddcommonspt").attr("taskid");
                _this.getTaskParam(taskid);
            },
            "取消": function () {
                $(this).dialog("close");
            }
        });
        $("input[name=tp_isroottask]").bind("click", function(){
            var chkval = parseInt($("input[name=tp_isroottask]:checked").val(), 10);
            if(chkval === 0){
                $("#tp12_2").show();
                $("tr[id^=tp11_]").show();
                $("tr[id^=tp13_]").hide();
                var toption = '<option value="-1">未选择</option>';
                for(var i=0,ilen=_this.taskInstance.tasks.length;i<ilen;i++){
                    toption += '<option value="'+i+'">任务'+i+'</option>';
                }
                $("#tp_parenttask").empty().append(toption);
            }
            else{
                $("#tp12_2").hide();
                $("tr[id^=tp11_]").hide();
                $("tr[id^=tp13_]").show();
            }
        });
        $("#tp_parenttask").bind("change", function(){
            var ptaskid = $(this).val();
            //获取父级的所有参数
            var parentChoiceParam = {};
            $(_this.taskInstance.tasks).each(function(i,v){
                if(v.taskID == ptaskid){
                    parentChoiceParam.constants_def = v.param.constants_def;
                    parentChoiceParam.paramsDef_def = v.param.paramsDef_def;
                    parentChoiceParam.parentParam_def = v.param.parentParam_def;
                    parentChoiceParam.runTimeParam_def = v.param.runTimeParam_def;
                    parentChoiceParam.outData_def = v.param.outData_def;
                    parentChoiceParam.g_global_def = v.param.g_global_def;
                    parentChoiceParam.URLCache_def = v.param.URLCache_def;
                    parentChoiceParam.TaskCache_def = v.param.TaskCache_def;
                    parentChoiceParam.AppCache_def = v.param.AppCache_def;
                    parentChoiceParam.g_collect_def = v.param.g_collect_def;
                    parentChoiceParam.CurrPageCache_def = v.param.CurrPageCache_def;
                    parentChoiceParam.g_current_def = v.param.g_current_def;
                    return false;
                }
            });
            $("#tp_fromPath_btn").attr('choiceparam', JSON.stringify(parentChoiceParam));
        });
        //账号联动表单
        $("input[name=tp_globalaccount]").bind("click", function(){
            var sltval = $("input[name=tp_globalaccount]:checked").val();
            if(parseInt(sltval, 10)){
                var children = $("#tp_addedaccountid").find(".useritem").length; 
                if(children > 0){
                    alert("使用全局帐号时不用添加帐号!");
                    return false;
                }
                $("input[name=tp_isswitch][value=1]").attr("checked", true);
                //是否切换帐号
                $("#tp10_8").hide();
                $("#tp10_12").hide();
                $("input[name=tp_logoutfirst]").attr("checked", false);
                $("input[name=tp_logoutfirst][value=1]").attr("checked", true);

                //切换帐号策略
                $("#tp10_9").show();
            }
            else{
                $("input[name=tp_isswitch][value=0]").attr("checked", true);
                //是否切换帐号
                $("#tp10_8").show();
                $("#tp10_12").show();
                //切换帐号策略
                $("#tp10_9").hide();
            }
        });
        $("input[name=tp_isswitch]").bind("click", function(){
            var sltval = $("input[name=tp_isswitch]:checked").val();
            var sltga = $("input[name=tp_globalaccount]:checked").val();
            if(parseInt(sltval, 10)){
                var children = $("#tp_addedaccountid").find(".useritem").length; 
                //没有添加帐号,并且不使用全局帐号时
                if(children == 0 && parseInt(sltga, 10) == 0){
                    $("input[name=tp_isswitch]").attr("checked", false);		
                    $("input[name=tp_isswitch][value=0]").attr("checked", true);		
                    alert("请添加抓取帐号!");
                    return false;
                }
                else{
                    //切换帐号策略
                    $("#tp10_9").show();
                    //退出登录
                    $("#tp10_12").hide();
                    $("input[name=tp_logoutfirst]").attr("checked", false);
                    $("input[name=tp_logoutfirst][value=1]").attr("checked", true);
                }
            }
            else{
                var children = $("#tp_addedaccountid").find(".useritem").length; 
                if(children > 1){
                    $("input[name=tp_isswitch]").attr("checked", false);		
                    $("input[name=tp_isswitch][value=1]").attr("checked", true);		
                    alert("不使用切换时最多选一个账号!");
                    return false;
                }
                else{
                    //切换帐号策略
                    $("#tp10_9").hide();
                    //退出登录
                    $("#tp10_12").show();
                }
            }
        });
        //URL
        /*
        $("#tp_listweburl").bind("click", function(){
            var listtpl;
            listtpl = $("#tp_template").val();
            var urltpl = {};
            $.each(config.allSpiderConfig, function(ci, citem){
                if(citem.id == listtpl){
                    urltpl = citem.urlconfigrule;
                    return false;
                }
            });
            var selectedVal = {};
            if($("#tp_listweburl").attr("urlconfigrule") != undefined){
                selectedVal = jQuery.parseJSON($("#tp_listweburl").attr("urlconfigrule"));
            }
            var choiceParam = {};
            choiceParam.constants_def = jQuery.parseJSON($("#tp_constants_def_text").val());
            choiceParam.paramsDef_def = jQuery.parseJSON($("#tp_paramsDef_def_text").val());
            choiceParam.parentParam_def = jQuery.parseJSON($("#tp_parentParam_def_text").val());
            choiceParam.runTimeParam_def = jQuery.parseJSON($("#tp_runTimeParam_def_text").val());
            choiceParam.outData_def = jQuery.parseJSON($("#tp_outData_def_text").val());
            myURLConfigure(urltpl, function(data){
                $("#tph_listweburl").val(JSON.stringify(data.urls));
                $("#tp_listweburl").attr("urlconfigrule", JSON.stringify(data.urlconfigrule));
                $("#tp_listurlrule").text(JSON.stringify(data.urls));
            }, selectedVal, "配置URL", choiceParam);
        });
        */
        $("#tp_datafrom_btn").bind("click", function(){
            var choiceParam = {};
            choiceParam.constants_def = jQuery.parseJSON($("#tp_constants_def_text").val());
            choiceParam.paramsDef_def = jQuery.parseJSON($("#tp_paramsDef_def_text").val());
            choiceParam.parentParam_def = jQuery.parseJSON($("#tp_parentParam_def_text").val());
            choiceParam.runTimeParam_def = jQuery.parseJSON($("#tp_runTimeParam_def_text").val());
            choiceParam.outData_def = jQuery.parseJSON($("#tp_outData_def_text").val());

            choiceParam.g_global_def = jQuery.parseJSON($("#tp_g_global_def_text").val());
            choiceParam.URLCache_def = jQuery.parseJSON($("#tp_URLCache_def_text").val());
            choiceParam.TaskCache_def = jQuery.parseJSON($("#tp_TaskCache_def_text").val());
            choiceParam.AppCache_def = jQuery.parseJSON($("#tp_AppCache_def_text").val());
            choiceParam.g_collect_def = jQuery.parseJSON($("#tp_g_collect_def_text").val());
            choiceParam.CurrPageCache_def = jQuery.parseJSON($("#tp_CurrPageCache_def_text").val());
            choiceParam.g_current_def = jQuery.parseJSON($("#tp_g_current_def_text").val());
            if(choiceParam){
                myParamPath.init(choiceParam, function(data){
                    var taskurl = $("#tp_listweburl").val();
                    var paths_str = $("#tp_listweburl").attr("paths");
                    if(paths_str){
                        var paths_arr = paths_str ? jQuery.parseJSON(paths_str) : [];
                        var tmpobj = {};
                        tmpobj.pathid = data.paramPathId;
                        tmpobj.path = data.paramPath;
                        paths_arr.push(tmpobj);
                    }
                    else{
                        var paths_arr = [];
                        var tmpobj = {};
                        tmpobj.pathid =data.paramPathId;
                        tmpobj.path = data.paramPath;
                        paths_arr.push(tmpobj);
                    }
                    $("#tp_listweburl").attr("paths", JSON.stringify(paths_arr));
                    $("#tp_listweburl").val(taskurl+"|"+data.paramPathId+"|");
                });
            }
            else{
                alert('请先进行参数定义');
            }
        });
        $("#tp_datafrom_clear_btn").unbind("click");
        $("#tp_datafrom_clear_btn").bind("click", function(){
            $("#tp_listweburl").val("");
        });
        $("#tp_genconf_datafrom_btn").bind("click", function(){
            /*
            var choiceParam = {};
            choiceParam.constants_def = jQuery.parseJSON($("#tp_constants_def_text").val());
            choiceParam.paramsDef_def = jQuery.parseJSON($("#tp_paramsDef_def_text").val());
            choiceParam.parentParam_def = jQuery.parseJSON($("#tp_parentParam_def_text").val());
            choiceParam.runTimeParam_def = jQuery.parseJSON($("#tp_runTimeParam_def_text").val());
            choiceParam.outData_def = jQuery.parseJSON($("#tp_outData_def_text").val());

            choiceParam.g_global_def = jQuery.parseJSON($("#tp_g_global_def_text").val());
            choiceParam.URLCache_def = jQuery.parseJSON($("#tp_URLCache_def_text").val());
            choiceParam.TaskCache_def = jQuery.parseJSON($("#tp_TaskCache_def_text").val());
            choiceParam.AppCache_def = jQuery.parseJSON($("#tp_AppCache_def_text").val());
            choiceParam.g_collect_def = jQuery.parseJSON($("#tp_g_collect_def_text").val());
            choiceParam.CurrPageCache_def = jQuery.parseJSON($("#tp_CurrPageCache_def_text").val());
            choiceParam.g_current_def = jQuery.parseJSON($("#tp_g_current_def_text").val());
            */
            var parentChoiceParam = $("#tp_fromPath_btn").attr('choiceparam') ? jQuery.parseJSON($("#tp_fromPath_btn").attr('choiceparam')) : null;
            if(parentChoiceParam){
                myParamPath.init(parentChoiceParam, function(data){
                    var taskurl = $("#tp_genconf_listweburl").val();
                    var paths_str = $("#tp_genconf_listweburl").attr("paths");
                    if(paths_str){
                        var paths_arr = paths_str ? jQuery.parseJSON(paths_str) : [];
                        var tmpobj = {};
                        tmpobj.pathid = data.paramPathId;
                        tmpobj.path = data.paramPath;
                        paths_arr.push(tmpobj);
                    }
                    else{
                        var paths_arr = [];
                        var tmpobj = {};
                        tmpobj.pathid =data.paramPathId;
                        tmpobj.path = data.paramPath;
                        paths_arr.push(tmpobj);
                    }
                    $("#tp_genconf_listweburl").attr("paths", JSON.stringify(paths_arr));
                    $("#tp_genconf_listweburl").val(taskurl+"|"+data.paramPathId+"|");
                });
            }
            else{
                alert('请先进行参数定义');
            }
        });
        $("#tp_genconf_datafrom_clear_btn").unbind("click");
        $("#tp_genconf_datafrom_clear_btn").bind("click", function(){
            $("#tp_genconf_listweburl").val("");
        });
        //设置模板
        $("#tp_grabsteps_btn").bind("click", function(){
            var checkedVal = [];
            $("#tp_grabsteps").find("span[name=step_span]").each(function(i, item){
                var tmpobj = {};
                tmpobj.tpl = $(item).attr("tpl");
                tmpobj.stepid = parseInt($(item).find('span[name=grabstep_id]').text(),10);
                tmpobj.steptpl = $(item).find('span[name=grabstep_tpl]').text();
                tmpobj.urlregex = $(item).find('span[name=grabstep_urlregex]').text();
                checkedVal.push(tmpobj);
            });
            myGrabSteps.init({
                checkedVal:checkedVal,
                successResult:function(data){
                    var spanhtml = "";
                    for(var i=0,ilen=data.length;i<ilen;i++){
                        var urltpl = data[i];
                        spanhtml += "<span class='selwordsbox'>";
                        spanhtml += "<span name='step_span' tpl='"+urltpl.tpl+"'><span><span>步骤ID：</span><span name='grabstep_id' >"+urltpl.stepid+"</span></span><br/><span><span>步骤模板：</span><span name='grabstep_tpl' >"+urltpl.steptpl+"</span></span><br/><span><span>匹配规则：</span><span name='grabstep_urlregex'>"+urltpl.urlregex+"</span></span></span>";
                        spanhtml += "</span>";
                    }
                    $("#tp_grabsteps").empty().append(spanhtml);
                    $("#tp_grabsteps").find("a[class=cancleitem]").unbind("click");
                    $("#tp_grabsteps").find("a[class=cancleitem]").bind("click", function(){
                        $(this).parent().remove();
                    });
                }
            });
        });
        //选择源
        $("#tp_sourceid").bind("click", function(){
            var selectedVal = [];
            $("#tp_addedsourceid .useritem").each(function(i, item){
                var tmpobj = {};
                tmpobj.name = $(item).text();
                tmpobj.code = $(item).attr("code")
                selectedVal.push(tmpobj);
            });

            var choiceVal = [];
            if(config.allAccountSource.length == 0){
                //同步ajax方法
                getAccountSource(function(data){
                    if(data){
                        config.allAccountSource = data;
                    }
                });
            }
            $.each(config.allAccountSource, function(si, sitem){
                var tmpobj = {};
                tmpobj['name'] = sitem['name'];
                tmpobj['code'] = sitem['id'];
                choiceVal.push(tmpobj);
            });
            myAccountSelect(function(data){
                if(data.length > 0){
                    var dhtml = "";
                    var codeArr = [];
                    $.each(data, function(di, ditem){
                        codeArr.push(ditem.code);
                        dhtml += "<span class='selwordsbox'><span class='useritem' code='"+ditem.code+"' >"+ditem.name+"</span><a class='useritem_a' onclick='cancelSelected(this)'>×</a></span>";
                    });
                    $("#tp_addedsourceid").empty().append(dhtml);
                    $("#tp_hdaddedsourceid").val(codeArr.toString());
                }
            }, selectedVal, choiceVal, false, undefined, undefined, undefined, false, "来源");
        });
        //抓取账号
        $("#tp_accountid").bind("click", function(){
            var choiceValue = [];
            var selectedVal = [];
            $("#tp_addedaccountid .useritem").each(function(i, item){
                var tmpobj = {};
                tmpobj.name = $(item).text();
                tmpobj.code = $(item).attr("code")
                selectedVal.push(tmpobj);
            });
            var sourceid = $("#tp_hdaddedsourceid").val();
            if(!sourceid){
                alert("请选择帐号来源!");
                return false;
            }
            var searchnameUrl = config.modelUrl + "spideraccount_model.php";
            var senddataobj = {
                account_sourceid: sourceid,
            type: "selectaccountbysourceid"
            }
            $.ajax({
                type: "GET",
            dataType: "json",
            async:false,
            url: searchnameUrl,
            data: senddataobj,
            success:function(data){
                $.each(data.datalist, function(di, ditem){
                    var tmpobj = {};
                    tmpobj.name = ditem.username;
                    tmpobj.code = ditem.id;
                    choiceValue.push(tmpobj);
                });
            }
            });
            myCommonSelect(choiceValue, function(data){
                var switchdisplay = $("#s10-8").css("display");
                if(switchdisplay != "none"){
                    //当选择的帐号大于1个时需要选中切换帐号
                    if(data.length > 1){ //须使用切换帐号
                        $("input[name=tp_isswitch]").attr("checked", false);
                        $("input[name=tp_isswitch][value=1]").attr("checked", true);
                        $("#tp10_9").show();
                    }
                    else if(data.length == 0){ //不使用切换帐号
                        $("input[name=tp_isswitch]").attr("checked", false);
                        $("input[name=tp_isswitch][value=0]").attr("checked", true);
                        $("#tp10_9").hide();
                    }
                }
                var dhtml = "";
                $.each(data, function(di, ditem){
                    dhtml += "<span class='selwordsbox'><span class='useritem' code='"+ditem.code+"' >"+ditem.name+"</span><a class='useritem_a' onclick='cancelSelected(this)'>×</a></span>";
                    if(ditem.sourceid != undefined){
                        $("#tp_hdaddedsourceid").val(ditem.sourceid);
                    }
                });
                $("#tp_addedaccountid").empty().append(dhtml);
            }, selectedVal, "选择帐号", undefined, undefined, false, undefined, true);
        });
        $("#tp_paramfilter_btn").unbind("click");
        $("#tp_paramfilter_btn").bind("click", function(){
            var choiceParam = {};
            //choiceParam.constants = jQuery.parseJSON($("#tp_constants_def_text").val());
            choiceParam.paramsDef_def = jQuery.parseJSON($("#tp_paramsDef_def_text").val());
            //choiceParam.parentParam = jQuery.parseJSON($("#tp_parentParam_def_text").val());
            //choiceParam.runTimeParam = jQuery.parseJSON($("#tp_runTimeParam_def_text").val());
            //choiceParam.outData = jQuery.parseJSON($("#tp_outData_def_text").val());
            if(choiceParam.paramsDef_def){
                myParamFilter.init(choiceParam, function(data){
                    var phtml = "<span class='selwordsbox'><span filter='"+JSON.stringify(data)+"'>"+data.fileterId+"</span><a class='useritem_a' onclick='cancelSelected(this)'>×</a></span>";
                    $("#tp_paramfilter_span").append(phtml);
                });
            }
            else{
                alert('请先进行参数定义');
            }
        });

        $.each(["constants", "paramsDef", "runTimeParam", "parentParam", "outData", "g_global", "URLCache", "TaskCache", "AppCache", "g_collect", "CurrPageCache", "g_current"], function(i, item){
            //参数定义
            if($("#tp_"+item+"_def_btn").length > 0){
                $("#tp_"+item+"_def_btn").unbind("click");
                $("#tp_"+item+"_def_btn").bind("click", function(){
                    var checkedVal = $("#tp_"+item+"_def_text").val();
                    if(checkedVal){
                        var treeObj = $.fn.zTree.getZTreeObj("tp_"+item+"_def_tree");
                        if(treeObj){
                            var checkedNodes = treeObj.transformToArray(treeObj.getNodes());
                            for(var i=0,ilen=checkedNodes.length;i<ilen;i++){
                                checkedNodes[i].children = undefined;
                            }
                        }
                    }
                    myParamDef.init(function(data, retNodes){
                        var zTreeObj;
                        var setting = {
                            view: {
                                selectedMulti: false
                            },
                            data: {
                                simpleData: { //这里是用的简单数据
                                    enable: true,
                                    idKey: "id",
                                    pIdKey: "pId",
                                    rootPId: 0
                                }
                            }
                        };
                        var zTreeNodes = retNodes;
                        zTreeObj = $.fn.zTree.init($("#tp_"+item+"_def_tree"), setting, zTreeNodes);
                        $("#tp_"+item+"_def_text").val(JSON.stringify(data));
                        $("#tp_"+item+"_assign_tree").empty();
                        $("#tp_"+item+"_assign_text").val('');
                    }, false, checkedNodes, item);
                });
            }
            if($("#tp_"+item+"_def_clear_btn").length > 0){
                $("#tp_"+item+"_def_clear_btn").unbind("click");
                $("#tp_"+item+"_def_clear_btn").bind("click", function(){
                    $("#tp_"+item+"_def_tree").empty();
                    $("#tp_"+item+"_def_text").val('');

                    $("#tp_"+item+"_assign_tree").empty();
                    $("#tp_"+item+"_assign_text").val('');

                    if(item == "outData"){
                        $("#tp_outData_path_tree").empty();
                        $("#tp_outData_path_text").val('');

                        $("#tp_userData_path_tree").empty();
                        $("#tp_userData_path_text").val('');
                    }
                });
            }
            //参数赋值
            if($("#tp_"+item+"_assign_btn").length > 0){
                $("#tp_"+item+"_assign_btn").unbind("click");
                $("#tp_"+item+"_assign_btn").bind("click", function(){
                    var paramdef = $("#tp_"+item+"_def_text").val();
                    if(paramdef){
                        var selectedVal = $("#tp_"+item+"_assign_text").val();
                        myParamGenerator(jQuery.parseJSON(paramdef), function(data, paramNodes){
                            var zTreeObj;
                            var setting = {
                                view: {
                                    selectedMulti: false
                                }
                            };
                            var zTreeNodes = paramNodes;
                            zTreeObj = $.fn.zTree.init($("#tp_"+item+"_assign_tree"), setting, zTreeNodes);
                            $("#tp_"+item+"_assign_text").val(JSON.stringify(data));
                        }, jQuery.parseJSON(selectedVal));
                    }
                    else{
                        alert('请先进行参数定义');
                    }
                });
            }
            if($("#tp_"+item+"_assign_clear_btn").length > 0){
                $("#tp_"+item+"_assign_clear_btn").unbind("click");
                $("#tp_"+item+"_assign_clear_btn").bind("click", function(){
                    $("#tp_"+item+"_assign_tree").empty();
                    $("#tp_"+item+"_assign_text").val('');
                });
            }
        });

        $("#tp_outData_path_btn").unbind("click");
        $("#tp_outData_path_btn").bind("click", function(){
            var choiceParam = {};
            //choiceParam.constants_def = jQuery.parseJSON($("#tp_constants_def_text").val());
            //choiceParam.paramsDef_def = jQuery.parseJSON($("#tp_paramsDef_def_text").val());
            //choiceParam.parentParam_def = jQuery.parseJSON($("#tp_parentParam_def_text").val());
            //choiceParam.runTimeParam_def = jQuery.parseJSON($("#tp_runTimeParam_def_text").val());
            choiceParam.outData_def = jQuery.parseJSON($("#tp_outData_def_text").val());
            if(choiceParam.outData_def){
                myParamPath.init(choiceParam, function(data){
                    var path = [];
                    _this.getDataPath(data.paramPath, path);
                    $("#tp_outData_path_tree").text('outData'+path.join(''));
                    $("#tp_outData_path_text").val(JSON.stringify(data));
                });
            }
            else{
                alert('请先进行参数定义');
            }
        });
        if($("#tp_outData_path_clear_btn").length > 0){
            $("#tp_outData_path_clear_btn").unbind("click");
            $("#tp_outData_path_clear_btn").bind("click", function(){
                $("#tp_outData_path_tree").empty();
                $("#tp_outData_path_text").val('');
            });
        }
        $("#tp_userData_path_btn").unbind("click");
        $("#tp_userData_path_btn").bind("click", function(){
            var choiceParam = {};
            choiceParam.outData_def = jQuery.parseJSON($("#tp_outData_def_text").val());
            if(choiceParam.outData_def){
                myParamPath.init(choiceParam, function(data){
                    var path = [];
                    _this.getDataPath(data.paramPath, path);
                    $("#tp_userData_path_tree").text('outData'+path.join(''));
                    $("#tp_userData_path_text").val(JSON.stringify(data));
                });
            }
            else{
                alert('请先进行参数定义');
            }
        });
        if($("#tp_userData_path_clear_btn").length > 0){
            $("#tp_userData_path_clear_btn").unbind("click");
            $("#tp_userData_path_clear_btn").bind("click", function(){
                $("#tp_userData_path_tree").empty();
                $("#tp_userData_path_text").val('');
            });
        }
        $("#tp_fromPath_btn").unbind("click");
        $("#tp_fromPath_btn").bind("click", function(){
            var parentChoiceParam = $(this).attr('choiceparam') ? jQuery.parseJSON($(this).attr('choiceparam')) : null;
            if(parentChoiceParam){
                /*获取最新的*/
                var ptaskid = $("#tp_parenttask").val();
                $(_this.taskInstance.tasks).each(function(i,v){
                    if(v.taskID == ptaskid){
                        parentChoiceParam.constants_def = v.param.constants_def;
                        parentChoiceParam.paramsDef_def = v.param.paramsDef_def;
                        parentChoiceParam.parentParam_def = v.param.parentParam_def;
                        parentChoiceParam.runTimeParam_def = v.param.runTimeParam_def;
                        parentChoiceParam.outData_def = v.param.outData_def;
                        parentChoiceParam.g_global_def = v.param.g_global_def;
                        parentChoiceParam.URLCache_def = v.param.URLCache_def;
                        parentChoiceParam.TaskCache_def = v.param.TaskCache_def;
                        parentChoiceParam.AppCache_def = v.param.AppCache_def;
                        parentChoiceParam.g_collect_def = v.param.g_collect_def;
                        parentChoiceParam.CurrPageCache_def = v.param.CurrPageCache_def;
                        parentChoiceParam.g_current_def = v.param.g_current_def;
                        return false;
                    }
                });

                myParamPath.init(parentChoiceParam, function(data){
                    if(data.paramPath.paramSource == 5){
                        $("#tp_targetType_div").show();
                    }
                    else{
                        $("#tp_targetType_div").hide();
                    }
                $("#tp_fromPath_text").attr("code", JSON.stringify(data));
                $("#tp_fromPath_text").val(data.paramPathId);
                });
            }
            else{
                alert('请先选择父任务!');
            }
        });
        $("#tp_toPath_btn").unbind("click");
        $("#tp_toPath_btn").bind("click", function(){
            var choiceParam = {};
            choiceParam.constants_def = jQuery.parseJSON($("#tp_constants_def_text").val());
            choiceParam.paramsDef_def = jQuery.parseJSON($("#tp_paramsDef_def_text").val());
            choiceParam.parentParam_def = jQuery.parseJSON($("#tp_parentParam_def_text").val());
            choiceParam.runTimeParam_def = jQuery.parseJSON($("#tp_runTimeParam_def_text").val());
            choiceParam.outData_def = jQuery.parseJSON($("#tp_outData_def_text").val());

            choiceParam.g_global_def = jQuery.parseJSON($("#tp_g_global_def_text").val());
            choiceParam.URLCache_def = jQuery.parseJSON($("#tp_URLCache_def_text").val());
            choiceParam.TaskCache_def = jQuery.parseJSON($("#tp_TaskCache_def_text").val());
            choiceParam.AppCache_def = jQuery.parseJSON($("#tp_AppCache_def_text").val());
            choiceParam.g_collect_def = jQuery.parseJSON($("#tp_g_collect_def_text").val());
            choiceParam.CurrPageCache_def = jQuery.parseJSON($("#tp_CurrPageCache_def_text").val());
            choiceParam.g_current_def = jQuery.parseJSON($("#tp_g_current_def_text").val());

            if(choiceParam){
                myParamPath.init(choiceParam, function(data){
                    $("#tp_toPath_text").attr("code", JSON.stringify(data));
                    $("#tp_toPath_text").val(data.paramPathId);
                });
            }
            else{
                alert('请先进行参数定义');
                return false;
            }
        });

        $("#tp_params_add").unbind("click");
        $("#tp_params_add").bind("click", function(){
            var fromStr = $("#tp_fromPath_text").attr("code");
            var fromObj = fromStr ? jQuery.parseJSON(fromStr) : null; 
            var toStr = $("#tp_toPath_text").attr("code");
            var toObj = toStr ? jQuery.parseJSON(toStr) : null; 
            var targetTypeAttr = "";
            if(fromObj.paramPath.paramSource == 5){
                var targetType = $("input[name=tp_targetType]:checked").val();
                targetTypeAttr = "targetType='"+targetType+"'";
            }
            var tmphtml = "<div name='paramPath'>";
            tmphtml += "<span name='paramPathfrom' code='"+fromStr+"' "+targetTypeAttr+">"+fromObj.paramPathId+"</span>";
            tmphtml += "->";
            tmphtml += "<span name='paramPathto' code='"+toStr+"'>"+toObj.paramPathId+"</span>";
            tmphtml += "<a class='cancleitem' style='color:red;cursor:pointer;' >X</a>";
            tmphtml += "</div>";
            $("#tp_taskGenConf_params").append(tmphtml);
            $("#tp_taskGenConf_params").find("a[class=cancleitem]").unbind("click");
            $("#tp_taskGenConf_params").find("a[class=cancleitem]").bind("click", function(){
                $(this).parent().remove();
            });

        });

        $("#tp_paramMap_btn").unbind("click");
        $("#tp_paramMap_btn").bind("click", function(){
            var choiceParam = {};
            choiceParam.constants_def = jQuery.parseJSON($("#tp_constants_def_text").val());
            choiceParam.paramsDef_def = jQuery.parseJSON($("#tp_paramsDef_def_text").val());
            choiceParam.parentParam_def = jQuery.parseJSON($("#tp_parentParam_def_text").val());
            choiceParam.runTimeParam_def = jQuery.parseJSON($("#tp_runTimeParam_def_text").val());
            choiceParam.outData_def = jQuery.parseJSON($("#tp_outData_def_text").val());

            choiceParam.g_global_def = jQuery.parseJSON($("#tp_g_global_def_text").val());
            choiceParam.URLCache_def = jQuery.parseJSON($("#tp_URLCache_def_text").val());
            choiceParam.TaskCache_def = jQuery.parseJSON($("#tp_TaskCache_def_text").val());
            choiceParam.AppCache_def = jQuery.parseJSON($("#tp_AppCache_def_text").val());
            choiceParam.g_collect_def = jQuery.parseJSON($("#tp_g_collect_def_text").val());
            choiceParam.CurrPageCache_def = jQuery.parseJSON($("#tp_CurrPageCache_def_text").val());
            choiceParam.g_current_def = jQuery.parseJSON($("#tp_g_current_def_text").val());

            if(choiceParam){
                myParamPath.init(choiceParam, function(data){
                    var tmphtml = "<div name='paramMap'>";
                    tmphtml += "<span name='paramMap' code='"+JSON.stringify(data)+"'>"+data.paramPathId+"</span>";
                    tmphtml += "<a class='cancleitem' style='color:red;cursor:pointer;' >X</a>";
                    tmphtml += "</div>";
                    $("#tp_paramMap_params").append(tmphtml);
                    $("#tp_paramMap_params").find("a[class=cancleitem]").unbind("click");
                    $("#tp_paramMap_params").find("a[class=cancleitem]").bind("click", function(){
                        $(this).parent().remove();
                    });
                }, true);
            }
            else{
                alert('请先进行参数定义');
                return false;
            }
        });

        /*
        $("#tp_urlconfigrule_clear").unbind("click");
        $("#tp_urlconfigrule_clear").bind("click", function(){
            $("#tp_urlconfigrule_text").val("");
            $("#tp_urlconfigrule_text").removeAttr("code");
        });
        $("#tp_urlconfigrule_btn").unbind("click");
        $("#tp_urlconfigrule_btn").bind("click", function(){
            myParamDef.init(function(data){
                var taskurl = $("#tp_urlconfigrule_text").val();
                var newurl = taskurl + "$<"+data.name+" \"%s\">";
                var taskStr = $("#tp_urlconfigrule_text").attr("code");
                var taskobj = taskStr ? jQuery.parseJSON(taskStr) : null;
                if(taskobj === null){
                    var taskobj = {};
                }
                taskobj.taskurl = newurl;
                taskobj[data.name] = {};
                taskobj[data.name].label= data.label;
                var dct = data.col_type;
                var datatype = "";
                switch(parseInt(dct, 10)){
                    case SE_TYPE_STRING:
                        datatype = "string";
                        break;
                    case SE_TYPE_INT32:
                        datatype = "int";
                        break;
                    case SE_TYPE_OBJECT:
                        datatype = "object";
                        break;
                    case SE_TYPE_ARRAY:
                        datatype = "string";
                        taskobj[data.name].multivalue = true;
                        break;
                    default:
                        break;
                }
                taskobj[data.name].datatype = datatype;
                $("#tp_urlconfigrule_text").attr("code", JSON.stringify(taskobj));
                $("#tp_urlconfigrule_text").val(newurl);
            });
        });
        */

        //用户选择的值还原
        if(taskele){
            if(taskele.taskID !== taskele.parentTaskID){
                $("input[name=isroottask]").attr("checked", false);
                $("#tp_isroottask0").attr("checked", true);
                $("#tp12_2").show();
                $("tr[id^=tp11_]").show();
                $("tr[id^=tp13_]").hide();
                var toption = '<option value="-1">未选择</option>';
                for(var i=0,ilen=_this.taskInstance.tasks.length;i<ilen;i++){
                    toption += '<option value="'+i+'">任务'+i+'</option>';
                }
                $("#tp_parenttask").empty().append(toption);
                $("#tp_parenttask").val(taskele.parentTaskID);
            }
            if(taskele.parentChoiceParam){
                $("#tp_fromPath_btn").attr('choiceparam', JSON.stringify(taskele.parentChoiceParam));
            }
            var taskp = taskele.param;
            var taskPro = taskp.taskPro;
            $("#tp_tasklevel").val(taskPro.tasklevel);
            //$("#tp_submiturl").val(taskPro.submiturl);
            //taskp.taskPro.local = 0;
            //taskp.taskPro.remote = 1;
            if(taskPro.iscommit !== undefined){
                $("input[name=iscommit]").attr("checked", false);
                $("input[name=iscommit][value="+taskPro.iscommit+"]").attr("checked", true);
            }
            //针对价格趋势抓取   2017/3/14   by   yu
            if(taskPro.creattask !== undefined){
                $("input[name=creattask]").attr("checked", false);
                $("input[name=creattask][value="+taskPro.creattask+"]").attr("checked", true);
            }

            if(taskPro.specifiedTypeForChild  !== undefined){
                $("input[name=taskchild]").attr("checked", false);
                $("input[name=taskchild][value="+taskPro.specifiedTypeForChild+"]").attr("checked", true);
            }
            if(taskPro.specifiedMacForChild  !== undefined){
                $("input[name=macchild]").attr("checked", false);
                $("input[name=macchild][value="+taskPro.specifiedMacForChild+"]").attr("checked", true);
            }
            //if(taskPro.submiturlForChild  !== undefined){
            //    $("input[name=submiturlchild]").attr("checked", false);
            //    $("input[name=submiturlchild][value="+taskPro.submiturlForChild+"]").attr("checked", true);
            //}

            //taskp.taskPro.source = 11;
            //taskp.taskPro.content_type = 0; //
            if(taskPro.duration !== undefined){
                $("#tp_duration").val(taskPro.duration); 
            }
            if(taskPro.conflictdelay){
                $("#tp_conflictdelay").val(taskPro.conflictdelay); 
            }
            if(taskPro.iscalctrend){
                $("input[name=iscalctrend]").attr("checked", false);
                $("input[name=iscalctrend][value="+taskPro.iscalctrend+"]").attr("checked", true);
            }
            if(taskPro.dataSave){
                $("input[name=dataSave]").attr("checked", false);
                $("input[name=dataSave][value="+taskPro.dataSave+"]").attr("checked", true);
            }
            if(taskPro.filPageTag){
                $("input[name=filPageTag]").attr("checked", false);
                $("input[name=filPageTag][value="+taskPro.filPageTag+"]").attr("checked", true);
            }
            if(taskPro.addUser){
                $("input[name=addUser]").attr("checked", false);
                $("input[name=addUser][value="+taskPro.addUser+"]").attr("checked", true);
            }
            if(taskPro.genCreatedAt){
                $("input[name=genCreatedAt]").attr("checked", false);
                $("input[name=genCreatedAt][value="+taskPro.genCreatedAt+"]").attr("checked", true);
            }
            if(taskPro.isGenChildTask){
                $("input[name=isGenChildTask]").attr("checked", false);
                $("input[name=isGenChildTask][value="+taskPro.isGenChildTask+"]").attr("checked", true);
            }

            if(taskPro.dictionaryPlan !== undefined && taskPro.dictionaryPlan){
                //数据放到全局数组里
                if(taskPro.dictionaryPlan instanceof Array) {
                    planArray=taskPro.dictionaryPlan;
                } else {
                    planArray=JSON.parse(taskPro.dictionaryPlan);
                }
                var planHtml="";
                var count=1;
                $.each(planArray,function(di, itemPlan){
                    planHtml +="方案"+count+":(";
                    var t2="";
                    var count2=0;
                    if(itemPlan!=undefined&&itemPlan.length==0){
                        if(count2!=0){
                            t2=t2+","+"默认方案";
                        }
                        else{
                            t2="默认方案";
                        }
                count2++;
                planHtml+=t2+")";
                count++;
                    }
                    else {
                        $.each(itemPlan, function (dj, itemCategory){
                            if(count2!=0)
                        {
                            t2=t2+","+itemCategory.name;
                        }
                            else{
                                t2=itemCategory.name;
                            }
                        count2++;
                        });
                        planHtml+=t2+")";
                        count++;
                    }
                });
                $("#tp_dictionaryPlanText2").text(planHtml);	
            }
            
			if(taskPro.cronstart !== undefined){
                $("#tp_activatetime").val(taskPro.cronstart); 
            }
			if(taskPro.cronend !== undefined){
                $("#tp_endtime").val(taskPro.cronend); 
            }
			if(taskPro.crontime !== undefined){
                $("#tp_crontime").val(taskPro.crontime);
				$("#tp_crontimedes").html(taskPro.crontimedes);
            }
			
            /*
            if(taskPro.template !== undefined){
                $("#tp_template").val(taskPro.template); 
            }
            */
            if(taskPro.remarks !== undefined){
                $("#tp_remarks").val(taskPro.remarks);
            }
            if(taskPro.specifiedType ){
                $("#tp_taskclassify").val(taskPro.specifiedType);
            }
            if(taskPro.specifiedMac){
                $("#tp_spcfdmac").val(taskPro.specifiedMac);
            }
            //if(taskPro.submiturl){
            //    $("#tp_submiturl").val(taskPro.submiturl);
            //}
            if(taskp.templMap){
                var spanhtml = "";
                for(var steptpl in taskp.templMap){
                    for(var i=0,ilen=taskp.templMap[steptpl].length;i<ilen;i++){
                        var stepNum = taskp.templMap[steptpl][i];
                        var tmpobj = {};
                        tmpobj.stepid = stepNum;
                        tmpobj.steptpl = steptpl;
                        var urltpl = {};
                        $.each(config.allSpiderConfig, function(ci, citem){
                            if(citem.id == taskPro.template){
                                urltpl = citem;
                                return false;
                            }
                        });
                        tmpobj.name = urltpl.name;
                        var reg = taskp.stepNumURLPatterns[stepNum];
                        tmpobj.urlregex = reg ? reg.join("\n") : null;
                        if(tmpobj.stepid){
                            spanhtml += "<span class='selwordsbox'>";
                            spanhtml += "<span name='step_span' tpl='"+taskPro.template+"'><span><span>步骤ID：</span><span name='grabstep_id' >"+tmpobj.stepid+"</span></span><br/><span><span>步骤模板：</span><span name='grabstep_tpl' >"+tmpobj.steptpl+"</span></span><br/><span><span>当前步骤校验规则：</span><span name='grabstep_urlregex'>"+tmpobj.urlregex+"</span></span></span>";
                            spanhtml += "</span>";
                        }
                    }
                }
                $("#tp_grabsteps").empty().append(spanhtml);
            }
            var constants = taskp.constants;
            if(constants){
                _this.initAssignText('constants', constants);
            }
            var paramsDef = taskp.paramsDef;
            if(paramsDef){
                _this.initAssignText('paramsDef', paramsDef);
            }
            var parentParam = taskp.parentParam;
            if(parentParam){
                _this.initAssignText('parentParam', parentParam);
            }
            var runTimeParam = taskp.runTimeParam;
            if(runTimeParam){
                _this.initAssignText('runTimeParam', runTimeParam);
            }
            var constants_def = taskp.constants_def;
            if(constants_def){
                _this.initDefText('constants', constants_def);
            }
            var paramsDef_def = taskp.paramsDef_def;
            if(paramsDef_def){
                _this.initDefText('paramsDef', paramsDef_def);
            }
            var parentParam_def = taskp.parentParam_def;
            if(parentParam_def){
                _this.initDefText('parentParam', parentParam_def);
            }
            var runTimeParam_def = taskp.runTimeParam_def;
            if(runTimeParam_def){
                _this.initDefText('runTimeParam', runTimeParam_def);
            }
            var outData_def = taskp.outData_def;
            if(outData_def){
                _this.initDefText('outData', outData_def);
            }

            var g_global_def = taskp.g_global_def;
            if(g_global_def){
                _this.initDefText('g_global', g_global_def);
            }

            var URLCache_def = taskp.URLCache_def;
            if(URLCache_def){
                _this.initDefText('URLCache', URLCache_def);
            }
            var TaskCache_def = taskp.TaskCache_def;
            if(TaskCache_def){
                _this.initDefText('TaskCache', TaskCache_def);
            }

            var AppCache_def = taskp.AppCache_def ;
            if(AppCache_def){
                _this.initDefText('AppCache', AppCache_def);
            }

            var g_collect_def = taskp.g_collect_def ;
            if(g_collect_def){
                _this.initDefText('g_collect', g_collect_def);
            }
            var CurrPageCache_def = taskp.CurrPageCache_def ;
            if(CurrPageCache_def){
                _this.initDefText('CurrPageCache', CurrPageCache_def);
            }
            var g_current_def = taskp.g_current_def ;
            if(g_current_def){
                _this.initDefText('g_current', g_current_def);
            }
            if(taskp.outData){
                if(taskp.outData.datasPath){
                    var outData_path = {};
                    outData_path.paramPathId = taskp.outData.datasPath.replace(/\|/g, ""); 
                    outData_path.paramPath = taskp.pathStructMap[outData_path.paramPathId]; 
                    $("#tp_outData_path_text").val(JSON.stringify(outData_path));
                    var path = [];
                    _this.getDataPath(outData_path.paramPath, path);
                    $("#tp_outData_path_tree").text('outData'+path.join(''));
                }
            }

            if(taskp.taskPro){
                if(taskp.taskPro.userPathId){
                    var userData_path = {};
                    userData_path.paramPathId = taskp.taskPro.userPathId.replace(/\|/g, ""); 
                    userData_path.paramPath = taskp.pathStructMap[userData_path.paramPathId]; 
                    $("#tp_userData_path_text").val(JSON.stringify(userData_path));
                    var path = [];
                    _this.getDataPath(userData_path.paramPath, path);
                    $("#tp_userData_path_tree").text('outData'+path.join(''));
                }
            }
            var loginAccounts = taskp.loginAccounts;
            if(loginAccounts.globalaccount !== undefined){
                $("input[name=tp_globalaccount]").attr("checked", false);
                $("input[name=tp_globalaccount][value="+loginAccounts.globalaccount+"]").attr("checked", true);
                if(parseInt(loginAccounts.globalaccount, 10)){
                    $("#tp10_8").hide();
                    $("#tp10_12").hide();
                    $("input[name=logoutfirst]").attr("checked", false);
                    $("input[name=logoutfirst][value=1]").attr("checked", true);
                }
            }
            if(loginAccounts.logoutfirst !== undefined){
                $("input[name=tp_logoutfirst]").attr("checked", false);
                $("input[name=tp_logoutfirst][value="+loginAccounts.logoutfirst+"]").attr("checked", true);
            }

            if(loginAccounts.isswitch !== undefined){
                $("input[name=tp_isswitch]").attr("checked", false);
                $("input[name=tp_isswitch][value="+loginAccounts.isswitch+"]").attr("checked", true);
                if(parseInt(loginAccounts.isswitch, 10)){
                    $("#tp10_9").show();
                }
            }
            if(loginAccounts.switchpage !== undefined){
                $("#tp_switchpage").val(loginAccounts.switchpage);
            }
            if(loginAccounts.switchtime !== undefined){
                $("#tp_switchtime").val(loginAccounts.switchtime);
            }
            if(loginAccounts.accounts != undefined && loginAccounts.accounts.length > 0){
                var uhtml = "";
                $.each(loginAccounts.accounts, function(di, ditem){
                    var accountname = getSpiderAccountName(loginAccounts.source, ditem);
                    uhtml += "<span class='selwordsbox'><span class='useritem' code='"+ditem+"' >"+accountname+"</span><a class='useritem_a' onclick='cancelSelected(this)'>×</a></span>";
                });
                $("#tp_addedaccountid").empty().append(uhtml);
            }
            if(loginAccounts.source !== undefined){
                var sourcename = getSourceName(loginAccounts.source);
                var uhtml = "";
                uhtml += "<span class='selwordsbox'><span class='useritem' code='"+loginAccounts.source+"' >"+sourcename+"</span><a class='useritem_a' onclick='cancelSelected(this)'>×</a></span>";
                $("#tp_addedsourceid").empty().append(uhtml);
            }
            //taskp.loginAccounts.switchstrategy = 1;
            //taskp.loginAccounts.globalaccounts = "";

            if(taskp.filters){
                $("#tp_paramfilter_span").empty();
                var phtml = "";
                for(var i=0;i<taskp.filters.length;i++){
                    var tmpFilter = taskp.filters[i];
                    phtml += "<span class='selwordsbox'><span filter='"+JSON.stringify(tmpFilter)+"'>"+tmpFilter.fileterId+"</span><a class='useritem_a' onclick='cancelSelected(this)'>×</a></span>";
                }
                $("#tp_paramfilter_span").append(phtml);
            }
            if(taskp.pathStructMap && taskp.pathStructMap.keys){
                for(var i=0,ilen=taskp.pathStructMap.keys.length;i<ilen;i++){
                    var pitem = taskp.pathStructMap.keys[i];
                    var pmap = taskp.pathStructMap[pitem];
                    var tmpobj = {};
                    tmpobj.paramPathId = pitem;
                    tmpobj.paramPath = pmap;
                    var tmphtml = "<div name='paramMap'>";
                    tmphtml += "<span name='paramMap' code='"+JSON.stringify(tmpobj)+"'>"+pitem+"</span>";
                    tmphtml += "<a class='cancleitem' style='color:red;cursor:pointer;' >X</a>";
                    tmphtml += "</div>";
                    $("#tp_paramMap_params").append(tmphtml);
                    $("#tp_paramMap_params").find("a[class=cancleitem]").unbind("click");
                    $("#tp_paramMap_params").find("a[class=cancleitem]").bind("click", function(){
                        $(this).parent().remove();
                    });
                }
            }
            var taskUrls = taskp.taskUrls;
            if(taskUrls){
                //gen:表示根据参数生成，consts表示直接赋值给爬虫
                $("input[name=urltype]").attr("checked", false);
                $("input[name=urltype][value="+taskUrls.type+"]").attr("checked", true);
                var urls = taskUrls.urlValues;
                var ids = [];
                var paths = [];
                for(var i=0;i<urls.length;i++){
                    var tmpid = urls[i];
                    ids.push(tmpid);
                    var structid = tmpid.replace(/\|/g, "");
                    var tmppath = {};
                    tmppath.pathid = structid;
                    tmppath.path = taskp.pathStructMap[structid];
                    paths.push(tmppath);
                }
                $("#tp_listweburl").val(ids.join(''));
                $("#tp_listweburl").attr("paths", JSON.stringify(paths));
            }
            var parentTask = {};
            for(var i=0, ilen=_this.taskInstance.tasks.length;i<ilen;i++){
                var tmpTask = _this.taskInstance.tasks[i];
                if(tmpTask.taskID == taskele.parentTaskID){
                    parentTask = tmpTask;
                }
            }
            if(parentTask.param.taskGenConf && parentTask.param.taskGenConf.length > 0){
                for(var i=0;i<parentTask.param.taskGenConf.length;i++){
                    var tgc = parentTask.param.taskGenConf[i];
                    if(tgc.childTaskDefId == taskele.taskID){
                        if(tgc.splitStep !== undefined){
                            $("#tp_splitStep").val(tgc.splitStep);
                        }
                        if(tgc.childTaskUrl){
                            $("input[name=genconf_urltype]").attr("checked", false);
                            $("input[name=genconf_urltype][value="+tgc.childTaskUrl.type+"]").attr("checked", true);
                            var urls = tgc.childTaskUrl.value;
                            if(!isEmptyObject(urls) && urls.length != 0){
                                $("#tp_genconf_listweburl").val(urls.paramValue);
                                var paths_arr = [];
                                var pathid = urls.paramValue.replace(/\|/g, "");
                                paths_arr.push({"pathid":pathid, "path":taskp.pathStructMap[pathid]});
                                var paths_str = JSON.stringify(paths_arr);
                                $("#tp_genconf_listweburl").attr("paths", paths_str);
                            }
                        }
                        if(tgc.params !== undefined && tgc.params.length > 0){
                            $("#tp_taskGenConf_params").empty();
                            for(var i=0,ilen=tgc.params.length;i<ilen;i++){
                                var tmpObj = tgc.params[i];
                                var tmp_from_path = {};
                                tmp_from_path.paramPathId = tmpObj.paramPath.replace(/\|/g, ""); 
                                tmp_from_path.paramPath = taskp.pathStructMap[tmp_from_path.paramPathId]; 
                                var targetTypeAttr = "";
                                if(tmpObj.targetType !== undefined){
                                    targetTypeAttr = "targetType='"+tmpObj.targetType+"'";
                                }
                                var tmp_to_path = {};
                                tmp_to_path.paramPathId = tmpObj.toParamPath.replace(/\|/g, ""); 
                                tmp_to_path.paramPath = taskp.pathStructMap[tmp_to_path.paramPathId]; 

                                var tmphtml = "<div name='paramPath'>";
                                tmphtml += "<span name='paramPathfrom' code='"+JSON.stringify(tmp_from_path)+"' "+targetTypeAttr+">"+tmp_from_path.paramPathId+"</span>";
                                tmphtml += "->";
                                tmphtml += "<span name='paramPathto' code='"+JSON.stringify(tmp_to_path)+"'>"+tmp_to_path.paramPathId+"</span>";
                                tmphtml += "<a class='cancleitem' style='color:red;cursor:pointer;' >X</a>";
                                tmphtml += "</div>";
                                $("#tp_taskGenConf_params").append(tmphtml);
                                $("#tp_taskGenConf_params").find("a[class=cancleitem]").unbind("click");
                                $("#tp_taskGenConf_params").find("a[class=cancleitem]").bind("click", function(){
                                    $(this).parent().remove();
                                });
                            }
                        }
                    }
                }
            }
        }
        else{
            _this.currEditElement = null;
        }
    };
    this.getDataPath = function(data, retPath){
        if(data.type){
            if(data.type == 2){
                retPath.push("."+data.data.chld_col_name);
            }
            else if(data.type ==1){
                retPath.push("["+data.data.arr_data.arr_idx+"]");
            }
        }
        if(data.col_name_ex){
            _this.getDataPath(data.col_name_ex, retPath);
        }
        if(data.name_ex){
            _this.getDataPath(data.name_ex, retPath);
        }
    };
    this.initDefText = function(item, defObj){
        $("#tp_"+item+"_def_text").val(JSON.stringify(defObj));
        var treeNodes = [];
        var extraObj = {};
        extraObj.id = defObj.id;
        extraObj.optype = defObj.type;
        extraObj.name = defObj.name;
        _this.sqlex2zTreeNodes(defObj, treeNodes, extraObj);
        var zTreeObj;
        var setting = {
            view: {
                selectedMulti: false
            },
            data: {
                simpleData: { //这里是用的简单数据
                    enable: true,
                    idKey: "id",
                    pIdKey: "pId",
                    rootPId: 0
                }
            }
        };
        var zTreeNodes = treeNodes;
        zTreeObj = $.fn.zTree.init($("#tp_"+item+"_def_tree"), setting, zTreeNodes);
    };
    this.initAssignText = function(item, assignObj){
        $("#tp_"+item+"_assign_text").val(JSON.stringify(assignObj));
        var assignNodes = [];
        _this.assign2zTreeNodes(assignObj, assignNodes);
        var zTreeObj;
        var setting = {
            view: {
                selectedMulti: false
            }
        };
        var zTreeNodes = assignNodes;
        zTreeObj = $.fn.zTree.init($("#tp_"+item+"_assign_tree"), setting, zTreeNodes);
    };
    this.assign2zTreeNodes = function(assignObj, treeNodes){
        for(var item in assignObj){
            if(typeof assignObj[item] == 'object' && isArray(assignObj[item])){
                var tmpobj = {};
                tmpobj.name = assignObj[item].join(',');
                treeNodes.push(tmpobj);
            }
            else if(typeof assignObj[item] == 'object'){
                var tmpnodes = {};
                tmpnodes.name = '父级';
                tmpnodes.children = [];
                treeNodes.push(tmpnodes);
                _this.assign2zTreeNodes(assignObj[item], treeNodes[treeNodes.length-1].children);
            }
            else{
                var tmpobj = {};
                tmpobj.name = assignObj[item];
                treeNodes.push(tmpobj);
            }
        }
    };
    this.sqlex2zTreeNodes = function(sourceObj, treeNodes, extraObj){
        if(sourceObj.col_type == SE_TYPE_OBJECT){
            var obj = {};
            obj.pId = extraObj.pId;
            obj.type = extraObj.optype;
            obj.col_type = sourceObj.col_type;
            obj.col_type_ex = sourceObj.col_type_ex;
            obj.label = extraObj.name;
            obj.name = extraObj.name;
            obj.id = extraObj.pId ? ++extraObj.id : extraObj.id;
            treeNodes.push(obj);
            //treeNodes.push(sourceObj);
            for(var item in sourceObj.col_type_ex.sc_map){
                extraObj.name = item;
                extraObj.pId = sourceObj.id ? sourceObj.id : obj.id;
                _this.sqlex2zTreeNodes(sourceObj.col_type_ex.sc_map[item], treeNodes, extraObj);
            }
        }
        else if(sourceObj.col_type == SE_TYPE_ARRAY){
            var obj = {};
            obj.pId = extraObj.pId;
            obj.type = extraObj.optype;
            obj.col_type = sourceObj.col_type_ex.ele_col.col_type;
            obj.col_type_ex = null;
            obj.label = extraObj.name;
            obj.name = extraObj.name;
            obj.id = ++extraObj.id;
            treeNodes.push(obj);
            extraObj.pId = obj.id;
            extraObj.name = '';
            _this.sqlex2zTreeNodes(sourceObj.col_type_ex.ele_col, treeNodes, extraObj);
        }
        else{
            var obj = {};
            obj.pId = extraObj.pId;
            obj.type = extraObj.optype;
            obj.col_type = sourceObj.col_type;
            obj.col_type_ex = null;
            obj.label = extraObj.name;
            obj.name = extraObj.name;
            obj.id = ++extraObj.id;
            treeNodes.push(obj);
        }
    };
    function trim(str){ //删除左右两端的空格
        return str.replace(/(^\s*)|(\s*$)/g, "");
    }
    this.getTaskParam = function(taskid){
        var taskele = {};
        var taskp = {};
        if(taskid !== undefined){
            for(var i=0, ilen=_this.taskInstance.tasks.length;i<ilen;i++){
                var tmpTask = _this.taskInstance.tasks[i];
                if(tmpTask.taskID == taskid){
                    taskele = tmpTask;
                    taskp = taskele.param;
                }
            }
        }
        else{
            taskele.taskID = _this.maxTaskID >= _this.taskInstance.tasks.length ? _this.maxTaskID : _this.taskInstance.tasks.length;
        }
        taskp.taskPro = {};
        taskp.taskPro.tasklevel = $("#tp_tasklevel").val();
        //taskp.taskPro.tasklevel = $("#tp_tasklevel").val();
        taskp.taskPro.local = 0;
        taskp.taskPro.remote = 1;
        taskp.taskPro.iscommit = $("input[name=iscommit]:checked").val();
        //针对价格趋势抓取    2017/3/14  by  yu
        taskp.taskPro.creattask = $("input[name=creattask]:checked").val();

        taskp.taskPro.source = 11;
        taskp.taskPro.content_type = 0;
        taskp.taskPro.duration = $("#tp_duration").val(); 
        taskp.taskPro.conflictdelay = $("#tp_conflictdelay").val(); 
        taskp.taskPro.iscalctrend = $("input[name=iscalctrend]:checked").val(); 
        taskp.taskPro.dataSave = $("input[name=dataSave]:checked").val(); 
        taskp.taskPro.filPageTag = $("input[name=filPageTag]:checked").val(); 
        taskp.taskPro.addUser = $("input[name=addUser]:checked").val(); 
        taskp.taskPro.genCreatedAt = $("input[name=genCreatedAt]:checked").val(); 
        taskp.taskPro.isGenChildTask = $("input[name=isGenChildTask]:checked").val();
        taskp.taskPro.dictionaryPlan = $("#tph_dictionaryPlan2").val()=="[[]]"?[[]]:$("#tph_dictionaryPlan2").val();
        taskp.taskPro.cronstart = $("#tp_activatetime").val() ? $("#tp_activatetime").val() : "";

        taskp.taskPro.specifiedType = trim($("#tp_taskclassify").val());
        taskp.taskPro.specifiedMac = trim($("#tp_spcfdmac").val());
        taskp.taskPro.specifiedTypeForChild = $("input[name=taskchild]:checked").val()=="true"?true:false;
        taskp.taskPro.specifiedMacForChild = $("input[name=macchild]:checked").val()=="true"?true:false;
		if( taskp.taskPro.specifiedMac && taskp.taskPro.specifiedMac.length < 17 ) {
            alert( "MAC地址长度短了" );
            return false;
        } else if( taskp.taskPro.specifiedMac && taskp.taskPro.specifiedMac.length > 17 ) {
            alert( "MAC地址长度长了" );
            return false;
        }
        //提交数据地址
        //taskp.taskPro.submiturl = $("#tp_submiturl").val().trim();
        //taskp.taskPro.submiturlForChild = $("input[name=submiturlchild]:checked").val()=="true"?true:false;
		//console.log( taskp.taskPro.specifiedType );
		//console.log( taskp.taskPro.specifiedMac );

		taskp.taskPro.cronend = $("#tp_endtime").val() ? $("#tp_endtime").val() : "";
        taskp.taskPro.crontime = $("#tp_crontime").val();
		taskp.taskPro.crontimedes = $("#tp_crontimedes").html();
		
		
		
		
		
        //taskp.taskPro.template = $("#tp_template").val();
        taskp.taskPro.remarks = $("#tp_remarks").val();
        var userpath = $("#tp_userData_path_text").val();
        taskp.taskPro.userPathId = userpath ? jQuery.parseJSON($("#tp_userData_path_text").val()).paramPathId : '';
        //步骤
        taskp.templMap = {};
        taskp.stepNumURLPatterns = {};
        $("#tp_grabsteps").find("span[name=step_span]").each(function(i, item){
            var tmpobj = {};
            tmpobj.tpl = $(item).attr("tpl");
            tmpobj.stepid = parseInt($(item).find('span[name=grabstep_id]').text(), 10);
            tmpobj.steptpl = $(item).find('span[name=grabstep_tpl]').text();
            tmpobj.urlregex = $(item).find('span[name=grabstep_urlregex]').text();
            if(taskp.templMap[tmpobj.steptpl]){
                taskp.templMap[tmpobj.steptpl].push(tmpobj.stepid);
            }
            else{
                taskp.templMap[tmpobj.steptpl] = [];
                taskp.templMap[tmpobj.steptpl].push(tmpobj.stepid);
            }
            taskp.stepNumURLPatterns[tmpobj.stepid] = tmpobj.urlregex.split("\n");
            taskp.taskPro.template = tmpobj.tpl;
        });

        //if(!taskp.taskPro.crontime){
        //   alert("请添加定时计划!");
        //    return false;
        //}

        if(taskp.taskPro.cronend < taskp.taskPro.cronstart){
            alert('结束时间应晚于或等于起始时间!');
            return false;
        }
		
		
        //定义
        taskp.paramsDef_def = jQuery.parseJSON($("#tp_paramsDef_def_text").val());
        taskp.parentParam_def = jQuery.parseJSON($("#tp_parentParam_def_text").val());
        taskp.runTimeParam_def = jQuery.parseJSON($("#tp_runTimeParam_def_text").val());
        taskp.constants_def = jQuery.parseJSON($("#tp_constants_def_text").val());
        taskp.outData_def = jQuery.parseJSON($("#tp_outData_def_text").val());

        taskp.g_global_def = jQuery.parseJSON($("#tp_g_global_def_text").val());
        taskp.URLCache_def = jQuery.parseJSON($("#tp_URLCache_def_text").val());
        taskp.TaskCache_def = jQuery.parseJSON($("#tp_TaskCache_def_text").val());
        taskp.AppCache_def = jQuery.parseJSON($("#tp_AppCache_def_text").val());
        taskp.g_collect_def = jQuery.parseJSON($("#tp_g_collect_def_text").val());
        taskp.CurrPageCache_def = jQuery.parseJSON($("#tp_CurrPageCache_def_text").val());
        taskp.g_current_def = jQuery.parseJSON($("#tp_g_current_def_text").val());

        //赋值
        taskp.paramsDef = jQuery.parseJSON($("#tp_paramsDef_assign_text").val());
        taskp.parentParam = jQuery.parseJSON($("#tp_parentParam_assign_text").val());
        taskp.runTimeParam = jQuery.parseJSON($("#tp_runTimeParam_assign_text").val());
        taskp.constants = jQuery.parseJSON($("#tp_constants_assign_text").val());

        taskp.loginAccounts = {};
        taskp.loginAccounts.globalaccount = $("input[name=tp_globalaccount]:checked").val();
        taskp.loginAccounts.logoutfirst = $("input[name=tp_logoutfirst]:checked").val();
        taskp.loginAccounts.isswitch = $("input[name=tp_isswitch]:checked").val();
        taskp.loginAccounts.switchpage = $("#tp_switchpage").val();
        taskp.loginAccounts.switchtime = $("#tp_switchtime").val();
        var accountsource = [];
        $("#tp_addedsourceid").find(".useritem").each(function(i, item){
            accountsource.push($(item).attr("code"));
        });
        taskp.loginAccounts.source = accountsource[0];
        taskp.loginAccounts.switchstrategy = 1;
        //抓取帐号
        var addaccountid = [];
        $("#tp_addedaccountid").find(".useritem").each(function(i, item){
            addaccountid.push($(item).attr("code"));
        });
        taskp.loginAccounts.accounts = addaccountid;
        taskp.loginAccounts.globalaccounts = "";
        taskp.pathStructMap = {};
        taskp.filters = [];
        $("#tp_paramfilter_span").find("span[filter]").each(function(i, item){
            var filterStr = $(item).attr("filter");
            var filterObj = filterStr ? jQuery.parseJSON(filterStr) : null;
            if(filterObj.valueCfg.valueType == "var"){
                taskp.pathStructMap[filterObj.valueCfg.paramPathId] = {};
                taskp.pathStructMap[filterObj.valueCfg.paramPathId] = filterObj.valueCfg.paramPath;
                delete filterObj.valueCfg.paramPath;
            }
            taskp.filters.push(filterObj);
        });
        taskp.taskUrls = {};
        //{gen:表示根据参数生成，consts表示直接赋值给爬虫}
        taskp.taskUrls.type = $("input[name=urltype]:checked").val();
        taskp.taskUrls.urlValues = [];
        var urlvalue = $("#tp_listweburl").val();
        taskp.taskUrls.urlValues.push(urlvalue);
        var paths_str = $("#tp_listweburl").attr("paths");
        var paths_arr = paths_str ? jQuery.parseJSON(paths_str) : [];
        if(paths_arr.length > 0){
            for(var i=0;i<paths_arr.length;i++){
                var pitem = paths_arr[i];
                taskp.pathStructMap[pitem.pathid] = {};
                taskp.pathStructMap[pitem.pathid] = pitem.path;
            }
        }
        taskp.outData = {};
        var outDatastr= $("#tp_outData_path_text").val();
        var outDataPath = outDatastr ? jQuery.parseJSON(outDatastr) : null;
        if(outDataPath !== null){
            taskp.outData.datasPath = "|"+outDataPath.paramPathId+"|";
            taskp.pathStructMap[outDataPath.paramPathId] = outDataPath.paramPath;
        }
        //参数映射
        taskp.pathStructMap.keys = [];
        $("#tp_paramMap_params").children("div[name=paramMap]").each(function(i, item){
            var paramMapStr= $(item).children("span[name=paramMap]").attr("code");
            var paramMapObj = paramMapStr ? jQuery.parseJSON(paramMapStr) : null;
            taskp.pathStructMap[paramMapObj.paramPathId] = paramMapObj.paramPath;
            taskp.pathStructMap.keys.push(paramMapObj.paramPathId);
        });
        var parentChoiceParam = $("#tp_fromPath_btn").attr('choiceparam') ? jQuery.parseJSON($("#tp_fromPath_btn").attr('choiceparam')) : null;
        if(parentChoiceParam){
            taskele.parentChoiceParam = parentChoiceParam;
        }
        var isroottask = parseInt($("input[name=tp_isroottask]:checked").val(), 10);
        if(isroottask === 1){
            taskele.parentTaskID = taskele.taskID;
        }
        else{//子任务
            taskele.parentTaskID = $("#tp_parenttask").val();
        }

        if(!taskp.taskGenConf){
            taskp.taskGenConf = [];
        } 
        else{ //复制或单独编辑一个有子任务的任务时,点击确定,子任务的路径定义需要从新赋值
            for(var i=0, ilen=taskp.taskGenConf.length;i<ilen;i++){
                var tgc = taskp.taskGenConf[i];
                if(tgc && tgc.childTaskDefId != undefined){
                    for(var i=0, ilen=_this.taskInstance.tasks.length;i<ilen;i++){
                        var tmpTask = _this.taskInstance.tasks[i];
                        if(tmpTask.taskID == tgc.childTaskDefId){
                            //keys keys中路径不应该添加到父任务中去.
                            var ckeys = tmpTask.param.pathStructMap['keys'];
                            if(!ckeys){
                                ckeys = [];
                            }
                            for(var cpath in tmpTask.param.pathStructMap){
                                if(!ckeys.inArray(cpath) && cpath != 'keys'){
                                    taskp.pathStructMap[cpath] = tmpTask.param.pathStructMap[cpath];
                                }
                            }
                            break;
                        }
                    }
                }
            }
        }
        if(isroottask === 0){ //当前任务为子任务

            var parentTask = {};
            for(var i=0, ilen=_this.taskInstance.tasks.length;i<ilen;i++){
                var tmpTask = _this.taskInstance.tasks[i];
                if(tmpTask.taskID == taskele.parentTaskID){
                    parentTask = tmpTask;
                    parentTask.param.taskGenConf = [];
                }
            }

            var tmpConf = {};
            tmpConf.dataPath = taskp.outData.datasPath ? taskp.outData.datasPath : '';
            tmpConf.splitStep = $("#tp_splitStep").val();
            tmpConf.childTaskDefId = taskele.taskID;
            tmpConf.childTaskUrl = {};
            var urltype = $("input[name=genconf_urltype]:checked").val();
            tmpConf.childTaskUrl.type = urltype;
            var urlvalue = $("#tp_genconf_listweburl").val();
            if(urltype == 'consts'){
                tmpConf.childTaskUrl.value = {};
                var paths_str = $("#tp_genconf_listweburl").attr("paths");
                var paths_arr = paths_str ? jQuery.parseJSON(paths_str) : [];
                if(paths_arr.length > 0){
                    for(var i=0;i<paths_arr.length;i++){
                        var pitem = paths_arr[i];
                        parentTask.param.pathStructMap[pitem.pathid] = {};
                        parentTask.param.pathStructMap[pitem.pathid] = pitem.path;
                        taskp.pathStructMap[pitem.pathid] = pitem.path;
                        tmpConf.childTaskUrl.value.paramValue = "|"+pitem.pathid+"|";
                        tmpConf.childTaskUrl.value.paramSource = pitem.path.paramSource;
                    }
                }
            }
            else{
                tmpConf.childTaskUrl.templ = urlvalue;
            }
            taskp.taskUrls = null;
            /*
            tmpConf.childTaskUrl.value = {};
            if(taskp.taskUrls.urlValues.length > 0){
                var reg = /\|(.*?)\|/g;
                var arr = taskp.taskUrls.urlValues[0].match(reg);
                var pathid = arr[0].replace(/\|/g, "");
                tmpConf.childTaskUrl.value.paramSource = taskp.pathStructMap[pathid].paramSource;
                tmpConf.childTaskUrl.value.paramValue = deepClone(taskp.taskUrls.urlValues[0]);
                parentTask.param.pathStructMap[pathid] = taskp.pathStructMap[pathid];
                taskp.taskUrls = null;
            }
            */
            tmpConf.params = [];

            var fromPath = {};
            var toPath = {};
            $("#tp_taskGenConf_params").children("div[name=paramPath]").each(function(i, item){
                var tmp = {};
                var pathFromStr= $(item).children("span[name=paramPathfrom]").attr("code");
                var pathFrom = pathFromStr ? jQuery.parseJSON(pathFromStr) : null;
                taskp.pathStructMap[pathFrom.paramPathId] = pathFrom.paramPath;
                tmp.paramPath = "|"+pathFrom.paramPathId+"|";
                tmp.targetType = pathFrom.paramPath.paramSource ? $(item).children("span[name=paramPathfrom]").attr("targetType") : undefined;

                fromPath.id = pathFrom.paramPathId;
                fromPath.path = pathFrom.paramPath;
                parentTask.param.pathStructMap[fromPath.id] = fromPath.path;
                var pathToStr= $(item).children("span[name=paramPathto]").attr("code");
                var pathTo = pathToStr ? jQuery.parseJSON(pathToStr) : null;
                taskp.pathStructMap[pathTo.paramPathId] = pathTo.paramPath;
                tmp.toParamPath = "|"+pathTo.paramPathId+"|";
                tmpConf.params.push(tmp);

                toPath.id = pathTo.paramPathId;
                toPath.path = pathTo.paramPath;
                parentTask.param.pathStructMap[toPath.id] = toPath.path;

            });
            //tmpConf是子任务参数，需要放到对应的父级结构中
            var has = false;
            for(var i=0;i<parentTask.param.taskGenConf;i++){
                if(JSON.stringify(parentTask.param.taskGenConf[i]) == JSON.stringify(tmpConf)){
                    has = true;
                    break;
                }
            }
            if(!has){
                parentTask.param.taskGenConf.push(tmpConf);
            }
            /*
            for(var i=0, ilen=_this.taskInstance.tasks.length;i<ilen;i++){
                var parentTask = _this.taskInstance.tasks[i];
                if(parentTask.taskID == taskele.parentTaskID){
                    parentTask.param.taskGenConf.push(tmpConf);
                    parentTask.param.pathStructMap[fromPath.id] = fromPath.path;
                    parentTask.param.pathStructMap[toPath.id] = toPath.path;
                }
            }
            */
        }
        taskele.param = taskp;
        if(taskid !== undefined){
            _this.currEditElement = taskele;
        }
        _this.completeAddElement(taskele);
    };
    this.completeAddElement = function(ele){
        if(ele != undefined){
            var isEdit = false;
            if(_this.currEditElement == null){//新增
                _this.taskInstance.tasks.push(ele);
                _this.addModelLayer(ele);
                _this.maxTaskID++; //新增完把全局的taskID++;
            }
            else{ //修改
                isEdit = true;
                var changemodels = ele.taskID != _this.currEditElement.taskID;
                if(changemodels){ //复制
                    ele.taskID = _this.currEditElement.taskID;
                    //删除旧的element
                    _this.delElement($("div[modellayerindex="+ele.taskID+"]").find("h1 a img"));
                    //添加上新的element
                    _this.taskInstance.tasks.push(ele);
                    _this.addModelLayer(ele);
                }
                else{ //编辑
                    for(var i=0,ilen=_this.taskInstance.tasks.length;i<ilen;i++){
                        var tmpTask = _this.taskInstance.tasks[i];
                        if(tmpTask.taskID == ele.taskID){
                            _this.taskInstance.tasks[i] = ele;
                        }
                    }
                    $("#divaddcommonspt").dialog('close');
                }
            }

            //移动modellayer
            var sourceoffset = $("#modellayer_"+ele.taskID+"").offset();
            if(sourceoffset != null){
                $("#modellayer_"+ele.taskID+"").css({left:parseInt(sourceoffset.left+1, 10)+"px"});
            }
            //连线
            if(ele.parentTaskID != ele.taskID){
                jsPlumbInstance.detachAllConnections("modellayer_"+ele.taskID+"");
                jsPlumbInstance.connect({ source: "modellayer_"+ele.parentTaskID+"", target: "modellayer_"+ele.taskID+"" });
            }
        }
        return true;
    };
    this.editElement = function(event){
        _this.setCurrModelLayer(event.target);
        var taskid = $(event.target).parents("div[canvas_taskid]").attr("canvas_taskid");		
        $(_this.taskInstance.tasks).each(function(i,v){
            if(v.taskID == taskid){
                /*
                if(v.taskID != v.parentTaskID){
                    for(var i=0;i<_this.taskInstance.tasks.length;i++){
                        var taskitem = _this.taskInstance.tasks[i]; 
                        if(taskitem.taskID == taskitem.parentTaskID){
                            for(var j=0;j<taskitem.param.taskGenConf.length;j++){
                                var tgitem = taskitem.param.taskGenConf[j];
                                if(tgitem.childTaskDefId == taskid){
                                    v.param.taskGenConf.push(tgitem);
                                    break;
                                } 
                            }
                            break;
                        }
                    }
                }
                */
                _this.currEditElement = v;

                _this.initElement(_this.currEditElement);
            }
        });
    };
    this.delElement = function(sender){
        for(var i=_this.taskInstance.tasks.length-1;i>-1;i--){
            var v = _this.taskInstance.tasks[i];
            //deleteTask
            if($(sender).parents("div[modellayerindex]").attr("modellayerindex") == v.taskID){
                var delparentTaskID = v.parentTaskID;
                for(var j=_this.taskInstance.tasks.length-1;j>-1;j--){
                    var m = _this.taskInstance.tasks[j];
                    if(m.taskID == delparentTaskID){
                        for(var k=0;k<m.param.taskGenConf.length;k++){
                            var kitem =  m.param.taskGenConf[k];
                            if(kitem.childTaskDefId == v.taskID){
                                _this.taskInstance.tasks[j].param.taskGenConf.splice(k,1);
                                break;
                            }
                        }
                        break;
                    }
                }
                _this.taskInstance.tasks.splice(i,1);
                break;
            }
        }
        $(sender).parents("div[id^=modellayer_]").remove();
    };
    this.completeCanvas = function(){
        var taskall = {};
        if(_this.taskInstance == undefined || _this.taskInstance == null || _this.taskInstance.tasks.length == 0){
            alert('请添加任务');
            return false;
        }
        if(_this.taskInstance.tasks.length == 1){
            taskall['root'] = _this.taskInstance.tasks[0].param;
        }
        else{
            for(var i=0,ilen=_this.taskInstance.tasks.length;i<ilen;i++){
                var task = _this.taskInstance.tasks[i];
                if(task.parentTaskID == task.taskID){
                    taskall['root'] = task.param;
                }
                else{
                    taskall[i] = task.param;
                }
            }
        }
        //验证
        if(taskall['root'].taskGenConf.length > 0 && taskall['root'].taskGenConf[0].params.length == 0){
            alert('请添加传递参数');
            return false;
        }

        //根任务需要设置定时计划  子任务设置不设置都可以
        //console.log(taskall['root']["taskPro"]["crontime"] );
        if( !taskall['root']["taskPro"]["crontime"] || taskall['root']["taskPro"]["crontime"] == "{}"){
            //if( ($("#tp_crontime").val() == "{}")|| ($("#tp_crontime").val() == "")) {
               alert("请添加根任务的定时计划!");
                return false;
            //}
        }
        //
        //if( !taskall['root']["taskPro"]["submiturl"] || taskall['root']["taskPro"]["submiturl"] == "0"){
        //    //if( ($("#tp_crontime").val() == "{}")|| ($("#tp_crontime").val() == "")) {
        //    alert("请添加根任务的数据入库地址!");
        //    return false;
        //    //}
        //}
        /*
        var query = "";
        query += "tasktype=2";
        query += "&task=20";
        query += "&tasklevel="+taskall['root'].taskPro.tasklevel;
        query += "&tasklevel="+taskall['root'].taskPro.tasklevel;
        query += "&local="+taskall['root'].taskPro.local;
        query += "&remote="+taskall['root'].taskPro.remote;
        query += "&activatetime="+taskall['root'].taskPro.activatetime;
        query += "&conflictdelay="+taskall['root'].taskPro.conflictdelay;
        query += "&remarks="+taskall['root'].taskPro.remarks;
        query +="&taskparams='"+JSON.stringify(taskall)+"'";
        */
        var query = {};
        query.tasktype = 2;
        query.task = 20;
        query.tasklevel = taskall['root'].taskPro.tasklevel;
        query.local = taskall['root'].taskPro.local;
        query.remote = taskall['root'].taskPro.remote;
        query.cronstart = taskall['root'].taskPro.cronstart;

        if(taskall['root'].taskPro.specifiedType ){
            query.specifiedType = taskall['root'].taskPro.specifiedType;
        } else {
            query.specifiedType = "";
        }
        if(taskall['root'].taskPro.specifiedMac ){
            query.specifiedMac = taskall['root'].taskPro.specifiedMac;
        } else {
            query.specifiedMac = "";
        }

        //if(taskall['root'].taskPro.submiturl ){
        //    query.submiturl = taskall['root'].taskPro.submiturl;
        //} else {
        //    query.submiturl = "";
        //}
		//query.specifiedType = taskall['root'].taskPro.specifiedType;
		//query.specifiedMac = taskall['root'].taskPro.specifiedMac;

		query.cronend = taskall['root'].taskPro.cronend;
        query.crontime = taskall['root'].taskPro.crontime;
        query.crontimedes = taskall['root'].taskPro.crontimedes;
		
		
		
        query.conflictdelay = taskall['root'].taskPro.conflictdelay;

        taskall['root'].taskPro.specifiedMacForChild = taskall['root'].taskPro.specifiedMacForChild =="true"|| taskall['root'].taskPro.specifiedMacForChild == true?true:false;
        taskall['root'].taskPro.specifiedTypeForChild = taskall['root'].taskPro.specifiedTypeForChild =="true" || taskall['root'].taskPro.specifiedTypeForChild == true?true:false;
        //taskall['root'].taskPro.submiturlForChild = taskall['root'].taskPro.submiturlForChild =="true" || taskall['root'].taskPro.submiturlForChild == true?true:false;

        query.remarks = taskall['root'].taskPro.remarks;
        query.taskparams = JSON.stringify(taskall);
        
        addScheduleTask($.param(query), function(r){
    
            if(r.result){
                searchData(true); //taskschedule.html 中的函数
                //canvasbody
                $("#canvasbody").dialog("close");
            }
            else{
                alert(r.msg);		
                return false;
            }	
        });

    };
    /*
    this.connectTask = function(sourceTaskID, targetTaskID){
        var sourceParam = {}; //子级
        var targetParam = {}; //父级
        for(var i=0, ilen=_this.taskInstance.tasks.length;i<ilen;i++){
            var tmpTask = _this.taskInstance.tasks[i];
            if(tmpTask.taskID == sourceTaskID){
                sourceParam = tmpTask.param;
            }
            if(tmpTask.taskID == targetTaskID){
                targetParam = tmpTask.param;
            }
        }
        if(!sourceParam.taskGenConf){
            sourceParam.taskGenConf = [];
        } 
        var tmpConf = {};
        tmpConf.childTaskDefId = targetTaskID;
        tmpConf.childTaskUrl = {};
        tmpConf.childTaskUrl.type = targetParam.taskUrls.type;
        tmpConf.childTaskUrl.templ = targetParam.taskUrls.urlValues[0];
        sourceParam.taskGenConf.push(tmpConf);
    };
    */
    this.show = function(taskparams){
        $("body").undelegate("div.canvaselement", "click");
        $("body").delegate("div.canvaselement", "click", _this.editElement);
        var canvashtml ='<div class="winbody">';
        canvashtml +='<div class="dataarea" id="canvasdiv">';
        canvashtml +='<h1 style="height: 43px; overflow: hidden; margin-bottom: 0px;">';
        canvashtml +='<p style="padding-top:10px"></p>';
        canvashtml +='<p class="datatag">';
        canvashtml +='<a href="javascript:void(0);" title="对一张表进行查询统计" id="addcanvastask" >新增任务</a>';
        canvashtml +='</p>';
        canvashtml +='</h1>';
        canvashtml +='<div class="tabcotent" id="modellayersdiv" style="height:300px;">';
        canvashtml +='</div>';
        canvashtml +='</div>';
        canvashtml +='</div>';

        var canvasbodyhtml = '<div id="canvasbody">'+canvashtml+'</div>';
        $("body").append(canvasbodyhtml);
        $("#canvasbody").dialog({
            autoOpen: false,
            modal:true,
            height:950,
            width:950
        });
        $("#canvasbody").dialog("open");
        $("#canvasbody").dialog("option", "buttons", {
            "确定": function () {
                _this.completeCanvas();


            },
            "取消": function () {
                $(this).dialog("close");
            }
        });

        jsPlumb.ready(function () {
            //jsPlumb 画布上模型连线
            // setup some defaults for jsPlumb.
            jsPlumbInstance = jsPlumb.getInstance({
                Endpoint: ["Dot", {radius: 2}],
                            isSource:true,	//是否可以拖动（作为连线起点）
                            isTarget:true,	//是否可以放置（作为连线终点）
                            HoverPaintStyle: {strokeStyle: "#1e8151", lineWidth: 2 },
                            ConnectionOverlays: [[ "Arrow", { location: 1, id: "arrow", length: 14, foldback: 0.8 } ], [ "Label", { label: "FOO", id: "label", cssClass: "aLabel" }]],
                            Container: "modellayersdiv"
            });
            jsPlumbInstance.bind("click", function (c) {
                if(confirm('删除连线？')){
                    jsPlumbInstance.detach(c);
                }
            });
            jsPlumbInstance.bind("connection", function (info) {
                //info.connection.getOverlay("label").setLabel(info.connection.id);
                //var sourceID = info.sourceId;
                //var targetID = info.targetId;
                //var sourceTaskID = sourceID.split("modellayer_")[1];
                //var targetTaskID = targetID.split("modellayer_")[1];
                //_this.connectTask(sourceTaskID, targetTaskID);

                //移动modellayer
                var sourceoffset = $("#"+sourceID+"").offset();
                if(sourceoffset != null){
                    $("#"+sourceID+"").css({left:parseInt(sourceoffset.left+1, 10)+"px"});
                }
                var targetoffset = $("#"+targetID+"").offset();
                if(targetoffset != null){
                    $("#"+targetID+"").css({left:parseInt(targetoffset.left+1, 10)+"px"});
                }
                //需要先移动modellayer 再重新画
                jsPlumbInstance.repaintEverything();
            });
        });
        $("#addcanvastask").unbind("click");
        $("#addcanvastask").bind("click", function(){
            _this.initElement();
        });
        //draw elements
        $("#modellayersdiv").empty();
        if(taskparams !== undefined){
            var taskcount = 0;
            for(var task in taskparams){
                for(var i=0,ilen=taskparams[task].taskGenConf.length;i<ilen;i++){
                    var ct = taskparams[task].taskGenConf[i];
                    taskparams[ct.childTaskDefId].parentTaskID = task;
                }
            }
            var tmptask = {};
            tmptask.taskID = taskcount;
            tmptask.parentTaskID = taskcount;
            tmptask.param = taskparams['root'];
            _this.taskInstance.tasks.push(tmptask);
            taskcount++;
            for(var task in taskparams){
                if(task !== 'root'){
                    var tmptask = {};
                    tmptask.taskID = taskcount;
                    tmptask.parentTaskID = taskparams[task].parentTaskID == 'root' ? 0 : taskparams[task].parentTaskID;
                    tmptask.parentChoiceParam = {};
                    var ptid = taskparams[task].parentTaskID;
                    tmptask.parentChoiceParam.constants_def = taskparams[ptid].constants_def;
                    tmptask.parentChoiceParam.paramsDef_def = taskparams[ptid].paramsDef_def;
                    tmptask.parentChoiceParam.parentParam_def = taskparams[ptid].parentParam_def;
                    tmptask.parentChoiceParam.runTimeParam_def = taskparams[ptid].runTimeParam_def;
                    tmptask.parentChoiceParam.outData_def = taskparams[ptid].outData_def;
                    tmptask.parentChoiceParam.g_global_def = taskparams[ptid].g_global_def;
                    tmptask.parentChoiceParam.URLCache_def = taskparams[ptid].URLCache_def;
                    tmptask.parentChoiceParam.TaskCache_def = taskparams[ptid].TaskCache_def;
                    tmptask.parentChoiceParam.AppCache_def = taskparams[ptid].AppCache_def;
                    tmptask.parentChoiceParam.g_collect_def = taskparams[ptid].g_collect_def;
                    tmptask.parentChoiceParam.CurrPageCache_def = taskparams[ptid].CurrPageCache_def;
                    tmptask.parentChoiceParam.g_current_def = taskparams[ptid].g_current_def;
                    tmptask.param = taskparams[task];
                    _this.taskInstance.tasks.push(tmptask);
                    taskcount++;
                }
            }
        }
        if(_this.taskInstance != null){
            var tlen = _this.taskInstance.tasks.length;
            if(tlen > 0){
                for(var i=0; i<tlen; i++){
                    var ele = _this.taskInstance.tasks[i];
                    _this.addModelLayer(ele);
                    $("body").undelegate("div.canvaselement", "click");
                    $("body").delegate("div.canvaselement", "click", _this.editElement);
                }
                //弹出最后一个，因为是最后push的
                $("div[canvas_taskid='"+_this.taskInstance.tasks[_this.taskInstance.tasks.length-1].taskID+"']").children("div").click();
            }
        }
    };
}
