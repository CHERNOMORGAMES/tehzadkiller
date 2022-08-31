<?php
# Так совпало, что задание получил, находясь на больничном, большую часть времени работать не мог из-за высокой температуры.
# Доволен, что вообще смог доделать, ну и с базой было интересно повозиться.
# Реализовал простой однофайловый вариант, с упором на баланс нагрузки между mysql и php.
# В вёрстке использовал мой шаблон для email'ов, т.к. он не нуждается во внешних ресурсах и работает везде.

# Access to DB without password is insecure, can cause multiple problems and shouldn't be used in production.
# To make it work in testing purposes, good way is to change mysql auth_socket plugin like:
# sudo mysql
# USE mysql;
# ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '';

$db_server = 'localhost';
$db_port = '3306';
$db_user = 'root';
$db_pass = '';
$db_name = 'db_test';
$db_options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];

try
{
  $pdo = new PDO("mysql:host=$db_server;port=$db_port;dbname=$db_name", $db_user, $db_pass, $db_options);
  $check = "Connected successfully <br>";
}
catch(PDOException $e)
{
  $check = "Connection failed: " . $e->getMessage();
}

function db_get($pdo, $statement, $params = null)
{
$query = $pdo->prepare($statement);
$query->execute($params);
return $query->fetchAll();
}


if (isset($_GET["user_lang"])):
switch ($_GET["user_lang"]):
case 'eng': $user_lang = 'eng'; $lang = 'en-US'; break;
case 'ger': $user_lang = 'ger'; $lang = 'de-DE'; break;
default: $user_lang = 'rus'; $lang = 'ru-RU';
endswitch;
else: $user_lang = 'rus'; $lang = 'ru-RU';
endif;

if (isset($_GET["glob_region"])):
switch ($_GET["glob_region"]):
case '2': $G_REGION = 2; break;
case '3': $G_REGION = 3; break;
default: $G_REGION = 1;
endswitch;
else: $G_REGION = 1;
endif;

# COUNTRIES
$params = "country.id, country.glob_region_id, country.c_name_$user_lang, country.c_descr_$user_lang, glob_region.gr_name_$user_lang, glob_region.gr_descr_$user_lang";
$statement = "SELECT $params FROM country LEFT JOIN glob_region ON(country.glob_region_id = glob_region.id) WHERE country.glob_region_id=?";
$countries = db_get($pdo, $statement, ["$G_REGION"]);

# REGIONS
$params = "region.id, region.r_country_id, region.r_name_$user_lang, region.r_descr_$user_lang";
$statement = "SELECT $params FROM region LEFT JOIN country ON(region.r_country_id = country.id) WHERE country.glob_region_id=?";
$regions = db_get($pdo, $statement, ["$G_REGION"]);

# CITIES
$params = "city.c_country_id, city.c_region_id, city.c_name_$user_lang, city.c_descr_$user_lang";
$statement = "SELECT $params FROM city LEFT JOIN country ON(city.c_country_id = country.id) WHERE country.glob_region_id=?";
$cities = db_get($pdo, $statement, ["$G_REGION"]);

$noregion = [];
foreach ($cities as $key => $city):
if ($city['c_region_id'] == 0):
array_push($noregion, $city);
unset($cities["$key"]);
endif;
endforeach;
$cities = array_values($cities); #array reindex

foreach ($regions as $r_ind => $region):
    foreach ($cities as $city):
        if ($city['c_region_id'] == $region['id']) $regions["$r_ind"]['CITIES'][] = $city;
    endforeach;
endforeach;
unset($cities);

foreach ($countries as $c_ind => $country):
    foreach ($noregion as $city):
        if ($city['c_country_id'] == $country['id']) $countries["$c_ind"]['NOREGION'][] = $city;
    endforeach;

    foreach ($regions as $region):
        if ($region['r_country_id'] == $country['id']) $countries["$c_ind"]['REGIONS'][] = $region;
    endforeach;
endforeach;
unset($noregion, $regions);

?>

