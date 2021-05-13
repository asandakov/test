BX.ready(function(){

    BX.Vue.create ({

        el: "#personal-block",

        data: {
            msg: "",
            page: 1,
            keyCaptcha: "",
            limit: 256,
            isAdd: true,
            isPage: true,
            personals:[]
        },

        computed: {
        },

        mounted() {
            this.nextPage();
        },

        methods: {

            add() {
                this.isAdd = false;
            },

            //возврат из компонентов
            setPersonal(type, personal) {

                switch (type) {
                    case "noAdd":
                        this.isAdd = true;
                    break;
                    case "add":
                        this.isAdd = true;
                        this.page = 1;
                        this.nextPage();
                    break;
                    case "delete":
                        this.page = 1;
                        this.nextPage();
                    break;
                }
            },

            //постраничная навигация
            nextPage() {
                this.msg = "";
                BX.ajax.runComponentAction('site:personal', 'nextPage', {
                    mode: 'class',
                    data: {
                        page: this.page
                    }
                }).then((response) =>{

                    if  (this.page == 1) {
                        this.personals = response["data"]["list"];
                    }else{
                        this.personals.push(...response["data"]["list"]);
                    }

                    this.page = this.page + 1;
                    this.isPage = response["data"]["isPage"];

                }, (response) => {
                    //console.log(response);
                    this.msg = "Техничская ошибка. Попробуйте позже.";
                 }  );
            },
        },


        //компоненты
        components: {

            //элемент списка
            'item-personal': {
                template: '#item-personal',
                props: ['data','personal'],
                data: function () {
                    return {
                        isSendAjax: true,
                        msg: "",
                        limit: 256,
                        isEdit: true,
                        isDelete: false,
                        curPersonal: null,
                        keyCaptcha: ""
                    }
                },
                mounted() {
                    this.keyCaptcha = this.data.keyCaptcha;
                },
                methods: {

                    //проверка email
                    isEmail: function(value) {
                        return /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test(value);
                    },

                    editPersonal() {
                        this.curPersonal = Object.assign({}, this.personal);
                        this.isEdit = false;
                    },

                    editCancel() {
                        this.msg = "";
                        this.personal = this.curPersonal;
                        this.curPersonal = null;
                        this.isEdit = true;
                    },

                    deletePersonal() {
                        this.isDelete = true;
                    },

                    deleteCancel() {
                        this.msg = "";
                        this.isDelete = false;
                    },

                    deleteYesPersonal() {
                        this.isDelete = false;
                        this.setPersonal("delete",this.personal);
                    },

                    savePersonal() {

                        if (this.personal.email === "" ||
                            this.personal.name === "" ||
                            this.personal.nameLast === "" ||
                            this.personal.phone === ""
                        ){
                            this.msg = "Все поля должны быть заполнены";
                            return;
                        }
                        if (!this.isEmail(this.personal.email)){
                            this.msg = "Не верный формат Email";
                            return;
                        }

                        this.isEdit = true;
                        this.setPersonal("edit",this.personal);
                        this.curPersonal = null;
                    },


                    // действия над персоналом
                    setPersonal(type, personal) {

                        if (!this.isSendAjax)
                            return;

                        this.isSendAjax = false;
                        this.msg = "Подождите...";
                        grecaptcha.execute(this.keyCaptcha, {action: 'setPersonalAction'}).then((token) => {

                            BX.ajax.runComponentAction('site:personal', 'setPersonal', {
                                mode: 'class',
                                data: {
                                    token: token,
                                    type: type,
                                    personal: personal
                                }
                            }).then((response) => {
                                this.isSendAjax = true;
                                if (response["data"]["flag"]) {
                                    this.msg = "Операция выполнена успешно";
                                    setTimeout( () => {
                                        this.msg = "";
                                        this.$emit('action-personal',type, personal);
                                    }, 700);


                                }else{
                                    this.msg = response["data"]["error"];
                                    if  (!this.msg){
                                        this.msg = "Техничская ошибка. Попробуйте позже.";
                                    }
                                }

                            }, (response) => {
                                this.isSendAjax = true;
                                this.msg = "Техничская ошибка. Попробуйте позже.";
                            } );

                        });
                    }



                }
            },

            // форма добавить
            'add-personal': {
                template: '#add-personal',
                props: ['data'],
                data: function () {
                    return {
                        isSendAjax: true,
                        keyCaptcha: "",
                        msg: "",
                        limit: 256,
                        personal: {
                            name: "",
                            nameLast: "",
                            phone: "",
                            email: ""
                        }
                    }
                },

                mounted() {
                    this.keyCaptcha = this.data.keyCaptcha;
                },

                methods: {

                    //проверка email
                    isEmail: function(value) {
                        return /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test(value);
                    },

                    //отмена
                    addCancel() {
                        this.msg = "";
                        this.$emit('action-personal','noAdd', this.personal);
                    },


                    //сохранить
                    savePersonal() {

                        if (this.personal.email === "" ||
                            this.personal.name === "" ||
                            this.personal.nameLast === "" ||
                            this.personal.phone === ""
                        ){
                            this.msg = "Все поля должны быть заполнены";
                            return;
                        }
                        if (!this.isEmail(this.personal.email)){
                            this.msg = "Не верный формат Email";
                            return;
                        }

                        this.setPersonal("add",this.personal);
                    },


                    // действия над персоналом
                    setPersonal(type,personal) {

                        if (!this.isSendAjax)
                            return;

                        this.isSendAjax = false;
                        this.msg = "Подождите...";
                        grecaptcha.execute(this.keyCaptcha, {action: 'setPersonalAction'}).then((token) => {

                            BX.ajax.runComponentAction('site:personal', 'setPersonal', {
                                mode: 'class',
                                data: {
                                    token: token,
                                    type: type,
                                    personal: personal
                                }
                            }).then((response) => {
                                this.isSendAjax = true;
                                if (response["data"]["flag"]) {
                                    this.msg = "Операция выполнена успешно";
                                    setTimeout( () => {
                                        this.msg = "";
                                        this.$emit('action-personal',type, personal);
                                    }, 700);


                                }else{
                                    this.msg = response["data"]["error"];
                                    if  (!this.msg){
                                        this.msg = "Техничская ошибка. Попробуйте позже.";
                                    }
                                }

                            }, (response) => {
                                this.isSendAjax = true;
                                this.msg = "Техничская ошибка. Попробуйте позже.";
                            } );

                        });
                    }


                }
            },
        },

    });
});
