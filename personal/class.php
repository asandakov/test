<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
use \Bitrix\Main;
use Bitrix\Main\Type;
use Bitrix\Main\Web\Json;
use \Bitrix\Main\Localization\Loc as Loc;
use Bitrix\Main\Entity;
use Bitrix\Main\Application;
use Bitrix\Main\Web\Cookie;

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine;

Main\Loader::includeModule('Highloadblock');
class PersonalSite extends CBitrixComponent  implements Controllerable
{

    const PERSONAL_HL_ID = 23;
    const LIMIT_PAGE = 6;

    /**
     * проверяет подключение необходиимых модулей
     * @throws LoaderException
     */
    public function checkModules()
    {
        if (!Main\Loader::includeModule('Highloadblock'))
            throw new Main\LoaderException(Loc::getMessage('HL_PERSONAL_SITE_HL'));
    }


    /**
     * подготавливает входные параметры
     * @param array $arParams
     * @return array
     */
    public function onPrepareComponentParams($arParams)
    {
        return $arParams;
    }


    private function getClassTable()
    {
        $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getById(self::PERSONAL_HL_ID)->fetch();
        $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();

        return $entity_data_class;
    }


    //////////////// НАЧАЛО: AJAX - МЕТОДЫ //////////////////////////////////////////////

    // Обязательный метод
    public function configureActions()
    {
        return [
             'setPersonal' => [
                'prefilters' => [
                    new \Bitrix\Main\Engine\ActionFilter\HttpMethod(
                        array(\Bitrix\Main\Engine\ActionFilter\HttpMethod::METHOD_POST)
                    ),
                    new \Bitrix\Main\Engine\ActionFilter\Csrf()
                ]
              ],
               'nextPage' => [
                     'prefilters' => [
                         new \Bitrix\Main\Engine\ActionFilter\HttpMethod(
                             array(\Bitrix\Main\Engine\ActionFilter\HttpMethod::METHOD_POST)
                         ),
                        new \Bitrix\Main\Engine\ActionFilter\Csrf()
               ]
            ]

        ];
    }

    public function nextPageAction($page)
    {
        $arResult = array("list" => [], "isPage" => false, "count" => 0, "error" => "");

        $page = (int) $page;
        if ($page <= 0){
            $page = 1;
        }
        $arData = $this->getData(array(),$page, self::LIMIT_PAGE)["DATA"];

        $arPersonal = array();
        foreach ($arData["LIST"] as $arItem) {
            $arPersonal[] = array(
                "id" => $arItem["ID"],
                "name" => $arItem["UF_NAME"],
                "nameLast" => $arItem["UF_LAST_NAME"],
                "phone" => $arItem["UF_PHONE"],
                "email" => $arItem["UF_EMAIL"]
            );
        }

        $arResult["list"] = $arPersonal;
        $arResult["count"] = $arData["COUNT"];
        if ($arData["FLAG_NAV"]) {
            $arResult["isPage"] = true;
        }

        return $arResult;
    }


    public function setPersonalAction($token, $type, $personal = array())
    {
        $arResult = array("flag" => false, "error" => "");

        //token
        $token = trim($token);
        if (empty($token)) {
            $arResult["error"] = "Техническая ошибка, попробуйте позже";
            return $arResult;
        }

        //проверка капчи
        $bot = checkCaptcha_v3($token);
        if($bot){
            $arResult["error"] = "Техническая ошибка или вы бот, попробуйте позже";
            return $arResult;
        }

        switch ($type){
            case "add":
                $arResult = $this->addPersonal($personal);
            break;

            case "edit":
                $arResult = $this->updPersonal($personal);
            break;

            case "delete":
                $arResult = $this->deletePersonal($personal);
            break;
            default:
                $arResult["error"] = "Техническая ошибка, попробуйте позже. Неизвестный тип операции.";
            break;
        }


        return $arResult;
    }

    //////////////////// КОНЕЦ: AJAX - МЕТОДЫ /////////////////////////////////////


