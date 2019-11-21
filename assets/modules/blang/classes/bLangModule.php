<?php
namespace bLang;


class bLangModule
{
    /** @var $modx \DocumentParser */
    private $modx;
    private $bLang;
    public $_lang;

    public $modulePath;
    public $moduleurl;


    private function loadModuleLang()
    {
        $manager_language = $this->modx->getConfig('manager_language');

        $file = MODX_BASE_PATH . 'assets/modules/bLang/module/lang/' . $manager_language . '.inc.php';

        if (!file_exists($file)) {
            $file = MODX_BASE_PATH . 'assets/modules/bLang/module/lang/russian-UTF8.inc.php';
        }
        require $file;
        $this->_lang =  $_lang;


    }

    /**
     * bLangModule constructor.
     * @param $modx
     * @param $bLang bLang
     * @param $modulePath
     * @param $moduleurl
     */
    public function __construct($modx, $bLang, $modulePath, $moduleurl)
    {
        $this->modx = $modx;
        $this->bLang = $bLang;
        $this->modulePath = $modulePath;
        $this->moduleurl = $moduleurl;

        $this->loadModuleLang();;

    }
    public function getModuleLang(){
        return $this->_lang;
    }

    public function createColumn($languages)
    {
        if(empty($languages)) return false;
        $table = $this->modx->getFullTableName('blang');
        $data = $this->modx->db->makeArray($this->modx->db->query("DESCRIBE $table"));

        $columns = array_column($data,"Field");



        $languages = explode(',',$languages);
        foreach ($languages as $lang) {
            if(!in_array($lang,$columns)){
                $eLang = $this->modx->db->escape($lang);
                $this->modx->db->query("ALTER TABLE $table ADD `$eLang` VARCHAR(1000) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' ");
            }
        }
        return true;
    }



    public function createDefaultParams()
    {
        $BT  = $this->modx->getFullTableName('blang_tmplvars');
        $params = json_decode(file_get_contents($this->modulePath . 'actions/default_fields.json'), true);


        if (is_array($params)) {
            foreach ($params as $fields) {


                foreach ($fields as $key => $val) {
                    $fields[$key] = $this->modx->db->escape($val);
                }
                $name = $this->modx->db->escape($fields['name']);
                $category = $fields['category'];

                $categoryId = $this->checkCategory($category);
                if (!$categoryId) {
                    $categoryId = $this->newCategory($category);
                }
                $fields['category'] = $categoryId;

                $id = $this->modx->db->getValue("select id from " . $BT . " where name = '" . $name . "'");
                if (empty($id)) {
                    $id = $this->modx->db->insert($fields, $BT);


                    if(!empty($_GET['template']) &&$_GET['template'] == 'all'){
                        $this->modx->db->delete($this->modx->getFullTableName('blang_tmplvar_templates'),'tmplvarid = '.$id);
                        $templates = $this->modx->db->makeArray($this->modx->db->select('*',$this->modx->getFullTableName('site_templates')));
                        foreach ($templates as $template) {

                            $this->modx->db->insert([
                                'tmplvarid'=>$id,
                                'templateid'=>$template['id']
                            ],$this->modx->getFullTableName('blang_tmplvar_templates'));
                        }
                    }

                }

            }
        }
    }



    function prepareFields($fields, $lang)
    {
        global $modx;
        $prepareFields = [];
        foreach ($fields as $key => $val) {
            $prepareFields[$key] = str_replace([
                '[lang]',
                '[suffix]',
            ], [
                $lang,
                $this->bLang->suffixes[$lang]
            ], $modx->db->escape($val));
        }
        unset($prepareFields['id']);
        return $prepareFields;
    }




    public  function checkCategory($newCat = '')
    {
        $modx = evolutionCMS();
        $newCat = $modx->db->escape($newCat);
        $cats = $modx->db->select('id', $modx->getFullTableName('categories'), "category='{$newCat}'");
        if ($cat = $modx->db->getValue($cats)) {
            return (int)$cat;
        }

        return 0;
    }
    public function newCategory($newCat)
    {
        $modx = evolutionCMS();
        $useTable = $modx->getFullTableName('categories');
        $categoryId = $modx->db->insert(
            array(
                'category' => $modx->db->escape($newCat),
            ), $useTable);
        if (!$categoryId) {
            $categoryId = 0;
        }

        return $categoryId;
    }


}