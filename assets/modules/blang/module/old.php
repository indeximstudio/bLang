<?php

return ;











//старие настройки плагина
$fields = $GLOBALS['bLangSetting']['fields'];
$translate = $GLOBALS['bLangSetting']['translate'];
$languages = $GLOBALS['bLangSetting']['langs'];
$root = $GLOBALS['bLangSetting']['root'];
$yandexKey = $GLOBALS['bLangSetting']['yandexKey'];

// echo $yandexKey;

$langs = explode(',', $languages);



$moduleurl = 'index.php?a=112&id=' . $_GET['id'] . '&';
$modulePath = MODX_BASE_PATH . 'assets/modules/blang/';

$GLOBALS['moduleUrl'] = $moduleurl;


$action_full = isset($_GET['action']) ? $_GET['action'] : 'home';

$data = array('moduleurl' => $moduleurl, 'manager_theme' => $modx->config['manager_theme'], 'session' => $_SESSION, 'action' => $action_full, 'selected' => array($action_full => 'selected'));


$table = $modx->getFullTableName('blang');
$BT = $modx->getFullTableName('blang_tmplvars');
$BS = $modx->getFullTableName('blang_settings');

$tbl_blang_tmplvar_templates = $modx->getFullTableName('blang_tmplvar_templates');
$tbl_site_tmplvar_templates = $modx->getFullTableName('site_tmplvar_templates');

$T = $modx->getFullTableName('site_tmplvars');



//Подключаем обработку шаблонов через DocLister
include_once(MODX_BASE_PATH . 'assets/snippets/DocLister/lib/DLTemplate.class.php');
require_once 'translate/src/Translation.php';
require_once 'translate/src/Translator.php';
require_once 'translate/src/Exception.php';
$tpl = DLTemplate::getInstance($modx);



$manager_theme = $modx->getConfig('manager_theme');
// include_once the style variables file

if (isset($manager_theme) && !isset($_style)) {
    $_style = array();
    include MODX_BASE_PATH . "manager/media/style/" . $manager_theme . "/style.php";
}


//язык modx


$_lang = array();
$manager_language = $modx->getConfig('manager_language');
if (!isset($manager_language) || !file_exists(MODX_MANAGER_PATH . "includes/lang/" . $manager_language . ".inc.php")) {
    $manager_language = "english"; // if not set, get the english language file.
}
include MODX_MANAGER_PATH . "includes/lang/english.inc.php";
if ($manager_language != "english" && file_exists(MODX_MANAGER_PATH . "includes/lang/" . $manager_language . ".inc.php")) {
    include "lang/" . $manager_language . ".inc.php";
}
//язык модуля
if (file_exists($modulePath . "lang/" . $manager_language . ".inc.php")) {
    include $modulePath . "lang/" . $manager_language . ".inc.php";
}

foreach ($_lang as $key => $val) {
    $data['_' . $key] = $val;
}





