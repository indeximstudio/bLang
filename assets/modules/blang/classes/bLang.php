<?php
namespace bLang;

class bLang
{
    public $lang;
    public $root;
    public $suffix;

    /** @var $modx \DocumentParser */
    private $modx;



    public $defaultLang = '';
    public $languages = [];
    public $roots = [];
    public $suffixes = [];

    private $settings = [];
    private $lexicon = [];
    private $config = [];


    private static $theirInstance;
    public static $isInit = false;
    public static $firstPageMakeUrl = true;


    public function getConfig(){
        return $this->config;
    }
    public function getSettings($name=''){
        if(!empty($name)){
            return $this->settings[$name];
        }
        return $this->settings;
    }
    /**
     * Грузим настройки из базы данных
     */
    public function loadSettings(){

        $table = $this->modx->getFullTableName('blang_settings');
        $settings = $this->modx->db->makeArray($this->modx->db->select('*',$table));
        $this->settings = array_column($settings,'value','name');

    }

    public function getLexicon($key=''){
        if(empty($key)){
            return $this->lexicon;
        }
        else if(isset($this->lexicon[$key])){
            return $this->lexicon[$key];
        }

        return '';

    }

    private function parseSettingString($string){
        $cfg = [];
        if(empty($string)) return $cfg;

        foreach (explode('||',$string) as $item) {

            if(strpos($string,'==') !== false){
                $itemArray = explode('==',$item);
                $cfg[$itemArray[0]] = $itemArray[1];
            }
            else{
                $cfg[] = $item;
            }
        }
        return $cfg;

    }
    /*
     * Устанавливаем параметры плагина относительно настроек модуля
     */
    private function setSettings()
    {
        //Список языков
        $this->languages =  $this->parseSettingString($this->settings['languages']);
        // суффиксы полей
        $this->suffixes =  $this->parseSettingString($this->settings['suffixes']);
        //язык по умолчанию
        $this->defaultLang = $this->settings['default'];

        foreach ($this->languages as $lang) {
            $this->roots[$lang] = $this->defaultLang == $lang ? '' : $lang . '/';
        }

    }
    private function Initialise($modx)
    {

        $this->modx = $modx;

        $this->loadSettings();;
        $this->setSettings();;


        $InListLang = $this->InListLang();
        $this->lang = !empty($_GET['lang']) && $InListLang?$_GET['lang']:$this->defaultLang;

        $this->root = $this->roots[$this->lang];
        $this->suffix = $this->suffixes[$this->lang];

        //проверяем есть ли язык с которым перешел пользователь в списке
        //вернет false если значение $_GET['lang'] отсутствует в спписке


//
        if (!$InListLang && !empty($_GET['lang'])) {
         $this->modx->sendErrorPage();
            return;
        }
//
        $this->setConfig();
    }


    public function InListLang()
    {
        $curr = $_GET['lang'] ?? '';
        $result = false;

        foreach ($this->languages as $key => $value) {
            if ($value == $curr) $result = true;
        }
        return $result;
    }


    private function __construct($modx)
    {

        $this->Initialise($modx);
    }

    /**
     * @param $modx
     * @return bLang
     */
    public static function GetInstance($modx)
    {
        if (!isset(self::$theirInstance)) {
            $c = __CLASS__;

            self::$theirInstance = new $c($modx);
            self::$isInit = true;

        }

        return self::$theirInstance;
    }

    public function getLangUrl($url,$lang = null)
    {
        $siteUrl = $this->modx->getConfig('site_url');
        $root = $this->root;

        if($lang !== null && isset($this->roots[$lang])){
            $root = $this->roots[$lang];
        }

        //если урл у нас пришел в абсолютном формате
        if (strpos($url, $siteUrl) !== false) {
            $url = str_replace($siteUrl, $siteUrl . $root, $url);
        } else {
            if (substr($url, 0, 1) === '/') {
                $url = substr($url, 1);
            }
            $url = '/' . $root . $url;
        }
        return $url;
    }

    public function setClientSettingFields()
    {
        $prefix = $this->settings['clientSettingsPrefix'];

        $translateFields = [];
        foreach (glob(MODX_BASE_PATH . 'assets/modules/clientsettings/config/*.php') as $file) {
            $config = include $file;
            if(empty($config['langFields'])) {
                continue;
            }
            $translateFields = array_merge($translateFields,$config['langFields']);

        }


        foreach ($translateFields as $fieldName) {
            $fieldName = $prefix.$fieldName;
            $fieldNameFull = $fieldName.$this->suffixes[$this->lang];
            $fieldValue = $this->modx->getConfig($fieldNameFull);

            if(method_exists($this->modx,'setConfig')){
                $this->modx->setConfig($fieldName,$fieldValue,true);
            }
            else{
                $this->modx->config[$fieldName] = $fieldValue;
            }
        }


    }