<!DOCTYPE html>
<html lang="<?php echo $lang;?>" xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="x-apple-disable-message-reformatting">
<!--[if !mso]><!-->
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<!--<![endif]-->
<title></title>
<!--[if mso]>
<style type="text/css">
    table {border-collapse:collapse;border-spacing:0;margin:0;}
    div, td {padding:0;}
    div {margin:0 !important;}
</style>
<noscript>
<xml>
    <o:OfficeDocumentSettings>
        <o:PixelsPerInch>96</o:PixelsPerInch>
    </o:OfficeDocumentSettings>
</xml>
</noscript>
<![endif]-->
</head>
<body style="width:100%;Margin:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;background-color:#f3f3f3 !important;color:#0a0a0a !important;word-spacing:normal;font-family:Helvetica,Arial,sans-serif;font-size:16px;font-weight:400;line-height:20px;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;-moz-hyphens:none;-webkit-hyphens:none;hyphens:none;-moz-box-sizing:border-box;-webkit-box-sizing:border-box;box-sizing:border-box;">
<div role="article" aria-roledescription="email" lang="<?php echo $lang;?>" style="width:100%;Margin:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;background-color:#f3f3f3 !important;color:#0a0a0a !important;word-spacing:normal;font-family:Helvetica,Arial,sans-serif;font-size:16px;font-weight:400;line-height:20px;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;-moz-hyphens:none;-webkit-hyphens:none;hyphens:none;-moz-box-sizing:border-box;-webkit-box-sizing:border-box;box-sizing:border-box;">
<table align="center" role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:100%;Margin:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;background-color:#f3f3f3 !important;color:#0a0a0a !important;word-spacing:normal;font-family:Helvetica,Arial,sans-serif;font-size:16px;font-weight:400;line-height:20px;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;-moz-hyphens:none;-webkit-hyphens:none;hyphens:none;-moz-box-sizing:border-box;-webkit-box-sizing:border-box;box-sizing:border-box;" bgcolor="#f3f3f3">
<tr style="Margin:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;">
<td align="center" style="Margin:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;">
<!--[if mso]>
<table role="presentation" align="center" style="width:600px;">
<tr>
<td style="padding:0px 0;">
<![endif]-->
<div style="width:100%;max-width:600px;Margin:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;">

<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;background-color:;" bgcolor="">
    <tr>
        <td align="center" style="Margin:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;line-height:0px;height:40px;background-color:;"bgcolor="">
            <a href="https://www.bantikov.ru" style="Margin:0;padding:0;text-align:center;vertical-align:middle;font-family:Helvetica,Arial,sans-serif;text-decoration:none;font-size:20px;font-weight:400;color:#ffffff;" target="_blank">
                <img alt="Company logo" src="https://www.bantikov.ru/assets/new/logo-small-a26b535dcbb6950d99d5ddb2424cf67e.png" width="600" style="width:100%;height:auto;border:none;outline:0;text-align:center;text-decoration:none;-ms-interpolation-mode:bicubic;" />
            </a>
        </td>
    </tr>
</table>

<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;background-color:#f3f3f3;" bgcolor="#f3f3f3">
    <tr>
        <td align="center" style="Margin:0;padding-top:15px;padding-right:0;padding-bottom:10px;padding-left:0;font-size:18;line-height:18px;height:18px;background-color:#f3f3f3;" bgcolor="#f3f3f3">
            <p style="Margin:0 00px 12px 00px;padding:0;word-break:break-all;text-align:center;vertical-align:middle;font-family:Helvetica,Arial,sans-serif;font-size:20px;font-weight:600;line-height:18px;color:#0a0a0a;" title="global region">EUROPE
            </p>
            <p style="Margin:0 00px 12px 00px;padding:0;word-break:break-all;text-align:center;vertical-align:middle;font-family:Helvetica,Arial,sans-serif;font-size:20px;font-weight:400;line-height:18px;color:#009688;" title="database status"><?= $check; ?>
            </p>
        </td>
    </tr>
</table>

<div style="width:100%;max-width:300px;Margin:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;display:inline-block;vertical-align:middle;">