switch ($action_full) {
    case 'home':

        foreach ($langs as $lang) {
            $data['lang_columns'] .= '{ fillspace:true,id:"' . $lang . '", editor:"text",	  name:"' . $lang . '",  header:"' . $lang . '",},';
            $data['lang_input'] .= '{view: "text",  name: "' . $lang . '", label: "' . $lang . '"},';
        }


        //параметры
        include(MODX_BASE_PATH . "/assets/modules/blang/actions/resources.static.php");

        $data['tvLst'] = createResourceList('blang_tmplvars', 301);

        //настройки

        $resp = $modx->db->makeArray($modx->db->query("select * from ".$BS));
        foreach ($resp as $field) {
            $data['setting_'.$field['name']] = $field['value'];
        }

        //удаление языков

        //languages_options
        if(!empty($data['setting_langs'])){
            $languages = explode(',',$data['setting_langs']);
            foreach ($languages as $lang) {
                $data['languages_options'] .= '<option>'.$lang.'</option>';
            }
        }



        $template = '@CODE:' . file_get_contents(dirname(__FILE__) . '/templates/home.tpl');
        $outTpl = $tpl->parseChunk($template, $data);



        break;


    case 'removeLanguage':

        $lang = $_POST['lang'];
        $fields = $modx->db->makeArray($modx->db->query("select * from ".$BT));
        if(is_array($fields)){
            foreach ($fields as $field) {
                $name = $field['name'].'_'.$lang;
                $name = $modx->db->escape($name);
                $tvId = $modx->db->getValue("select id from ".$T." where name = '".$name."'");
                if(!empty($tvId)){
                    // delete variable
                    $modx->db->delete($modx->getFullTableName('site_tmplvars'), "id='{$tvId}'");

                    // delete variable's content values
                    $modx->db->delete($modx->getFullTableName('site_tmplvar_contentvalues'), "tmplvarid='{$tvId}'");

                    // delete variable's template access
                    $modx->db->delete($modx->getFullTableName('site_tmplvar_templates'), "tmplvarid='{$tvId}'");

                    // delete variable's access permissions
                    $modx->db->delete($modx->getFullTableName('site_tmplvar_access'), "tmplvarid='{$tvId}'");
                }

            }
        }




        break;

    case 'settings-save':
        $fields = $_POST;
        foreach ($fields as $name => $value) {

            $name = $modx->db->escape($name);
            $value = $modx->db->escape($value);
            $old = $modx->db->getValue("select name from  " . $BS . " where name = '" . $name . "'");
            $fields = [
                'name' => $name,
                'value' => $value,
            ];
            if (empty($old)) {
                $modx->db->insert($fields, $BS);
            } else {
                $modx->db->update($fields, $BS, "name = '" . $name . "'");
            }
        }
        $header="Location: ".$GLOBALS['moduleUrl'];
        header($header);


        break;
    case 'updateTV':
        function prepareFields($fields, $lang)
        {
            global $modx;
            $prepareFields = [];
            foreach ($fields as $key => $val) {
                $prepareFields[$key] = str_replace('[lang]', $lang, $modx->db->escape($val));
            }
            $prepareFields['name'] = $prepareFields['name'] . '_' . $lang;
            unset($prepareFields['tab']);
            unset($prepareFields['id']);
            return $prepareFields;
        }


        $tvs = $modx->db->makeArray($modx->db->query("select * from " . $BT));
        if (is_array($tvs)) {
            foreach ($tvs as $fields) {
                $blangFieldId = $fields['id'];
                foreach ($langs as $lang) {
                    $prepareFields = prepareFields($fields, $lang);
                    $name = $prepareFields['name'];


                    //ищем тв в стандартной таблице
                    $tvId = $modx->db->getValue("select id from " . $T . " where name = '" . $name . "'");
                    if (empty($tvId)) {
                        $tvId = $modx->db->insert($prepareFields, $T);
                    } else {
                        $modx->db->update($prepareFields, $T, 'id = ' . (int)$tvId);
                    }

                    //привязка шаблонов
                    $templates = $modx->db->makeArray($modx->db->query("select * from " . $tbl_blang_tmplvar_templates . " where tmplvarid='" . $blangFieldId . "'"));


                    $modx->db->delete($tbl_site_tmplvar_templates, "tmplvarid='{$tvId}'");

                    foreach ($templates as $template) {
                        $templateId = $modx->db->escape($template['templateid']);

                        $resp = $modx->db->insert(
                            array(
                                'tmplvarid' => $tvId,
                                'templateid' => $templateId,
                                'rank' => 0,
                            ), $tbl_site_tmplvar_templates);
                    }


                }

            }
        }
        $header="Location: ".$GLOBALS['moduleUrl'];
        header($header);


        break;

    case 'paramDefault':

        $params = json_decode(file_get_contents($modulePath . 'actions/default_fields.json'), true);
        if (is_array($params)) {
            foreach ($params as $fields) {

                foreach ($fields as $key => $val) {
                    $fields[$key] = $modx->db->escape($val);
                }
                $name = $modx->db->escape($fields['name']);
                $category = $fields['category'];

                include_once(MODX_MANAGER_PATH . 'includes/categories.inc.php');
                $categoryid = checkCategory($category);
                if (!$categoryid) {
                    $categoryid = newCategory($category);
                }
                $fields['category'] = $categoryid;

                $id = $modx->db->getValue("select id from " . $BT . " where name = '" . $name . "'");
                if (empty($id)) {
                    $modx->db->insert($fields, $BT);
                }
            }
        }
        $header = "Location: " . $GLOBALS['moduleUrl'];
        header($header);
        break;
    case 'paramDelete':

        include_once($modulePath . "processors/delete_tmplvars.processor.php");

        break;
    case 'paramSave':
        include_once($modulePath . "processors/save_tmplvars.processor.php");


        break;
    case 'paramSort':
        include_once($modulePath . "includes/header.inc.php");
        include_once($modulePath . "actions/mutate_tv_rank.dynamic.php");
        include_once($modulePath . "includes/footer.inc.php");
        break;
    case 'param':
        $modx->manager->action = 300;
        if (!empty($_GET['elemId'])) {
            $modx->manager->action = 301;
        }

        include_once($modulePath . "includes/header.inc.php");
        include_once($modulePath . "actions/mutate_tmplvars.dynamic.php");
        include_once($modulePath . "includes/footer.inc.php");


        // $template = '@CODE:' . file_get_contents(dirname(__FILE__) . '/templates/param.tpl');
        // $outTpl = $tpl->parseChunk($template, $data);
        break;

    case 'getData':
        $sql = "select * from " . $table;
        $q = $modx->db->query($sql);
        $outData = $modx->db->makeArray($q);

        foreach ($outData as $key => $val) {
//            $outData[$key]['name'] = '[(__'.$outData[$key]['name'].')]';
        }

        break;
    case 'translate':

        $data = $_POST;

        $newData = [];

        $fromLanguage = '';
        $str = '';
        foreach ($langs as $lang) {
            if (!empty($data[$lang])) {
                $fromLanguage = $lang;
                $str = $data[$lang];
            }
        }
        foreach ($langs as $lang) {
            if ($lang == $fromLanguage || !empty($data[$lang])) {
                continue;
            }
            $transLang = $lang;
            $transFromLanguage = $fromLanguage;

            if(!empty($data[$lang])){
                continue;
            }

            switch ($lang) {
                case 'ua':
                    $transLang = 'uk';
                    break;
            }
            switch ($fromLanguage) {
                case 'ua':
                    $transFromLanguage = 'uk';
                    break;
            }

            $translator = new Yandex\Translate\Translator($yandexKey);
            $translation = $translator->translate($str, $transFromLanguage . '-' . $transLang);
            $newData[$lang] = (string)$translation;
        }
        $newData[$fromLanguage] = $str;


        $outData = $newData;


        break;
    case 'checkName':
        $id = $modx->db->escape($_POST['id']);
        $name = $modx->db->escape($_POST['name']);

        $columndId = $modx->db->getValue("select id from ".$table." where name = '".$name."' and id != ".$id);
        if(empty($columndId)){
            echo 1;
        }
        else{
            echo 0;
        }


        break;
    case 'save':

        $data = [];
        foreach ($_POST as $key => $item) {
            if ($key == 'webix_operation') {
                continue;
            }
            $data[$key] = $modx->db->escape($item);
        }

        if($_POST['webix_operation'] == 'insert'){
            return '';
        }
        $recordId = 0;

        if (!empty($_POST['webix_operation']) && $_POST['webix_operation'] == 'delete') {
            $modx->db->delete($table, 'id = ' . (int)$_POST['id']);
        } else if (empty($_POST['id'])) {
            if(empty($data)){
                $data['title']= '';
            }
            $recordId =  $modx->db->insert($data, $table);

        } else {
            $recordId = $modx->db->update($data, $table, 'id = ' . (int)$_POST['id']);


        }
        echo $recordId;
        $modx->clearCache('full');
        break;
}


if (!is_null($outTpl)) {
    $headerTpl = '@CODE:' . file_get_contents(dirname(__FILE__) . '/templates/header.tpl');
    $footerTpl = '@CODE:' . file_get_contents(dirname(__FILE__) . '/templates/footer.tpl');
    $output = $tpl->parseChunk($headerTpl, $data) . $outTpl . $tpl->parseChunk($footerTpl, $data);
} else if (is_array($outData)) {
    header('Content-type: application/json');
    $output = json_encode($outData, JSON_UNESCAPED_UNICODE);
}
if (!empty($output)) {
    echo $output;
}
