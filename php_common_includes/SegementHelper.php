<?php

initLogger(LOGNAME_WEBAPI);//初始化日志配置

function supplyParagraph4NewDoc(&$result, &$paragraphFiledValue, $general_pg_tmp, &$indirect_guid_query_conds, $content_type_tmp, $analysis_status_tmp, $isSegemented = false, $skipNum = 0)
{
    global $logger, $segementedParagraphFieldMapping;
    $hasSkiped = 0;
    $tmp = array();
    $tmp['content_type'] = $content_type_tmp;
    $tmp['analysis_status'] = $analysis_status_tmp;
    foreach ($paragraphFiledValue as $pgindex => $pg) {
        if ($hasSkiped < $skipNum) {
            $hasSkiped++;
            continue;
        }

        //如果数据已经分词，则根据路径取出分段之后文章的内容，并设置到新增的段落上面去
        $tmp['paragraphid'] = $pgindex + 1;

        if ($isSegemented) {
            if (empty($pg["pg_text"])) {
                throw new Exception("补充段落(newDoc)(文章已经分词)--+-设置段落guid失败,分段数据(List)中的文档未设置:[pg_text]字段! ParagraphDoc:[" . var_export($pg, true) . "].");
            }
            $tmp['pg_text'] = $pg["pg_text"];
            $tmp = array_merge($tmp, $general_pg_tmp);
            setOterField4ParagraphField($segementedParagraphFieldMapping, $pg, $tmp);
        } else {
            $tmp['pg_text'] = $pg;
            $tmp = array_merge($tmp, $general_pg_tmp);
        }

        //插入的段落guid
        $tmp['guid'] = setArticleGuid($tmp);
        if ($tmp['guid'] === false) {
            //return array("result" => false, "msg" => "设置段落guid失败");
            throw new Exception("补充段落(文章已经分词)--+-设置段落guid失败,分段数据(List)中的文档未设置:[pg_text]字段!");
        }

        //补充docguid标记,最后遍历 $indirect_guid_query_conds 中所有的文章
        //根据需要补充的字段的配置，如需要更新doc_guid时候，则"add_guid_doc"标记为:1
        //根据guid更新这些字段
        $indirect_tmp = array();
        $indirect_tmp['add_guid_doc'] = 1;
        $indirect_tmp = array_merge($tmp, $indirect_tmp);
        $indirect_guid_query_conds[] = $indirect_tmp;

        $result["send_solr_data"][] = $tmp;
        $logger->debug("新文章[" . ($isSegemented ? "没有分词" : "已分词") . "]，补充段落成功：" . var_export($tmp, true));
    }
}

function setOterField4ParagraphField(&$segementedParagraphFieldMapping, &$segementedDoc, &$newDoc)
{
    foreach ($segementedParagraphFieldMapping as $targetFieldName) {
        if (in_array($targetFieldName, $segementedDoc) && !empty($segementedDoc[$targetFieldName])) {
            $newDoc[$targetFieldName] = $segementedDoc[$targetFieldName];
        }
    }
}

function supplyParagraph4UpdateDoc(&$result, &$general_pg_tmp, &$indirect_guid_query_conds, &$oldparas, $content_type_tmp, $analysis_status_tmp, &$item, $update_pg_num, $isSegemented = false)
{
    global $logger, $segementedParagraphFieldMapping;
    //循环旧的数组
    $tmp = array();
    $tmp['content_type'] = $content_type_tmp;
    $tmp['analysis_status'] = $analysis_status_tmp;

    $hasUpdateNum = 0;

    foreach ($oldparas as $oldpk => $oldp) {
        $tmp['guid'] = $oldp['guid'];

        if ($hasUpdateNum >= $update_pg_num) {
            //添加需要删除的文档配置
            $result['delete_solr_data'][] = $tmp;
        } else {
            if ($isSegemented) {
                //只考虑更新 pg_text字段
                if (empty($item['pg_text'][$oldpk]["pg_text"])) {
                    throw new Exception("补充段落(updateDoc)(文章已经分词)--+-设置段落guid失败,分段数据(List)中的文档未设置:[pg_text]字段!");
                }
                $tmp['pg_text'] = $item['pg_text'][$oldpk]['pg_text'];
                //设置其他字段
                setOterField4ParagraphField($segementedParagraphFieldMapping, $item['pg_text'][$oldpk], $tmp);
            } else {
                $tmp['pg_text'] = $item['pg_text'][$oldpk];
            }

            $tmp['paragraphid'] = $oldpk + 1;
            $result['update_solr_data'][] = $tmp; //插入更新数组
            //$logger->debug("新段落>旧段落，更新段落：".var_export($tmp,true));

            if (empty($oldp['docguid'])) {
                $indirect_tmp = array();
                $indirect_tmp['add_guid_doc'] = 1;
                $indirect_tmp = array_merge($tmp, $general_pg_tmp, $indirect_tmp);
                $indirect_guid_query_conds[] = $indirect_tmp;
            }
        }

        //添加已经更新的文章数
        $hasUpdateNum++;
    }
}


/**
 * 在文章分段时候，将主文章的相关属性设置到段落文章中去
 * @param $general_pg_tmp
 * @param $item
 */
function copyFieldValue4ParagraphDoc(&$general_pg_tmp, &$item)
{
    $general_pg_tmp['original_url'] = $item['original_url'];
    if (isset($item['floor'])) {
        $general_pg_tmp['floor'] = $item['floor'];
    }
    $general_pg_tmp['source_host'] = $item['source_host'];
    if (isset($item['reply_father_floor'])) {
        $general_pg_tmp['reply_father_floor'] = $item['reply_father_floor'];
    }
    if (isset($item['child_post_id'])) {
        $general_pg_tmp['child_post_id'] = $item['child_post_id'];
    }
    if (isset($item['question_id'])) {
        $general_pg_tmp['question_id'] = $item['question_id'];
    }
    if (isset($item['answer_id'])) {
        $general_pg_tmp['answer_id'] = $item['answer_id'];
    }
    if (isset($item['question_father_id'])) {
        $general_pg_tmp['question_father_id'] = $item['question_father_id'];
    }
    if (isset($item['answer_father_id'])) {
        $general_pg_tmp['answer_father_id'] = $item['answer_father_id'];
    }
    if (isset($item['sourceid']))
        $general_pg_tmp['sourceid'] = $item['sourceid'];
    if (isset($item['mid']))
        $general_pg_tmp['mid'] = $item['mid'];
}

function addNedSupplyDocInfos($ispartialdata = false, $isCommit = TRUE, &$timeStatisticObj = null)
{
    global $indirect_guid_query_conds, $logger, $nedSupplyDocs;
    // add by wangcc
    //将需要补充的数据返回给调用者，最后在提交之后统一补充
    if (empty($nedSupplyDocs)) {
        $nedSupplyDocs["isCommit"] = $isCommit;//默认为false,最后统一提交
        $nedSupplyDocs["ispartialdata"] = $ispartialdata;//默认为true
        $nedSupplyDocs["docs"] = $indirect_guid_query_conds;
    } else {
        $nedSupplyDocs["docs"] = array_merge($nedSupplyDocs["docs"], $indirect_guid_query_conds);
    }
    //清空
    $logger->debug("本次添加需要补充的docId/fatherGuid/retweeted_guid 的文章信息：[" . count($indirect_guid_query_conds) . "]条,总共:[" . count($nedSupplyDocs["docs"]) . "条. 本次添加信息:" . var_export($indirect_guid_query_conds, true));
    $indirect_guid_query_conds = array();
}