    //Добавить товарища
    private function addPersonal($arPersonal = array())
    {
        global $CACHE_MANAGER;

        $arResult = array("flag" => false, "error" => "");

        //имя
        $name = trim((strip_tags($arPersonal["name"])));
        if (empty($name)) {
            $arResult["error"] = "Введите имя";
            return $arResult;
        }
        $name = TruncateText($name, 256);

        //фамилия
        $nameLast = trim((strip_tags($arPersonal["nameLast"])));
        if (empty($name)) {
            $arResult["error"] = "Введите фамилию";
            return $arResult;
        }
        $nameLast = TruncateText($nameLast, 256);


        //email
        $email = trim(strip_tags($arPersonal["email"]));
        if (!check_email($email))
        {
            $arResult["error"] = "Неверный формат email";
            return $arResult;
        }

        //phone
        $phone = trim(strip_tags($arPersonal["phone"]));
        $phone = $this->getPhoneFormat($phone)["PHONE"];
        if (empty($phone))
        {
            $arResult["error"] = "Неверный формат телефона";
            return $arResult;
        }


        $date = date("d.m.Y H:i:s");

        $arDataAdd = array(
            "UF_DATE_CREATE" => $date,
            "UF_DATE_UPDATE" => $date,
            "UF_NAME" => $name,
            "UF_LAST_NAME" => $nameLast,
            "UF_EMAIL" => $email,
            "UF_PHONE" => $phone,
            "UF_USER_IP" => \Bitrix\Main\Service\GeoIp\Manager::getRealIp()
        );

        $entity_data_class = $this->getClassTable();
        $result =  $entity_data_class::add($arDataAdd);
        if ($result->isSuccess()) {
            $arResult["flag"] =  true;
            $CACHE_MANAGER->ClearByTag("HL_SITE_PERSONAL");
        } else {
            $arResult["error"] =  "Техническая ошибка"; //. implode(', ', $result->getErrors())
       }

        return $arResult;
    }


    //Изменить товарища
    public function updPersonal($arPersonal = array())
    {
        global $CACHE_MANAGER;

        $arResult = array("flag" => false, "error" => "");

        //имя
        $name = trim((strip_tags($arPersonal["name"])));
        if (empty($name)) {
            $arResult["error"] = "Введите имя";
            return $arResult;
        }
        $name = TruncateText($name, 256);

        //фамилия
        $nameLast = trim((strip_tags($arPersonal["nameLast"])));
        if (empty($name)) {
            $arResult["error"] = "Введите фамилию";
            return $arResult;
        }
        $nameLast = TruncateText($nameLast, 256);


        //email
        $email = trim(strip_tags($arPersonal["email"]));
        if (!check_email($email))
        {
            $arResult["error"] = "Неверный формат email";
            return $arResult;
        }

        //phone
        $phone = trim(strip_tags($arPersonal["phone"]));
        $phone = $this->getPhoneFormat($phone)["PHONE"];
        if (empty($phone))
        {
            $arResult["error"] = "Неверный формат телефона";
            return $arResult;
        }

        //наличие товарища
        $idPersonal = (int) $arPersonal["id"];
        if (!$this->isPersonal($idPersonal)){
            $arResult["error"] = "Техническая ошибка, нельзя изменить сотрудника";
            return $arResult;
        }


        $arDataUpd = array(
            "UF_DATE_UPDATE" => date("d.m.Y H:i:s"),
            "UF_NAME" => $name,
            "UF_LAST_NAME" => $nameLast,
            "UF_EMAIL" => $email,
            "UF_PHONE" => $phone,
            "UF_USER_IP" => \Bitrix\Main\Service\GeoIp\Manager::getRealIp()
        );

        $entity_data_class = $this->getClassTable();
        $result =  $entity_data_class::update($idPersonal,$arDataUpd);
        if ($result->isSuccess()) {
            $arResult["flag"] =  true;
            $CACHE_MANAGER->ClearByTag("HL_SITE_PERSONAL");
        } else {
            $arResult["error"] =  "Техническая ошибка";
        }

        return $arResult;
    }


    //Удалить товарища
    public function deletePersonal($arPersonal = array())
    {
        global $CACHE_MANAGER;

        $arResult = array("flag" => false, "error" => "");

        //наличие товарища
        $idPersonal = (int) $arPersonal["id"];
        if (!$this->isPersonal($idPersonal)){
            $arResult["error"] = "Техническая ошибка, нельзя удалить сотрудника";
            return $arResult;
        }

        $entity_data_class = $this->getClassTable();
        $result =  $entity_data_class::delete($idPersonal);
        if ($result->isSuccess()) {
            $arResult["flag"] =  true;
            $CACHE_MANAGER->ClearByTag("HL_SITE_PERSONAL");
        } else {
            $arResult["error"] =  "Техническая ошибка";
        }

        return $arResult;
    }


