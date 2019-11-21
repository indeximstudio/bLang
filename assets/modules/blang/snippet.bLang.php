<?php
require_once MODX_BASE_PATH.'assets/snippets/DocLister/lib/DLTemplate.class.php';

use bLang\bLang;

$type = isset($type) ? $type : 'switch';
$bLang = bLang::GetInstance($modx);

$lang = $bLang->lang;
$suffix = $bLang->suffixes[$lang];



switch ($type) {
    case 'suffix':
        return $suffix;
        break;
    case 'alterTitle':
        $id = isset($id) ? $id : $modx->documentIdentifier;
        $lang = isset($lang) ? $lang : $modx->getConfig('_lang');
        $pageTitle = $modx->runSnippet('DocInfo', ['field' => 'pagetitle_' . $lang, 'docid' => $id]);
        $longTitle = $modx->runSnippet('DocInfo', ['field' => 'longtitle_' . $lang, 'docid' => $id]);
        echo empty($longTitle) ? $pageTitle : $longTitle;
        break;
    case 'menutitle':
        $id = isset($id) ? $id : $modx->documentIdentifier;
        $lang = isset($lang) ? $lang : $modx->getConfig('_lang');
        $pageTitle = $modx->runSnippet('DocInfo', ['field' => 'pagetitle_' . $lang, 'docid' => $id]);
        $menutitle = $modx->runSnippet('DocInfo', ['field' => 'menutitle_' . $lang, 'docid' => $id]);
        echo empty($menutitle) ? $pageTitle : $menutitle;
        break;
    case 'DocInfo':
        echo $modx->runSnippet('DocInfo', ['docid' => $docid, 'field' => $field . $suffix]);
        break;
    case 'switch':
        if (isset($pl)) return '[+' . $pl . $suffix . '+]';
        if (isset($f)) return '[*' . $f . $suffix . '*]';
        if (isset($s)) return $modx->getConfig($s . $suffix);

        echo $$lang;
        break;

    case 'list':

        include_once(MODX_BASE_PATH . 'assets/snippets/DocLister/lib/DLTemplate.class.php');
        $tpl = DLTemplate::getInstance($modx);
        //шаблоны
        $outerTpl = isset($outerTpl) ? $outerTpl : '@CODE:<div>[+active+][+list+]</div>';
        $activeTpl = isset($activeTpl) ? $activeTpl : '@CODE:<a class="active" href="[+url+]">[+title+]</a>';
        $listTpl = isset($listTpl) ? $listTpl : '@CODE:<ul>[+wrapper+]</ul>';
        $listRow = isset($listRow) ? $listRow : '@CODE:<li class="[+classes+]"><a href="[+url+]">[+title+]</a></li>';

        $languages = $bLang->languages;
        $activeLang = (string)$bLang->lang;


        $activeTitle = !empty($modx->getConfig('__' . $activeLang . '_title')) ? $modx->getConfig('__' . $activeLang . '_title') : $activeLang;
        $activeUrl = $modx->getConfig('_' . $activeLang . '_url');

        $active = $tpl->parseChunk($activeTpl, [
            'title' => $activeTitle,
            'url' => $activeUrl,
        ]);
        $listItems = '';
        foreach ($languages as $key => $lang) {
            $url = $modx->getConfig('_' . $lang . '_url');
            $title = !empty($modx->getConfig('_' . $lang . '_title')) ? $modx->getConfig('__' . $lang . '_title') : $lang;

            $class = ' lang-item';
            if ($lang == $activeLang) {
                $class .= ' active';
            }
            if ((count($languages) - 1) == $key) {
                $class .= ' last-lang-item';
            }
            $listItems .= $tpl->parseChunk($listRow, [
                'classes' => $class,
                'title' => $title,
                'url' => $url,
            ]);
        }
        $list = $tpl->parseChunk($listTpl, [
            'wrapper' => $listItems,
        ]);
        $outer = $tpl->parseChunk($outerTpl, [
            'active' => $active,
            'list' => $list,
        ]);
        echo $outer;
        break;
}
return;