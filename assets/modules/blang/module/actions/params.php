<?php
namespace bLang;

class params
{
    /** @var $modx \DocumentParser */
    private $modx;
    /** @var $tpl \DLTemplate */
    private $tpl;
    /** @var $bLangModuleObj bLangModule */
    private $bLangModuleObj;
    /** @var $bLang \bLang\bLang */
    private $bLang;
    private $BT;
    private $BTT;

    private  $data;
    
    public function __construct($modx,$bLangModuleObj,$bLang,$tpl)
    {
        $this->modx = $modx;




        $this->bLangModuleObj = $bLangModuleObj;
        $this->bLang = $bLang;
        $this->tpl = $tpl;

        //название таблиц
        $this->BT = $modx->getFullTableName('blang_tmplvars');
        $this->BTT = $modx->getFullTableName('blang_tmplvar_templates');

        $this->data = [
            'moduleurl' => $this->bLangModuleObj->moduleurl,
            'manager_theme' => $modx->config['manager_theme'],
            'action' => isset($_GET['action'])?$_GET['action']:'home',
            'stay.'.$_SESSION['stay'] => 'selected',
            'selected' => [isset($_GET['action'])?$_GET['action']:'home' => 'selected']
        ];
        foreach ($this->bLangModuleObj->_lang as $key => $value) {
            $this->data['_'.$key] = $value;
        }




    }
    