    private final function __clone()
    {
        throw new Exception('Clone is not allowed on singleton (LANG).');
    }





    private function setConfig()
    {



        $id = is_numeric($this->modx->documentIdentifier) ? $this->modx->documentIdentifier : $this->modx->getConfig('error_page');


        $config['lang'] = $this->lang;
        $config['root'] = $this->root;

        $lang = [
            '_lang'=>$this->lang,
            '_root'=>$this->root,
        ];

        foreach ($this->languages as $key => $value) {
            $config[$value . '_url'] = $this->getLangUrl($this->modx->makeUrl($id),$value);


            $lang['_'.$value . '_url'] = $this->getLangUrl($this->modx->makeUrl($id),$value);
        }




        $_LANG = parse_ini_string($this->modx->getChunk($this->lang));
        if (!empty($_LANG)) {
            foreach ($_LANG as $key => $value) {
                $config['_' . $key] = $value;
                $lang[$key] = $value;
                $this->lexicon[ $key] = $value;
            }
        }


        $q = $this->modx->db->query("select * from ".$this->modx->getFullTableName('blang'));
        $res = $this->modx->db->makeArray($q);

        foreach ($res as $item) {
            $key = $item['name'];
            $value = $item[$config['lang']];
            if (empty($key)) {
                continue;
            }
            $config['_' . $key] = $value;
            $lang[$key] = $value;

            $this->lexicon[ $key] = $value;
        }

        if(method_exists($this->modx,'addDataToView')){
            $this->modx->addDataToView(['lang'=>$lang]);
        }



        $this->config = $config;
        foreach ($config as $key => $value) {
            $this->modx->config['_'.$key] = $value;

        }
    }





    private function changeFields($settings,$fieldKey,$changesFields,$captionTemplate){
        $languages = $this->languages;

        $pageLang = $this->modx->getConfig('_lang');
        //делаем пол¤ mtv мультимовными
        $fields = $settings[$fieldKey];
        $langFields = [];
        foreach ($changesFields as $fieldName) {
            $temp = $fields[$fieldName];
            foreach ($languages as $lang) {
                $suffix = $this->suffixes[$lang];
                $tempLang = $temp;
                $tempLang['caption'] = str_replace(['#caption#','#lang#'],[$tempLang['caption'],$lang],$captionTemplate);
                $langFields[$fieldName][$fieldName . $suffix] = $tempLang;
            }
        }
        $newFields = [];
        foreach ($fields as $fieldName => $field) {
            if (in_array($fieldName, $changesFields) && !empty($langFields[$fieldName])) {
                foreach ($langFields[$fieldName] as $langFieldName => $langField) {
                    $newFields[$langFieldName] = $langField;
                }
            } else {
                $newFields[$fieldName] = $field;
            }
        }
        if (!empty($newFields)) {
            $settings[$fieldKey] = $newFields;
            $settings['langFields'] = $changesFields;
        }
        return $settings;
    }
    public function changeClientSettingsFields($settings, $changesFields = [],$captionTemplate = '#caption# (#lang#)'){
        $settings = $this->changeFields($settings,'settings',$changesFields,$captionTemplate);
        return $settings;
    }
    public function bLangChangeMultitvFields($settings, $changesFields = [],$captionTemplate = '#caption# (#lang#)',$templates = ['templates'])
    {


        $settings = $this->changeFields($settings,'fields',$changesFields,$captionTemplate);

        //замена полей
        if (is_array($templates)) {
            foreach ($templates as $templateKey) {
                $rowTpl = $settings[$templateKey]['rowTpl'];
                foreach ($changesFields as $fieldName) {
                    $rowTpl = str_replace('[+' . $fieldName . '+]', '[+' . $fieldName . '_' . $pageLang . '+]', $rowTpl);
                }
                $settings[$templateKey]['rowTpl'] = $rowTpl;
            }
        }

        return $settings;
    }

    public function isDefaultField($field){
        $default_field = array(
            'id', 'type', 'contentType', 'pagetitle', 'longtitle', 'description', 'alias', 'link_attributes', 'published', 'pub_date',
            'unpub_date', 'parent', 'isfolder', 'introtext', 'content', 'richtext', 'template', 'menuindex', 'searchable',
            'cacheable', 'createdon', 'createdby', 'editedon', 'editedby', 'deleted', 'deletedon', 'deletedby', 'publishedon',
            'publishedby', 'menutitle', 'donthit', 'privateweb', 'privatemgr', 'content_dispo', 'hidemenu', 'alias_visible'
        );
        return in_array($field,$default_field);
    }


}