    //валидатор телефона
    private static function getPhoneFormat($phoneReal = "") {

        $arResult = array("ERROR" => array(), "PHONE" => "");

        if (!empty($phoneReal)){
            $phone = preg_replace('/[^0-9]/', '', $phoneReal);
            if(strlen($phone) == 11){
                $phone = substr($phone, 1, 10);
            }
            if(strlen($phone) == 10){
                $arResult["PHONE"] = $phone;
            }else{
                $arResult["ERROR"]["TEXT"] = "Не верный формат телефона";
            }
        }else{
            $arResult["ERROR"]["TEXT"] = "Не введен телефон";
        }

        return $arResult;
    }


    //НАЛИЧИЕ ТОВАРИЩА
    private function isPersonal($idPersonal = 0)
    {
        $idPersonal = (int) $idPersonal;
        if ($idPersonal <= 0)
            return false;

        $entity_data_class = $this->getClassTable();
        $result = $entity_data_class::getList(array(
            "filter" => array("=ID" => $idPersonal),
            'select' => array('ID'),
            'limit' => 1
        ));
        if ($arFields = $result->fetch()) {
            return true;
        }
    }

    //СПИСОК ТОВАРИЩЕЙ
    private function getPersonal($arFilter = array(),$page = 1, $limit = 10)
    {
        $arResult = array("LIST" => array(), "COUNT" => 0,  "ALL_PAGE" => 0,  "FLAG_NAV" => true);

        $arRealFilter = array();
        if (count($arFilter) > 0) $arRealFilter = array_merge($arRealFilter,$arFilter);

        $entity_data_class = $this->getClassTable();
        $result = $entity_data_class::getList(array(
            'count_total' => true,
            "filter" => $arRealFilter,
            'select' => array('*'),
            'limit' => $limit,
            'offset' => $limit*($page-1),
            'order' => array("ID" => "desc")
        ));
        while ($arFields = $result->fetch()) {
            $arResult["LIST"][$arFields["ID"]] = $arFields;
        }

        if (!empty($result))
            $arResult["COUNT"] = $result->getCount();


        $arResult["ALL_PAGE"] = floor($arResult["COUNT"]/$limit);

        if ($arResult["ALL_PAGE"] == 0)
            $arResult["FLAG_NAV"] = false;

        if($arResult["COUNT"] % $limit > 0)
        {
            $arResult["ALL_PAGE"]++;
        }

        if ($arResult["ALL_PAGE"] == $page){
            $arResult["FLAG_NAV"] = false;
        }

        return $arResult;
    }

    //ДАННЫЕ
    private function getData($arFilter = array(), $page = 1, $limit = 10)
    {
        $arResult = array("DATA" => array(),"CACHE" => false);


        $arCacheParams = array("FILTER" => $arFilter, "PAGE" => $page, "LIMIT" => $limit);

        $cacheTime = "3600000";
        $cacheId = md5(serialize($arCacheParams));
        $cacheDir = '/' . SITE_ID . '/test/personal';
        $cache = Bitrix\Main\Data\Cache::createInstance();
        if ($cache->initCache($cacheTime, $cacheId, $cacheDir)) {

            $arResult = $cache->getVars();
            $arResult["CACHE"] = true;
        } elseif ($cache->startDataCache()) {

            $arResult["DATA"] = $this->getPersonal(array(), $page, $limit);

            if (empty($arResult["DATA"]["LIST"])) {
                $cache->abortDataCache();
                return;
            }

            global $CACHE_MANAGER;
            $CACHE_MANAGER->StartTagCache($cacheDir);
            $CACHE_MANAGER->RegisterTag("HL_SITE_PERSONAL");
            $CACHE_MANAGER->EndTagCache();
            $cache->endDataCache($arResult);

        }

        return $arResult;

    }


    protected function showPage()
    {
        $this->arResult["CAPTCHA_KEY"] = RE_SITE_KEY_s4;
    }

    /**
     * выполняет логику работы компонента
     */
    public function executeComponent()
    {
        global $APPLICATION;

       // global$CACHE_MANAGER;
       // $CACHE_MANAGER->ClearByTag("HL_SITE_PERSONAL");

        $this->checkModules();
        $this->showPage();
        $this->IncludeComponentTemplate();

    }

}
?>