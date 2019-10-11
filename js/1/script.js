window.addEventListener('load', function($) {
    jQuery( "#basic-form" ).submit(function( event ) {
        event.preventDefault();
        jQuery('.formOplata').hide();
        invoice = jQuery('.formOplata .formOrderID').val();
        userEmail = jQuery('.formOplata .formEmail').val();
        seccode = jQuery('.formOplata .seccode').val();
        token = jQuery('.formOplata #g-recaptcha-response').val();
        loadData(invoice, userEmail, seccode, token);
    });



   });

function loadData (inv) {
    jQuery(".response").html('<img src="https://www.w3schools.com/jquery/demo_wait.gif" class="ploader">');


    jQuery.post( '/modules/mod_paypb/service.php',{
            'order': invoice,
            'email':userEmail,
            'token':token,
            'seccode':seccode,
            'href' :window.btoa(document.location.origin+document.location.pathname)
            })
        .done(
        function( data ) {

            if(isJson(data)){
                fragment = JSON.parse(data)
                fio = renderFirstData(fragment[0]);
                services = renderServiceName(fragment);
                payBut = renderPayButton(fragment);
                jQuery('.response').html(fio).show();
                jQuery('.response1').html(services).show();
                jQuery('.response2').html(payBut).show();
                jQuery('.responseWrapper').show();
            }else{
                jQuery('.response1').hide();
                jQuery('.response2').hide();
                jQuery('.response').html(data+"<p class='uk-text-center'><input type=\"button\" onclick=\"cancel()\" class=\"cancel uk-button uk-button-danger\" name=\"btn_text\" value=\"Повернутись\"></p>");

                jQuery('.responseWrapper').show();
            };

        });

    function isObjectEmpty(obj) {
        return Object.keys(obj).length === 0;
    }

};

function isJson(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}

function cancel(){
    jQuery('.responseWrapper').hide();
    //jQuery('#basic-form input').val('');
    jQuery('.formOplata').show();
}

function agrementpb(){
    var checkBox = document.querySelector('.agreement input');
    var inputPayAction = document.querySelector(".sendForm>.submit");
    if (checkBox.checked == true){
        inputPayAction.removeAttribute("disabled");
    } else {
        inputPayAction.setAttribute("disabled", "true");
    }
}


function renderFirstData(fragment) {
    // Получаем шаблон
    var templateScript =jQuery('#users-template').html();

// Функция Handlebars.compile принимает шаблон и возвращает новую функцию
    var template = Handlebars.compile(templateScript);

// Формируем HTML и вставляем в документ
    return template(fragment);

}


function renderServiceName(fragment){
    var service ={
        name:fragment[1],
    }

    var templateScript =jQuery('#service-template').html();
// Функция Handlebars.compile принимает шаблон и возвращает новую функцию
    var template = Handlebars.compile(templateScript);
// Формируем HTML и вставляем в документ
    return template(service);

};


function renderPayButton(fragment){
    var button = Object.assign({}, fragment[2]);

    var templateScript =jQuery('#payButton-template').html();
// Функция Handlebars.compile принимает шаблон и возвращает новую функцию
    var template = Handlebars.compile(templateScript);
// Формируем HTML и вставляем в документ
    return template(button);

};
