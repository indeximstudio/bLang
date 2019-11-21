<?php

use bLang\bLang;
use bLang\translate;

$e = $modx->event;
require_once MODX_BASE_PATH . 'assets/modules/bLang/classes/bLang.php';
require_once MODX_BASE_PATH . 'assets/modules/bLang/classes/translate.php';
require_once MODX_BASE_PATH . 'assets/snippets/DocLister/core/DocLister.abstract.php';
require_once MODX_BASE_PATH . 'assets/modules/bLang/classes/bLangLexiconHandler.php';
require_once MODX_BASE_PATH . 'assets/modules/bLang/classes/lang_menu.php';
require_once MODX_BASE_PATH . 'assets/modules/bLang/classes/lang_content.php';

if (bLang::$isInit === false && in_array($e->name, ['OnMakeDocUrl'])) {
    return false;
}
/** @var  $bLang bLang */
$bLang = bLang::GetInstance($modx);
$bLangTranslate = new translate($modx, $bLang);
$settings = $bLang->getSettings();

switch ($e->name) {
    case 'OnWebPageInit':
  //      $bLangTranslate->translateDoc(8);
 //       die();
        $bLang->setClientSettingFields();
        break;
    case 'OnMakeDocUrl':
        if (intval($settings['autoUrl']) !== 1) {
            return true;
        }
        $url = $params['url'];
        if (bLang::$firstPageMakeUrl === true) {
            bLang::$firstPageMakeUrl = false;
            return true;
        }

        $url = $bLang->getLangUrl($url);
        $e->setOutput($url);

        break;
    case 'OnMakePageCacheKey':

       // echo 5;die();

        break;
    case 'OnLoadDocumentObject':
    //    echo 2;die();
        break;
    case 'OnLoadWebPageCache':
    //    echo 3;die();
        break;
    case 'OnAfterLoadDocumentObject':
  //   echo 4;die();
        if (intval($settings['autoFields']) !== 1) {
            return true;
        }
        $docObj = $e->params['documentObject'];

        $lang = $bLang->lang;
        $suffix = $bLang->suffixes[$lang];

        $fields = $modx->db->makeArray($modx->db->query("select * from " . $modx->getFullTableName('blang_tmplvars')));


        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $fieldFull = $fieldName . $suffix;

            $fieldValue = is_array($docObj[$fieldFull]) ? $docObj[$fieldFull][1] : $docObj[$fieldFull];

            if (is_array($docObj[$fieldName])) {
                $docObj[$fieldName][1] = $fieldValue;
            } else {
                $docObj[$fieldName] = $fieldValue;
            }
        }
        $e->setOutput($docObj);
        break;
    case 'OnBeforeClientSettingsSave':
        $bLangTranslate->translateClientSettings($params['fields']);
        break;
    case 'OnDocFormTemplateRender':

        $output = "
        <script >
        jQuery('#actions > .btn-group').append('<a id=\"ButtonTranslateBuilder\" class=\"btn btn-secondary\" href=\"javascript:;\"><i class=\"fa fa-clone\"></i><span>Перевести PB</span></a>');
        
        jQuery('#ButtonTranslateBuilder').click(function() {
            var jBtn = jQuery(this);
              var jText = jBtn.find('span');
            if(jBtn.hasClass('btn-active')){
                jQuery('#translatePageBuilder').remove();
              jBtn.removeClass('btn-active');
              jText.text('Перевести PB');
              jBtn.removeClass('btn-danger');
              jBtn.addClass('btn-secondary');
          }
            else{
                 jQuery('#mutate').prepend('<input type=\"hidden\" name=\"translatePageBuilder\" id=\"translatePageBuilder\" value=\"1\" />');
              jBtn.addClass('btn-active');
              jText.text('Не переводить PB');
              jBtn.addClass('btn-danger');
              jBtn.removeClass('btn-secondary'); 
            }
        })
        
        </script>
        ";
        $modx->event->addOutput($output);
        break;
    case 'OnDocFormSave':
        $docId = $params['id'];
        if (!empty($docId)) {
            $bLangTranslate->translateDoc($docId);
        };

        break;
}