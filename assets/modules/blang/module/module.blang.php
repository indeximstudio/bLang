<?php


use bLang\bLang;
use bLang\bLangModule;
use bLang\translate;

if (IN_MANAGER_MODE != "true" || empty($modx) || !($modx instanceof DocumentParser)) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
}
if (!$modx->hasPermission('exec_module')) {
    header("location: " . $modx->getManagerPath() . "?a=106");
}
include_once(MODX_BASE_PATH . 'assets/snippets/DocLister/lib/DLTemplate.class.php');
include_once(MODX_BASE_PATH . 'assets/modules/bLang/classes/bLangModule.php');
include_once(MODX_BASE_PATH . 'assets/modules/bLang/classes/bLang.php');
include_once(MODX_BASE_PATH . 'assets/modules/bLang/module/actions/params.php');
include_once(MODX_BASE_PATH . 'assets/modules/bLang/module/actions/settings.php');

$action = isset($_GET['action'])?$_GET['action']:'home';
$moduleurl = 'index.php?a=112&id=' . $_GET['id'] . '&';
$modulePath = MODX_BASE_PATH . 'assets/modules/bLang/module/';

$tpl = DLTemplate::getInstance($modx);
$tpl->setTemplatePath('assets/modules/bLang/module/templates/');
$tpl->setTemplateExtension('tpl');

$bLang =  bLang::GetInstance($modx);
$bLangModuleObj = new bLangModule($modx,$bLang,$modulePath,$moduleurl);

$bLangTranslate = new translate($modx,$bLang);

//название таблиц
$B = $modx->getFullTableName('blang');
$BT = $modx->getFullTableName('blang_tmplvars');
$BTT = $modx->getFullTableName('blang_tmplvar_templates');
$BS = $modx->getFullTableName('blang_settings');

$data = [
    'moduleurl' => $moduleurl,
    'manager_theme' => $modx->config['manager_theme'],
    'action' => $action,
    'stay.'.$_SESSION['stay'] => 'selected',
    'selected' => [$action => 'selected']
];

$moduleLang = $bLangModuleObj->getModuleLang();
foreach ($moduleLang as $key => $value) {
    $data['_'.$key] = $value;
}

$bLangParamsObj = new \bLang\params($modx,$bLangModuleObj,$bLang,$tpl);
$bLangSettingsObj = new \bLang\settings($modx,$tpl,$bLang,$bLangModuleObj,$data);




switch ($action) {
    case 'home':
        foreach ($bLang->languages as $lang) {


            $data['lang_columns'] .= $tpl->parseChunk('@CODE:{ fillspace:true,id:"[+lang+]",  header:["[+lang+]",{content:"selectFilter"}], editor:"text",	  name:"[+lang+]"  },',[
                'lang'=>$lang,
            ]);
        }

        $outTpl = $tpl->parseChunk('@FILE:home', $data,true);
        break;

    case 'getVocabulary':
        $outData = $modx->db->makeArray($modx->db->select('*',$B,'','id asc'));
        break;
    case 'saveVocabulary':

        $data = $_POST;
        $webix_operation = $data['webix_operation'];
        unset($data['webix_operation']);

        if(empty($data)){
            $data = ['title'=>''];
        }
        $data = $modx->db->escape($data);
        $rowId = intval($data['id']);
        $outData = ['status'=>false];
        switch ($webix_operation){
            case 'insert':
                $newId = $modx->db->insert($data,$B);
                if(!empty($newId)){
                    $outData = ['status'=>true,'newid'=>$newId];
                }
                break;
            case 'update':
                $status = $modx->db->update($data,$B,"id = $rowId");
                if(!empty($status)){
                    $outData = ['status'=>true];
                }
                break;
            case 'delete':
                $status = $modx->db->delete($B,"id = $rowId");
                if(!empty($status)){
                    $outData = ['status'=>true];
                }
                break;
        }
        break;
    case 'translate':

        $data = $_POST;
        $newData = [];
        $fromLanguage = '';
        $str = '';
        foreach ($bLang->languages as $lang) {
            if (!empty($data[$lang])) {
                $fromLanguage = $lang;
                $str = $data[$lang];
                break;
            }
        }
        foreach ($bLang->languages as $lang) {
            if ($lang == $fromLanguage || !empty($data[$lang])) {
                continue;
            }

            if (!empty($data[$lang])) {
                continue;
            }
            $translation = $bLangTranslate->translate($str, $fromLanguage, $lang);
            $newData[$lang] = $translation;
        }
        $outData = $newData;
        break;

    case 'settings':

        if(!empty($_POST)){
            foreach ($_POST as $name => $value) {
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
            $bLangModuleObj->createColumn($_POST['langs']);
        }
        $bLang->loadSettings();
        $bLangSettings = $bLang->getSettings();
        foreach ($bLangSettings as $name => $value) {
            $data['setting_'.$name] = $value;
        }
        $outTpl = $tpl->parseChunk('@FILE:settings', $data,true);

        $outTpl = $bLangSettingsObj->renderForm();
        break;
    case 'createDefaultParams':
        $bLangModuleObj->createDefaultParams();
        $modx->sendRedirect($moduleurl.'action=params');
        break;

    case 'paramForm':
        $outTpl = $bLangParamsObj->getAndProcessParamForm();
        break;
    case 'updateTV':
        $bLangParamsObj->updateTV();

        break;
    //Выводим список параметров
    case 'deleteParams':
        $bLangParamsObj->deleteParam();
        break;
    case 'params':
        $outTpl = $bLangParamsObj->getParamsList();
        break;
}


if (!is_null($outTpl)) {

    $output = $tpl->parseChunk('@FILE:header', $data) . $outTpl . $tpl->parseChunk('@FILE:footer', $data);
} else if (is_array($outData)) {
    header('Content-type: application/json');
    $output = json_encode($outData, JSON_UNESCAPED_UNICODE);
}

echo $output;
