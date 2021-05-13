<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>
<?
$asset = \Bitrix\Main\Page\Asset::getInstance();
$asset->addJs('https://www.google.com/recaptcha/api.js?render='.$arResult["CAPTCHA_KEY"]);
\Bitrix\Main\UI\Extension::load("ui.vue");
?>


<div class="personal-block" id="personal-block">

    <div class="anons">Необходимо разработать интерфейс добавления/редактирование/просмотра сотрудников компании</div>

    <div class="msg">{{msg}}</div>
    <div class="btn-block add" @click="add" v-if="isAdd">Добавить</div>
    <add-personal v-else :data='<?=\Bitrix\Main\Web\Json::encode(["keyCaptcha"=>$arResult["CAPTCHA_KEY"]])?>' v-on:action-personal="setPersonal"></add-personal>
    <div class="clear"></div>

    <div class="items">
       <item-personal :data='<?=\Bitrix\Main\Web\Json::encode(["keyCaptcha"=>$arResult["CAPTCHA_KEY"]])?>' v-for="personal in personals" v-on:action-personal="setPersonal"  :personal="personal"  :key="personal.id"></item-personal>
    </div>
    <div class="clear"></div>
    <div class="btn-block more" @click="nextPage" v-if="isPage">показать еще</div>
    <div class="warring" >Сервис работает в тестовом режиме. Все данные вымышленные и любое совпадение случайно.</div>
</div>

<template id="add-personal">
    <div class="item item-add">
        <div class="add-param">
            <div class="tit">Добавить сотрудника</div>
            <div class="msg">{{msg}}</div>
            <div class="row">
                <div class="col-md-6">
                    <div class="it">
                        <div class="nm">Имя</div>
                        <div class="val"><input type="text" :maxlength="limit"  v-model.trim="personal.name"/></div>
                    </div>

                    <div class="it">
                        <div class="nm">Фамилия</div>
                        <div class="val"><input type="text" :maxlength="limit"  v-model.trim="personal.nameLast"/></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="it">
                        <div class="nm">Телефон</div>
                        <div class="val"><input type="number"  v-model.number="personal.phone"/></div>
                    </div>

                    <div class="it">
                        <div class="nm">Email</div>
                        <div class="val"><input type="text"  v-model.trim="personal.email"/></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="btn-block" @click="savePersonal">Сохранить</div>
                </div>
                <div class="col-md-6">
                    <div class="btn-block" @click="addCancel">Отмена</div>
                </div>
            </div>
        </div>
    </div>
</template>

<template id="item-personal">
    <div class="item">
        <div class="msg">{{msg}}</div>
        <div class="show-param" v-if="isEdit">
            <div class="row">
                <div class="col-md-6">
                    <div class="it">
                        <div class="nm">Имя</div>
                        <div class="val">{{personal.name}}</div>
                    </div>

                    <div class="it">
                        <div class="nm">Фамилия</div>
                        <div class="val">{{personal.nameLast}}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="it">
                        <div class="nm">Телефон</div>
                        <div class="val">{{personal.phone}}</div>
                    </div>

                    <div class="it">
                        <div class="nm">Email</div>
                        <div class="val">{{personal.email}}</div>
                    </div>
                </div>
            </div>

            <div class="row" v-if="!isDelete">
                <div class="col-md-6">
                    <div class="btn-block" @click="editPersonal" >Изменить</div>
                </div>
                <div class="col-md-6" @click="deletePersonal">
                    <div class="btn-block">Удалить</div>
                </div>
            </div>

            <div class="row" v-else>
                <div class="col-md-6">
                    <div class="btn-block" @click="deleteYesPersonal" >Да, удалить</div>
                </div>
                <div class="col-md-6">
                    <div class="btn-block" @click="deleteCancel">Отмена</div>
                </div>
            </div>

        </div>

        <div class="edit-param" v-else>
            <div class="row">
                <div class="col-md-6">
                    <div class="it">
                        <div class="nm">Имя</div>
                        <div class="val"><input type="text" :maxlength="limit"  v-model.trim="personal.name"/></div>
                    </div>

                    <div class="it">
                        <div class="nm">Фамилия</div>
                        <div class="val"><input type="text" :maxlength="limit"  v-model.trim="personal.nameLast"/></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="it">
                        <div class="nm">Телефон</div>
                        <div class="val"><input type="number"  v-model.number="personal.phone"/></div>
                    </div>

                    <div class="it">
                        <div class="nm">Email</div>
                        <div class="val"><input type="text"  v-model.trim="personal.email"/></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="btn-block" @click="savePersonal">Сохранить</div>
                </div>
                <div class="col-md-6">
                    <div class="btn-block" @click="editCancel">Отмена</div>
                </div>
            </div>
        </div>
    </div>
</template>