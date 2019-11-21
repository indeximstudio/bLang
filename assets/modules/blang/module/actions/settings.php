<?php
namespace bLang;

class settings
{
    /** @var $modx \DocumentParser */
    private $modx;
    /** @var $tpl \DLTemplate */
    private $tpl;
    /** @var $bLang \bLang */
    private $bLang;

    /** @var $bLangModuleObj bLangModule */
    private $bLangModuleObj;

    private  $data;




    public function __construct($modx,$tpl,$bLang,$bLangModuleObj,$data)
    {
        $this->modx = $modx;
        $this->bLang = $bLang;
        $this->bLangModuleObj = $bLangModuleObj;

        $this->tpl = $tpl;


        $this->data = $data;

    }

    public function renderForm()
    {




        $settingValues = $this->bLang->getSettings();

        $settings = [
            'main'=>[
                'caption'=>'Main',
                'fields'=>[
                    'languages'=> [
                        'caption'=>$this->data['_settings_languages']
                    ],
                    'suffixes' => [
                        'caption'=>$this->data['_settings_suffix']
                    ],

                    'default' => [
                        'caption'=>$this->data['_settings_default']
                    ],

                    'autoFields' => [
                        'caption'=>$this->data['_settings_autoFields'],
                        'type' => 'select',
                        'elements' => '||Да==1||Нет==0',
                    ],

                    'autoUrl' => [
                        'caption'=>$this->data['_settings_autoUrl'],
                        'type' => 'select',
                        'elements' => '||Да==1||Нет==0',
                    ],
                ]
            ],
            'translate' => [
                'caption'=>'translate',
                'fields' => [
                    'fields' => [
                        'caption' => $this->data['_settings_fields']
                    ],
                    'translate' => [
                        'caption' => $this->data['_settings_translate'],
                        'type' => 'select',
                        'elements' => '||Да==1||Нет==0',
                    ],
                    'translate_provider' => [
                        'caption' => $this->data['_settings_translate_provider'],
                        'type' => 'select',
                  //      'elements' => $translatorElements,
                    ],
                    'clientSettingsPrefix' => [
                        'caption' => $this->data['_settings_clientPrefix']
                    ],
                ]
            ],
            'controllers' => [
                'caption'=>'controllers',
                'fields' => [

                    'menu_controller_fields' => [
                        'caption'=>$this->data['_settings_menu_controller_fields']
                    ],
                    'content_controller_fields' => [
                        'caption'=>$this->data['_settings_content_controller_fields']
                    ],
                ]
            ]
        ];

        $translatorFolders = scandir(MODX_BASE_PATH.'assets/modules/bLang/translator/');
        $translatorElements = '';
        foreach ($translatorFolders as $fileName) {
            if(in_array($fileName,['.','..'])) continue;
            $translatorElements .= '||'.$fileName.'=='.$fileName;
            $transLateConfig = json_decode(file_get_contents(MODX_BASE_PATH.'assets/modules/bLang/translator/'.$fileName.'/config.json'),true);



            $settings[] = [
                'caption'=>$fileName,
                'fields'=>$transLateConfig
            ];
        }
        $settings['translate']['fields']['translate_provider']['elements'] = $translatorElements;



        $output = '';

        foreach ($settings as $groupKey => $group) {

            $groupOutput = '';
            foreach ($group['fields'] as $fieldName => $field) {

                $field['name'] = $fieldName;
                $fieldType = isset($field['type'])?$field['type']:'text';
                $field['value'] = !empty($settingValues[$fieldName])?$settingValues[$fieldName]:'';

                switch ($fieldType){
                    case 'text':
                        $groupOutput .= $this->tpl->parseChunk('@FILE:settings/text',$field);
                        break;
                    case 'select':
                        $elements = $this->parseElementsString($field['elements']);
                        $field['options'] = '';
                        foreach ($elements as $caption => $value) {

                            $field['options'] .= $this->tpl->parseChunk('@FILE:settings/option',[
                                'value'=>$value,
                                'caption'=>$caption,
                                'selected'=>$field['value'] == $value?' selected':'',
                            ]);
                        }


                        $groupOutput .= $this->tpl->parseChunk('@FILE:settings/select',$field);
                        break;
                }
            }

            $output .= $this->tpl->parseChunk('@FILE:settings/group',[
                'fields'=>$groupOutput,
                'caption'=>$group['caption'],
            ]);
        }

        $this->data['fields'] = $output;

        return $this->tpl->parseChunk('@FILE:settings',$this->data);
    }


    private function parseElementsString($string){
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


}