<?php

define('BASE_URL', '//' . $_SERVER['HTTP_HOST'] . '/autostation_php');
function route($url): void
{
    $url = ltrim($url, "/");
    $url = explode("/", $url);

    $page = $url[0] ?? "home";

    $id = $url[1] ?? "";

    switch ($page) {
        case "create":
            require_once "views/create.php";
            break;
        case "view":
            if ($id && is_numeric($id)) {
                require_once "views/view.php";
            } else {
                echo "Не указан id записи";
            }
            break;
        case "edit":
            if ($id && is_numeric($id)) {
                require_once "views/edit.php";
            } else {
                echo "Не указан id записи";
            }
            break;
        case "delete":
            if ($id && is_numeric($id)) {
                require_once "views/delete.php";
            } else {
                echo "Не указан id записи";
            }
            break;
        default:
            require_once "views/home.php";
            break;
    }
}