    public function getAndProcessParamForm(){


        $resourceData = [];
        $resourceTemplates  = [];
        if(!empty($_GET['param'])){
            $resourceData = $this->modx->db->getRow($this->modx->db->select('*',$this->BT,'id = '.intval($_GET['param'])));
            $resourceTemplates = $this->modx->db->getColumn('templateid',$this->modx->db->select('*',$this->BTT,'tmplvarid = '.intval($_GET['param'])));
        }

        $errors = [];
        if(!empty($_POST)){

            $stay = $_POST['stay'];
            $_SESSION['stay'] = $stay;

            $formData = $_POST;
            $templates = !empty($formData['template']) && is_array($formData['template'])?$formData['template']:[];
            $nextAction = $formData['action'];
            unset($formData['action']);
            unset($formData['stay']);
            unset($formData['template']);

            $formType =  'edit';
            if(empty($formData['id'])){
                unset($formData['id']);
                $formType = 'new';
            }
            $formData['category'] = $formData['categoryid'];
            unset($formData['categoryid']);

            if(empty($formData['name'])){
                $formData['name'] = 'Untitled variable';
            }
            if(empty($formData['caption'])){
                $formData['caption'] = 'Untitled variable';
            }
            $formData = $this->modx->db->escape($formData);

            //проверяем уникальность
            $uniqueWhere = '`name` = "'.$formData['name'].'"';
            if(!empty($formData['id'])){
                $uniqueWhere .= ' AND id != '.$formData['id'];
            }
            $checkUnique = $this->modx->db->getValue($this->modx->db->select('id',$this->BT,$uniqueWhere));
            if(!empty($checkUnique)){
                $errors[] = str_replace('[+name+]',$formData['name'],$this->data['_params_not_unique']);
            }

            if(empty($errors)) {
                //проверяем новую категорию
                if(!empty($formData['newcategory'])){
                    $checkCategory = $this->bLangModuleObj->checkCategory($formData['newcategory']);

                    if(empty($checkCategory)){
                        $formData['category'] = $this->bLangModuleObj->newCategory($formData['newcategory']);
                    }
                    else{
                        $formData['category'] = $checkCategory;
                    }
                }
                unset($formData['newcategory']);

                $formData['rank'] = intval($formData['rank']);
                $formData['category'] = intval($formData['category']);
                if(!empty($formData['id'])){
                    $this->modx->db->update($formData,$this->BT,'id = '.$formData['id']);
                    $paramId = $formData['id'];
                }
                else{
                    $paramId = $this->modx->db->insert($formData,$this->BT);
                }


                //удаляем шаблоны
                $this->modx->db->delete($this->BTT,'tmplvarid = '.$paramId);
                foreach ($templates as $templateId) {
                    $this->modx->db->insert([
                        'tmplvarid' => $paramId,
                        'templateid' => $templateId,
                        'rank'=>0
                    ], $this->BTT);
                }

                $resourceData = $formData;
                $resourceTemplates = $templates;

                if($stay == 1){
                    $this->modx->sendRedirect($this->bLangModuleObj->moduleurl.'action=paramForm');
                }
                if($stay == ''){
                    $this->modx->sendRedirect($this->bLangModuleObj->moduleurl.'action=params');
                }
                if($formType == 'new'){
                    $this->modx->sendRedirect($this->bLangModuleObj->moduleurl.'action=paramForm&param='.$paramId);
                }
            }
            else{
                $resourceData = $_POST;
                $resourceTemplates = $_POST['template'];
            }
        }

        //стандартные параметры
        $tvTypes = [
            'text'=>'Text',
            'rawtext'=>'Raw Text (deprecated)',
            'textarea'=>'Textarea',
            'rawtextarea'=>'Raw Textarea (deprecated)',
            'textareamini'=>'Textarea (Mini)',
            'richtext'=>'RichText',
            'dropdown'=>'DropDown List Menu',
            'listbox'=>'Listbox (Single-Select)',
            'listbox-multiple'=>'Listbox (Multi-Select)',
            'option'=>'Radio Options',
            'checkbox'=>'Check Box',
            'image'=>'Image',
            'file'=>'File',
            'url'=>'URL',
            'email'=>'Email',
            'number'=>'Number',
            'date'=>'Date',
        ];
        foreach ($tvTypes as $type => $typeCaption) {
            $this->data['standardTVType'] .= $this->tpl->parseChunk('@CODE:<option value="[+type+]" [+selected+]>[+caption+]</option>',[
                'type'=>$type,
                'caption'=>$typeCaption,
                'selected'=>!empty($resourceData['type']) && $resourceData['type'] == $type?'selected':'',
            ]);
        }
        //custom tv
        $custom_tvs = scandir(MODX_BASE_PATH . 'assets/tvs');
        $customTVS = ['custom_tv'=>'Custom Input'];
        foreach($custom_tvs as $ctv) {
            if(strpos($ctv, '.') !== 0 && $ctv != 'index.html') {
                $type = 'custom_tv:' . $ctv;
                $customTVS['custom_tv:' . $ctv] = $ctv;
            }
        }
        foreach ($customTVS as $type => $typeCaption) {
            $this->data['customTVType'] .= $this->tpl->parseChunk('@CODE:<option value="[+type+]" [+selected+]>[+caption+]</option>',[
                'type'=>$type,
                'caption'=>$typeCaption,
                'selected'=>!empty($resourceData['type']) && $resourceData['type'] == $type?'selected':'',
            ]);
        }

        $modxCategories = $this->modx->db->makeArray($this->modx->db->select('*',$this->modx->getFullTableName('categories')),'id');

        foreach ($modxCategories as $category) {
            $this->data['categories'] .= $this->tpl->parseChunk('@CODE:<option value="[+id+]" [+selected+]>[+caption+]</option>',[
                'id'=>$category['id'],
                'caption'=>$category['category'],
                'selected'=>!empty($resourceData['category']) && $resourceData['category'] == $category['id']?'selected':'',
            ]);
        }
        //генерим список шаблонов
        $templates = $this->modx->db->makeArray($this->modx->db->select('*',$this->modx->getFullTableName('site_templates')));

        $templateCategory = [];
        foreach ($templates as $template) {
            $templateCategory[$template['category']][] = $template;
        }
        $this->data['templates'] = '';

        foreach ($templateCategory as $categoryId => $templates) {
            $wrap = '';
            foreach ($templates as $template) {
                $wrap .= $this->tpl->parseChunk('@CODE:<li><label><input name="template[]" value="[+id+]" type="checkbox" [+checked+]> [+name+]&nbsp;<small>([+id+])</small> [+description+] </label></li>', [
                    'id' => $template['id'],
                    'name' => $template['templatename'],
                    'description' => !empty($template['description']) ? ' - ' . $template['description'] : '',
                    'checked' => !empty($resourceTemplates) && in_array($template['id'], $resourceTemplates) ? 'checked' : '',
                ]);
            }

            $this->data['templates'] .= $this->tpl->parseChunk('@CODE:<li><strong>[+categoryName+]</strong><ul>[+wrap+]</ul></li>',[
                'wrap'=>$wrap,
                'categoryName'=>!empty($modxCategories[$categoryId])?$modxCategories[$categoryId]['category']:$this->data['_empty_category']
            ]);
        }

        if(!empty($errors)){
            foreach ($errors as $error) {
                $this->data['errors'] .= $this->tpl->parseChunk("@CODE:alert('[+error+]');\n",['error'=>$error]);
            }
        }
        return $this->tpl->parseChunk('@FILE:params/paramForm', array_merge($this->data,$resourceData),true);
    }

