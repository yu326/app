/*
 * 2016-1-21
 * 爬虫任务参数的定义
 * 依赖jQuery 实现
 * 依赖zTree
 * */
var myParamDef;
if (!myParamDef) {
    myParamDef = {};
}
    var SE_TYPE_INT32 = 5; /* mapping to APR_DBD_TYPE_INT, int32 */
    var SE_TYPE_STRING = 13; /* mapping to APR_DBD_TYPE_STRING, char* */
    var SE_TYPE_OBJECT = 23; /* This is used for object data type of NoSQL column only */
    var SE_TYPE_ARRAY = 24; /* This is used for array data type of NoSQL column only */
    var SE_TYPE_TIMESTAMP = 18;      /* mapping to APR_DBD_TYPE_TIMESTAMP, char*, represented as string */

(function () {
    function ct2Cvt(type){
        var cvt = "";
        switch(parseInt(type, 10)){
            case SE_TYPE_INT32:
                cvt = "i32_val";
                break;
            case SE_TYPE_STRING:
                cvt = "str";
                break;
            case SE_TYPE_TIMESTAMP:
                cvt = "date";
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


    var zNodes = [];

    var id_i = 2;
    var setting = {
        view: {
            addHoverDom: addHoverDom,
            removeHoverDom: removeHoverDom,
            selectedMulti: false
        },
        edit: {
            enable: true,
            editNameSelectAll: true,
            showRemoveBtn: showRemoveBtn,
            showRenameBtn: showRenameBtn
        },
        data: {
            simpleData: { //这里是用的简单数据
                enable: true,
                idKey: "id",
                pIdKey: "pId",
                rootPId: 0
            }
        },
        callback: {
            beforeDrag: beforeDrag,
            beforeEditName: beforeEditName,
            beforeRemove: beforeRemove,
            beforeRename: beforeRename,
            onRemove: onRemove,
            onRename: onRename
        }
    };
function addHoverDom(treeId, treeNode) {
    var sObj = $("#" + treeNode.tId + "_span");
    if (treeNode.editNameFlag || $("#addBtn_" + treeNode.tId).length > 0) return;
    if (treeNode.col_type == SE_TYPE_ARRAY) {
        //这里的字符串不要继续after拼接，否则样式变化，这里的数组就必须这样放在内部显示
        var addStr = "<span class='button add' id='addBtn_" + treeNode.tId + "' title='add node' onfocus='this.blur();'></span><span style='font-size:2px;' class='button edit' id='editBtn_" + treeNode.tId + "' title='edit node' onfocus='this.blur();'></span><span id='typeBtn_" + treeNode.tId + "' style='font-size:4px;'>[数组]</span>";
        sObj.after(addStr);
        var btn = $("#addBtn_" + treeNode.tId);
        if (btn) {
            btn.unbind("click");
			btn.bind("click",
            function() {
                if (treeNode.isParent) {
                    alert("该节点为数组类型，只能添加一个子节点");
                    return;
                }
                var zTree = $.fn.zTree.getZTreeObj("treeDemo");
                var pidx = treeNode.id;
                $("#addfielddiv1").attr("addopttype", "phyfield_add");
                addData1(pidx, treeNode,"phyfield_add");
                return false;
            });
        }
        var editbtn = $("#editBtn_" + treeNode.tId + "");
        if (editbtn) {
			editbtn.unbind("click");
            editbtn.bind("click",
            function() {
                $("#addfielddiv1").attr("addopttype", "phyfield_modify");
                $("#addfielddiv1").attr("fieldid", treeNode.id);
                $("#addfield_name_text1").val(treeNode.name);
                $("#addfield_label_text1").val(treeNode.label);
                $("#addfield_datatype_text1").val(treeNode.type);
                var zTree = $.fn.zTree.getZTreeObj("treeDemo");
                var pidx = treeNode.pId;
                addData1(pidx, treeNode,"phyfield_modify");
				return false;
            });
        }
    } 
    else if (treeNode.col_type == SE_TYPE_OBJECT) {
        var addStr = "<span class='button add' id='addBtn_" + treeNode.tId + "' title='add node' onfocus='this.blur();'></span><span style='font-size:2px;' class='button edit' id='editBtn_" + treeNode.tId + "' title='edit node' onfocus='this.blur();'></span><span id='typeBtn_" + treeNode.tId + "' style='font-size:4px;'>[对象]</span>";
        sObj.after(addStr);
        var btn = $("#addBtn_" + treeNode.tId);
        if (btn) {
            btn.bind("click",
            function() {
                var zTree = $.fn.zTree.getZTreeObj("treeDemo");
                var pidx = treeNode.id;
                //$("#addfielddiv1").attr("addopttype", "phyfield_add");
				var optype = "phyfield_add";
                addData1(pidx, treeNode,optype);
                return false;
            });
        }
        var editbtn = $("#editBtn_" + treeNode.tId + "");
        if (editbtn) {
            editbtn.bind("click",
            function() {
                $("#addfielddiv1").attr("addopttype", "phyfield_modify");
                $("#addfielddiv1").attr("fieldid", treeNode.id);
                $("#addfield_name_text1").val(treeNode.name);
                $("#addfield_tb_text1").val(treeNode.tbid);
                $("#addfield_label_text1").val(treeNode.label);
                $("#addfield_datatype_text1").val(treeNode.type);
                var zTree = $.fn.zTree.getZTreeObj("treeDemo");
                var pidx = treeNode.pId;
				var optype = "phyfield_modify";
                addData1(pidx, treeNode,optype);
				return false;
            });
        }
        return;
    } else {
        if ($("#editBtn_" + treeNode.tId + "").length == 0) {
            var addStr = "<span style='font-size:2px;' class='button edit' id='editBtn_" + treeNode.tId + "' title='edit node' onfocus='this.blur();'></span><span id='typeBtn_" + treeNode.tId + "' style='font-size:4px;'>["+ct2Cvt(parseInt(treeNode.col_type))+"]</span>"
            sObj.after(addStr);
            var editbtn = $("#editBtn_" + treeNode.tId + "");
            if (editbtn) {
                editbtn.bind("click",
                function() {
                    //$("#addfielddiv1").attr("addopttype", "phyfield_modify");
                    //$("#addfielddiv1").attr("fieldid", treeNode.id);
                    $("#addfield_name_text1").val(treeNode.name);
                    $("#addfield_tb_text1").val(treeNode.tbid);
                    $("#addfield_label_text1").val(treeNode.label);
                    $("#addfield_datatype_text1").val(treeNode.type);
                    var zTree = $.fn.zTree.getZTreeObj("treeDemo");
                    var pidx = treeNode.pId;
					var optype = "phyfield_modify";
                    addData1(pidx, treeNode, optype);
					return false;
                });
            }
        }
        return;
    }
};
//鼠标移除后的hover事件
function removeHoverDom(treeId, treeNode) {
    $("#addBtn_" + treeNode.tId).remove();
    $("#editBtn_" + treeNode.tId).remove();
    $("#typeBtn_" + treeNode.tId).remove();
};
function showRemoveBtn(treeId, treeNode) {
    return true;
}
//showRenameBtn  这个函数是节点重命名按钮，我这里设置为false了，你可以设置为true，然后编辑按钮就可以弹出来以做编辑工作
function showRenameBtn(treeId, treeNode) {
    return false;
}
function beforeDrag(treeId, treeNodes) {
    return false;
}
function beforeEditName(treeId, treeNode) {
    className = (className === "dark" ? "": "dark");
    var zTree = $.fn.zTree.getZTreeObj("treeDemo");
    zTree.selectNode(treeNode);
    return confirm("进入节点 -- " + treeNode.name + " 的编辑状态吗？");
}
function beforeRemove(treeId, treeNode) {
    //className = (className === "dark" ? "": "dark");
    var zTree = $.fn.zTree.getZTreeObj("treeDemo");
    zTree.selectNode(treeNode);
    if(treeNode.isParent) {
        return confirm("该节点含有子节点，删除后所有子节点将被一并被删除，是否确认删除 节点 -- " + treeNode.name + " 吗？");
    } else {
        return confirm("确认删除 节点 -- " + treeNode.name + " 吗？");
    }
}
function getChildren(ids, treeNode) {
    ids.push(treeNode.id);
    if (treeNode.isParent) {
        for (var obj in treeNode.children) {
            getChildren(ids, treeNode.children[obj]);
        }
    }
    return ids;
}
function remove_treenode(remove_treenode_id) {
    for (var i = 0; i < zNodes.length; i++) {
        var znode_obj = zNodes[i];
        var znode_obj_id = zNodes[i].id;
        if (znode_obj_id == remove_treenode_id) {
            zNodes.splice(i, 1);
        }
    }
}
function onRemove(e, treeId, treeNode) {
    var ids = [];
    ids = getChildren(ids, treeNode);
    for (var i = 0; i < ids.length; i++) {
        var remove_treenode_id = ids[i];
        remove_treenode(remove_treenode_id);
    }
}
function beforeRename(treeId, treeNode, newName, isCancel) {
    className = (className === "dark" ? "": "dark");
    if (newName.length == 0) {
        alert("节点名称不能为空.");
        var zTree = $.fn.zTree.getZTreeObj("treeDemo");
        setTimeout(function() {
            zTree.editName(treeNode)
        },
        10);
        return false;
    }
    return true;
}

function onRename(e, treeId, treeNode, isCancel) {
}
function clearZtree(){
	zNodes = [];
}
function getDatatypeSelect(selectedType){
	var typeArray = new Array();
	typeArray.push({"type":SE_TYPE_INT32,"str":"uint32"});
	typeArray.push({"type":SE_TYPE_STRING,"str":"string"});
	typeArray.push({"type":SE_TYPE_ARRAY,"str":"array"});
	typeArray.push({"type":SE_TYPE_OBJECT,"str":"object"});
	typeArray.push({"type":SE_TYPE_TIMESTAMP,"str":"date"});
	
	var html = "";
	for(var i=0;i<typeArray.length;i++){
		if((typeArray[i].type) == selectedType){
			html += '<option selected="true" value="'+typeArray[i].type+'">'+typeArray[i].str+'</option>';
		}else{
			html += '<option value="'+typeArray[i].type+'">'+typeArray[i].str+'</option>';
		}
	}
	return html;
}

function getDatatypeEx(zNodes,parentNode){
	//根据树形结构生成datatype_ex
	var datatype_ex = {};
	if(parentNode != undefined && parentNode != null){
		if(parentNode.col_type != SE_TYPE_ARRAY && parentNode.col_type != SE_TYPE_OBJECT){
			var tmpobj = {};
			tmpobj.col_type = parentNode.col_type;
			tmpobj.col_type_ex = null;
			datatype_ex = tmpobj;
		}
		var childNodes = getChildzNodes(zNodes,parentNode);
		for(var i=0;i<childNodes.length;i++){
			if(SE_TYPE_ARRAY == parentNode.col_type){
				//var tmpele = {};
				//tmpele.col_type = childNodes[i].col_type;
				//tmpele.col_type_ex = getDatatypeEx(zNodes,childNodes[i]);
                if(datatype_ex.col_type === undefined){
                    datatype_ex.col_type = SE_TYPE_ARRAY;
                }
                if(datatype_ex.col_type_ex === undefined){
                    datatype_ex.col_type_ex = {};
                }
				datatype_ex.col_type_ex.ele_col = getDatatypeEx(zNodes,childNodes[i]);
			}else if(SE_TYPE_OBJECT == parentNode.col_type){
                if(datatype_ex.col_type === undefined){
                    datatype_ex.col_type = SE_TYPE_OBJECT;
                }
                if(datatype_ex.col_type_ex === undefined){
                    datatype_ex.col_type_ex = {};
                }
                if(datatype_ex.col_type_ex.sc_map === undefined){
                    datatype_ex.col_type_ex.sc_map = {};
                }
				datatype_ex.col_type_ex.sc_map[childNodes[i].name] = getDatatypeEx(zNodes,childNodes[i]);
			}else{
				datatype_ex.col_type = parentNode.col_type;
			}
		}
	}
	return datatype_ex;
}
function getChildzNodes(zNodes,parentNode){
	var childNodes = [];
	for(var i=0;i<zNodes.length;i++){
		if(zNodes[i].pId == parentNode.id){
			childNodes.push(zNodes[i]);
		}
	}
	return childNodes;
}
function addData1(pIdx, treeNode, optype) {
    if (optype == "phyfield_add") {
        var dialogtitle = "新增子节点";
    } else if (optype == "phyfield_modify") {
        var dialogtitle = "修改子节点";
    }
    var p_datatype = treeNode.col_type;
    var childhtml = '<div style="text-align:center;">'+dialogtitle+'</div>';
    childhtml += '<table class="formtable"><tr><td class="tdleft">数据类型:</td><td><select id="addfield_datatype_text1">';
    //添加一个字段treenode就是parent			
    if(optype == "phyfield_add"){
        //按照默认顺序显示字段类型
        childhtml+= getDatatypeSelect();
        //添加孩子字段
        //如果是Array,那么只要给出字段类型就可以
    }
    //修改一个字段那么treenode就是本身
    else{
        //显示被修改的treenode的字段类型
        childhtml+= getDatatypeSelect(parseInt(treeNode.col_type));
    }
    /*
    if(p_datatype == SE_TYPE_ARRAY && pIdx == 0){
        childhtml+= '<tr>'+
            '<td class="tdleft">字段名:</td>'+
            '<td>'+
            '<input type="text" name="addfield_name_text1" id="addfield_name_text1"/>'+
            '</td>'+
            '<td></td>'+
            '<td class="tdtip" id="ro_name_tip1"></td>'+
            '</tr>';
    }
    else if(p_datatype == SE_TYPE_OBJECT){
        childhtml+= '<tr>'+
            '<td class="tdleft">字段名:</td>'+
            '<td>'+
            '<input type="text" name="addfield_name_text1" id="addfield_name_text1"/>'+
            '</td>'+
            '<td></td>'+
            '<td class="tdtip" id="ro_name_tip1"></td>'+
            '</tr>';
    };
    */
    if(p_datatype != SE_TYPE_ARRAY){
        childhtml+= '<tr>'+
            '<td class="tdleft">字段名:</td>'+
            '<td>'+
            '<input type="text" name="addfield_name_text1" id="addfield_name_text1"/>'+
            '</td>'+
            '<td></td>'+
            '<td class="tdtip" id="ro_name_tip1"></td>'+
            '</tr>';
    }
    childhtml+=	'</select>'+
        ' </td>'+
        '</tr>';
    childhtml+=  '</table></div>';
    childhtml+= '<button class="btn btn-primary btn-sm pull-right" id="btn_add_mod_child">确定</button><button class="btn btn-primary btn-sm pull-right" id="btn_canclea_add_mod_child">取消</button>';
    $("#child_node_div").empty().append(childhtml);

    $("#btn_add_mod_child").unbind("click");
    $("#btn_add_mod_child").bind("click",function(e){
        var child_datatype = $("#addfield_datatype_text1").val();
        var addfield_name = "";
        if(p_datatype == SE_TYPE_OBJECT || $("#addfield_name_text1").length > 0 ){
            addfield_name = $("#addfield_name_text1").val().trim();
        }
        if(optype == "phyfield_modify"){
            for(var i=0,ilen=zNodes.length;i<ilen;i++){
                if(treeNode.id == zNodes[i].id){
                    zNodes[i].col_type= child_datatype;
                }
            }
        }
        var obj = {};
        obj.pId = pIdx;
        obj.type = optype;
        obj.col_type = child_datatype;
        //obj.col_type_ex = getDatatypeEx(zNodes,zNodes[0]).col_type_ex;
        obj.col_type_ex = null;
        obj.label = addfield_name;
        obj.name = addfield_name;
        obj.id = treeNode.id;
        if (optype == "phyfield_add") {
            zNodes.push(obj);
            id_i++;
            obj.id = id_i;
            var zTree = $.fn.zTree.getZTreeObj("treeDemo");
            zTree.addNodes(treeNode, obj);
        } else {
            var fieldid = treeNode.id;
            for (var i = 0; i < zNodes.length; i++) {
                if (zNodes[i].id == fieldid) {
                    zNodes[i] = obj;
                }
            }
        }
        $.fn.zTree.init($("#treeDemo"), setting, zNodes);
        $('#child_node_div').empty();
    });
    $("#btn_canclea_add_mod_child").bind("click",function(){
        $('#child_node_div').empty();
    });
}
    function initNodes() {
        var obj = {};
        obj.id = 1;
        obj.pId = 0;
        obj.label = $("#const_label").val();
        obj.name = $("#const_name").val();
        obj.col_type = $("#const_datatype").val();
        obj.open = true;
        if (zNodes.length < 1) {
            zNodes.push(obj);
        }else{
            zNodes = new Array();
            zNodes.push(obj);
        }
    }

    function datatypeCheck(){
        var select_value = $("#const_datatype").val();
        if (select_value == SE_TYPE_ARRAY || select_value == SE_TYPE_OBJECT) {
            var name = $("#const_name").val();
            if (name == "") {
                alert("选择array或者object时请预先输入字段名");
                return;
            }
            initNodes();
            $.fn.zTree.init($("#treeDemo"), setting, zNodes);
            $("#div_tree").css({"display":"block", "border":"1px gray solid"});
        } else {
            $("#div_tree").css({"display":"none", "border":"1px gray solid"});
        }
    }

    function clickCloseTree() {
        $("#div_tree").css({"display":"none", "border":"1px gray solid"});
    }
    function addDef(){
        var ty = "phyfield_add";
        var tmpobj = {};
        //tmpobj.paramtype = $("#const_sourcepath").val();
        tmpobj.label = $("#const_label").val();
        tmpobj.name = $("#const_name").val();
        tmpobj.col_type = $("#const_datatype").val();
        if(tmpobj.col_type == SE_TYPE_ARRAY || tmpobj.col_type == SE_TYPE_OBJECT){
            var part_root_obj = zNodes[0];
            //修改部分
            tmpobj = {};
            tmpobj.type = ty;
            var fid = zNodes[0].id;
            if(ty == "phyfield_modify"){
                fid = $("#addfielddiv").attr("fieldid");
            }
            tmpobj.id = fid;
            tmpobj.label = $("#const_label").val();
            tmpobj.col_type = $("#const_datatype").val();
            tmpobj.col_type_ex = getDatatypeEx(zNodes,zNodes[0]).col_type_ex;
            tmpobj.name = part_root_obj.name;
            tmpobj.open = true;
            zNodes.splice(0, 1, tmpobj);
            clearZtree();
        }
        return tmpobj;
    }
    if(typeof myParamDef.init !== 'function'){
        myParamDef.init = function(successResult, array_result, checkedNodes, rootname){
            array_result = array_result === undefined ? false : array_result;
            $("#paramdefbox").remove();
            var showName = '定义所需参数';
            var paramdefhtml = "";
            paramdefhtml += "<fieldset>";
            paramdefhtml += "<legend>"+showName+"</legend>";
            paramdefhtml += "<div>";
            /*添加常量定义,这个用来做为模板的const*/
            paramdefhtml += "<div style='float:left;width:200px;'>"; //const start;
            /*
            paramdefhtml += "<div><span>参数类型:</span><span>"; //paramsource start 
            paramdefhtml += "<select id='const_sourcepath'>";
            paramdefhtml += "<option value='1'>常量</option>";
            paramdefhtml += "<option value='2'>参数定义</option>";
            paramdefhtml += "<option value='3'>运行时参数</option>";
            paramdefhtml += "<option value='4'>父参数</option>";
            paramdefhtml += "<option value='5'>抓取记录</option>";
            paramdefhtml += "</select>";
            paramdefhtml += "</span></div>"; //paramsource end 
            */
            paramdefhtml += "<div><span>字段标签:</span><span><input type='text' name='const_label' id='const_label' value='"+rootname+"' /></span></div>";
            paramdefhtml += "<div><span>字段名:</span><span><input type='text' name='const_name' id='const_name' value='"+rootname+"' /></span></div>";
            paramdefhtml += "<div><span>数据类型:</span><span>"; //select start
            paramdefhtml += "<select id='const_datatype'>";
            paramdefhtml += "<option value='5'>int</option>";
            paramdefhtml += "<option value='13'>string</option>";
            paramdefhtml += "<option value='18'>date</option>";
            paramdefhtml += "<option value='24'>array</option>";
            paramdefhtml += "<option value='23'>object</option>";
            paramdefhtml += "</select>";
            paramdefhtml += "</span></div>"; //select end
            if(array_result){
                paramdefhtml += "<div><span><input type='button' name='const_add_btn' id='const_add_btn' value='添加' /></span></div>";
                paramdefhtml += "<div id='const_added_div'>"; //已添加的定义
                paramdefhtml += "</div>";
            }
            paramdefhtml += "</div>"; //const end;
            paramdefhtml += "<div style='width:250px;float:right;'>"; //ztree start
            paramdefhtml += "<div id='div_tree' style='display:none;border:1px gray solid;'>";
            paramdefhtml += "<div class='zTreeDemoBackground left'>";
            paramdefhtml += "<ul id='treeDemo' class='ztree'></ul>";
            paramdefhtml += "<div style='text-align:right;margin-right:2px;margin-bottom:2px;'>";
            paramdefhtml += "<input type='button' value='关闭'  id='btn_tree_close' />";
            paramdefhtml += "</div>";
            paramdefhtml += "</div>";					
            paramdefhtml += "</div>";			
            paramdefhtml += "<div id='child_node_div'></div>";
            paramdefhtml += "</div>"; //ztree end

            paramdefhtml += "</div>";
            paramdefhtml += "</fieldset>";
            $("<div id='paramdefbox' title='"+showName+"列表'></div>").insertAfter("body");
            $("#paramdefbox").append(paramdefhtml);
            if(checkedNodes){
                zNodes = checkedNodes;
                $("#const_datatype").val(zNodes[0].col_type);
                $("#div_tree").css({"display":"block", "border":"1px gray solid"});
                $.fn.zTree.init($("#treeDemo"), setting, checkedNodes);
            }
            $("#paramdefbox").dialog({
                autoOpen: true,
                modal:true,
                width:550,
                buttons:{
                    "确定":function(){
                        var retNodes = zNodes;
                        if(array_result){
                            checkedVal = [];
                            $("#const_added_div").find("span[name=const_span]").each(function(i, item){
                                var ret = jQuery.parseJSON($(item).attr("code"));
                                checkedVal.push(ret);
                            });
                        }
                        else{
                            checkedVal = addDef();
                        }
                        successResult(checkedVal, retNodes);
                        $("#paramdefbox").dialog("close");
                    },
                "取消":function(){
                    $("#paramdefbox").dialog("close");
                }
                },
                close:function(){
                }
            });
            //事件处理
            $("#const_add_btn").unbind("click");
            $("#const_add_btn").bind("click", function(){
                var tmpobj = addDef();
                var spanhtml = "";
                spanhtml += "<span class='selwordsbox'>";
                spanhtml += "<span name='const_span' code='"+JSON.stringify(tmpobj)+"'>"+tmpobj.label+"</span>";
                spanhtml += "<a class='cancleitem'>×</a>";
                spanhtml += "</span>";
                $("#const_added_div").append(spanhtml);
            });
            $("#const_datatype").unbind("change");
            $("#const_datatype").bind("change", function(){
                datatypeCheck();
            });
            $("#btn_tree_close").unbind("click");
            $("#btn_tree_close").bind("click", function(){
                clickCloseTree();
            });
        };
    }
}());