<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;background-color:#ffffff;" bgcolor="#ffffff">
    <tr>
        <td align="center" width="300" style="width:300px;Margin:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;line-height:16px;height:16px;background-color:#ffffff;" bgcolor="#ffffff">

<?php foreach($countries as $country): ?>

<p style="Margin:0 40px 12px 40px;padding:0;word-break:break-all;text-align:left;vertical-align:middle;font-family:Helvetica,Arial,sans-serif;font-size:20px;font-weight:600;line-height:18px;color:#0a0a0a;" title="<?= $country["c_descr_$user_lang"]; ?>"><?= $country["c_name_$user_lang"]; ?>
</p>

    <?php foreach($country['NOREGION'] as $noregion): ?>

    <p style="Margin:0 20px 12px 80px;padding:0;word-break:break-all;text-align:left;vertical-align:middle;font-family:Helvetica,Arial,sans-serif;font-size:18px;font-weight:400;line-height:18px;color:#0a0a0a;" title="<?= $noregion["c_descr_$user_lang"]; ?>"><?= $noregion["c_name_$user_lang"]; ?>
    </p>

    <?php endforeach; ?>

    <?php foreach($country['REGIONS'] as $region): ?>

    <p style="Margin:0 20px 12px 80px;padding:0;word-break:break-all;text-align:left;vertical-align:middle;font-family:Helvetica,Arial,sans-serif;font-size:18px;font-weight:400;line-height:18px;color:#0a0a0a;" title="<?= $region["r_descr_$user_lang"]; ?>"><?= $region["r_name_$user_lang"]; ?>
    </p>

        <?php if (isset($region['CITIES'])): foreach($region['CITIES'] as $city): ?>

        <p style="Margin:0 20px 12px 100px;padding:0;word-break:break-all;text-align:left;vertical-align:middle;font-family:Helvetica,Arial,sans-serif;font-size:18px;font-weight:400;line-height:18px;color:#0a0a0a;" title="<?= $city["c_descr_$user_lang"]; ?>"><?= $city["c_name_$user_lang"]; ?>
        </p>

        <?php endforeach; endif; ?>

    <?php endforeach; ?>

<?php endforeach; ?>

        </td>
    </tr>
</table>

<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;background-color:#f3f3f3;" bgcolor="#f3f3f3">
    <tr>
        <td align="center" style="Margin:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;font-size:0;line-height:18px;height:18px;background-color:#f3f3f3;" bgcolor="#f3f3f3">&nbsp;</td>
    </tr>
</table>
</div>

<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;background-color:#131313;" bgcolor="#131313">
    <tr>
        <td align="center" style="Margin:0;padding-top:14px;padding-right:0;padding-bottom:0;padding-left:0;line-height:16px;height:16px;background-color:#131313;" bgcolor="#131313">

            <p style="Margin:4px 0 8px 0;padding:0;text-align:center;vertical-align:middle;font-family:Helvetica,Arial,sans-serif;font-size:14px;font-weight:400;line-height:14px;color:#ffffff;" title="This is my own template from another project">
                Vladimir Chernomor
                <span style="color:#009688;font-size:16px;font-weight:600;">|</span>
                responsive email markup 
            </p>


            <p style="Margin:0 0 16px 0;padding:0;text-align:center;vertical-align:middle;font-family:Helvetica,Arial,sans-serif;font-size:16px;font-weight:400;line-height:26px;color:#2199e8;" title="Originaly here was my email">
                <a href="mailto:vladimirzhukov86@mail.ru" style="Margin:0;padding:0;text-align:center;vertical-align:middle;font-family:Helvetica,Arial,sans-serif;font-style:italic;font-size:14px;font-weight:600;line-height:14px;color:#009688;text-decoration:none;" target="_blank">
                    https://github.com/chernomorgames</a>
            </p>

        </td>
    </tr>
</table>

</div>
<!--[if mso]>
</td>
</tr>
</table>
<![endif]-->
</td>
</tr>
</table>
</div>
<!-- prevent Gmail on iOS font size manipulation -->
<div style="display:none;white-space:nowrap;font:15px courier;line-height:0;">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</div>

</body>
</html>
