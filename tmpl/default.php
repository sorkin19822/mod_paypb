<?php
defined('_JEXEC') or die;
JHtml::_('behavior.formvalidator');

// Get a handle to the Joomla! application object
$app = JFactory::getApplication();
$flag = 0;
if($flag) {
    JFactory::getApplication()->enqueueMessage('Message');
}
modPaypbHelper::getSecCode();
//$this->setState('list.limit', '111');
?>

<div class="formOplata">
<form id="basic-form" action="/" method="post">
	<table>
		<tr>
			<td class="uk-text-right"><label for="orderid">Номер рахунку <span style="color: red">*</span></label></td>
			<td><input id="orderid" class=" formOrderID" type="text" name="country_code" maxlength="12" pattern="[0-9A-Za-zА-Яа-я]{8,12}" <?= modPaypbHelper::getOrderId()?>  placeholder="введіть номер" title="" required></td>
		</tr>
		<tr>
			<td class="uk-text-right"><label for="orderEmail">Ваша пошта<span style="color: red">*</span></label></td>
			<td><input id="orderEmail" class="formEmail required validate-email" type="email" name="email" maxlength="35" placeholder="введіть email" title="user@domain.name" required></td>
		</tr>
        <tr>
            <td class="uk-text-right"><label for="seccode">Перевірочний код</label></td>
            <td><input id="orderEmail" class="seccode" type="text" name="seccode" maxlength="35" pattern="[0-9A-Za-zА-Яа-я]{5,7}"  value="<?= modPaypbHelper::getSecCode()?>" placeholder="перевірочний код" title=""></td>
        </tr>
	</table>
    <?php if($captchaEnabled === true && $captchaPublicKey !== ""){ ?>
        <div data-sitekey="<?php echo $captchaPublicKey; ?>">
            <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response" class="invisible-recaptcha">
        </div>
    <?php }; ?>
    <p class="uk-text-center"><a class="getData uk-button uk-button-success">Знайти</a></p>
</form>
</div>
<div class="responseWrapper">
    <div class="response"></div>
    <div class="response1"></div>
    <div class="response2"></div>
</div>




<script id="users-template" type="x-handlebars-template">
        <table cols="2" class="uk-table uk-table-striped">
            <thead>
            <tr>
                <th colspan="2" class="uk-text-center">Найдены следующие данные по счету №{{order}}</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>Пациентка</td>
                <td>{{patient}}</td>
            </tr>
            <tr>
                <td>Дата приема</td>
                <td>{{date}}</td>
            </tr>
            <tr>
                <td>К оплате</td>
                <td>{{total}} грн.</td>
            </tr>
            </tbody>
        </table>
</script>

<script id="service-template" type="x-handlebars-template">
    <table width="100%" class="uk-text-center uk-table uk-table-striped">
        <tr>
            <th>Наименование услуги</th>
            <th>Цена</th>
        </tr>
        {{#each name}}
        <tr>
            <td>{{service_name}}</td>
            <td>{{price}} грн.</td>
        </tr>
        {{/each}}
    </table>
</script>


<script id="payButton-template" type="x-handlebars-template">
    {{{button}}}
</script>

