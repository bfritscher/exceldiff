<?php
//error_reporting(E_ALL);
//ini_set("display_errors", 1);
require('excel_diff.php');
//TODO move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile);
$solfile = getcwd() . "/sol.xlsx";
$excelDiff = new ExcelDiffComponent;
$excelDiff->compare($_FILES['userfile']['tmp_name'], $solfile);
echo $excelDiff->getJsonResult();
?>