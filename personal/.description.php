<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
use \Bitrix\Main\Localization\Loc as Loc;
Loc::loadMessages(__FILE__);
$arComponentDescription = array(
    "NAME" => Loc::getMessage('NAME_PERSONAL_SITE'),
    "DESCRIPTION" => Loc::getMessage('DESCRIPTION_PERSONAL_SITE'),
    "ICON" => '/images/icon.gif',
    "SORT" => 20,
    "PATH" => array(
        "ID" => 'test',
        "NAME" => Loc::getMessage('GROUP_PERSONAL_SITE'),
        "SORT" => 10
    ),
);
?>
