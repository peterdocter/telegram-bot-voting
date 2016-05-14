<?php

require('../php/inc.php');
require('module.php');

$db = getDb();

$stmt1 = $db->prepare("SELECT DATE_FORMAT(create_date, '%e %M') 'label', DAY(create_date) 'day', COUNT(1) 'n' FROM voter GROUP BY DATE_FORMAT(create_date, '%e %M') ORDER BY 1;");
$stmt1->execute();
$result = $stmt1->fetchAll();
file_put_contents("../php/chart/daily_grow.json", "{\"items\":". json_encode($result). "}");

$stmt1 = $db->prepare("SELECT DATE_FORMAT(create_date, '%e %M') 'label', DAY(create_date) 'day', COUNT(1) 'n' FROM voter where stage='Q14' GROUP BY DATE_FORMAT(create_date, '%e %M') ORDER BY 1;");
$stmt1->execute();
$result = $stmt1->fetchAll();
file_put_contents("../php/chart/daily_vote.json", "{\"items\":". json_encode($result). "}");

$stmt1 = $db->prepare("select q1 'label', count(1) * 100 / informal.total 'value' from informal_question, (SELECT count(1) total FROM informal_question) informal group by q1;");
$stmt1->execute();
$result = $stmt1->fetchAll();
file_put_contents("../php/chart/informal1.json", "{\"items\":". json_encode($result). "}");

$stmt1 = $db->prepare("SET @idx=0;");
$stmt1->execute();
$stmt1 = $db->prepare("select name, val, @idx:=@idx+1 AS idx from (SELECT q1 'name', round(count(1) * 100 / informal.total, 1) 'val' from informal_question, (SELECT count(1) total FROM informal_question) informal group by q1 order by val desc) final;");
$stmt1->execute();
$result = $stmt1->fetchAll();
file_put_contents("../php/chart/informal2.json", "{\"items\":". json_encode($result). "}");

$stmt1 = $db->prepare("SELECT q1 'name', round(count(1) * 100 / informal.total, 1) 'percent' from informal_question, (SELECT count(1) total FROM informal_question) informal group by q1 order by percent desc;");
$stmt1->execute();
$result = $stmt1->fetchAll();
file_put_contents("../php/chart/informal3.json", "{\"items\":". json_encode($result). "}");

$db = null;

?>