    public function getParamsList()
    {
        $modxCcategories = $this->modx->db->makeArray($this->modx->db->select('*',$this->modx->getFullTableName('categories')),'id');
        //получаем список параметорв
        $bLangTmplvars = $this->modx->db->makeArray($this->modx->db->query(
            "select * from $this->BT order by `category` asc, `id` asc"
        ));
        $categories = [];
        foreach ($bLangTmplvars as $el) {
            $categories[$el['category']][] = $el;
        }
        $this->data['paramGroups'] = '';
        foreach ($categories as $categoryId => $tmplvars) {
            $wrap = '';
            foreach ($tmplvars as $tmplvar) {
                $wrap .= $this->tpl->parseChunk('@FILE:params/param',array_merge($tmplvar,['moduleurl'=>$this->bLangModuleObj->moduleurl]));
            }
            $this->data['paramGroups']  .= $this->tpl->parseChunk('@FILE:params/paramGroup',[
                'id'=>$categoryId,
                'wrap'=>$wrap,
                'name'=>!empty($modxCcategories[$categoryId])?$modxCcategories[$categoryId]['category']:$this->data['_empty_category']
            ]);
        }
        return $this->tpl->parseChunk('@FILE:params/params', $this->data,true);

    }

    public function deleteParam()
    {
        $paramId = intval($_GET['param']);
        $this->modx->db->delete($this->BT,'id = '.$paramId);
        $this->modx->db->delete($this->BTT,'tmplvarid = '.$paramId);
        $this->modx->sendRedirect($this->bLangModuleObj->moduleurl.'action=params');
    }

    public function updateTV()
    {
        $ST = $this->modx->getFullTableName('site_tmplvars');
        $STT = $this->modx->getFullTableName('site_tmplvar_templates');

        $languages = $this->bLang->languages;
        $suffixes = $this->bLang->suffixes;

        $bLangParams = $this->modx->db->makeArray($this->modx->db->select('*',$this->BT));

        foreach ($bLangParams as $param) {

            foreach ($languages as $lang) {
                $tvName = $param['name'].$suffixes[$lang];
                if($this->bLang->isDefaultField($tvName)){
                    continue;
                }

              //  echo $tvName.'<br>';

                $tvId = $this->modx->db->getValue($this->modx->db->select('id',$ST,'name = "'.$this->modx->db->escape($tvName).'"'));
                $tvData = array_merge($param,['name'=>$tvName]);
                $tvData = $this->bLangModuleObj->prepareFields($tvData,$lang);


                $data = [];
                $originalFields = ['type', 'name', 'caption', 'description', 'editor_type', 'category', 'locked', 'elements', 'rank', 'display', 'display_params', 'default_text',];

                foreach ($originalFields as $fieldName) {
                    $data[$fieldName] = isset($tvData[$fieldName])?$tvData[$fieldName]:'';
                }


                if(empty($tvId)){
                    $tvId =  $this->modx->db->insert($data,$ST);
                }
                else{
                    $this->modx->db->update($data,$ST,'id = '.intval($tvId));
                }


                $this->modx->db->delete($STT,'tmplvarid = '.intval($tvId));

                $bLangTemplates = $this->modx->db->makeArray($this->modx->db->select('*',$this->BTT,'tmplvarid = '.intval($param['id'])));
                foreach ($bLangTemplates as $template) {
                    $this->modx->db->insert([
                        'tmplvarid'=>intval($tvId),
                        'templateid'=>intval($template['templateid'])
                    ],$STT);

                }
            }
        }
   //     $this->modx->sendRedirect($this->bLangModuleObj->moduleurl.'action=params');
    }
}