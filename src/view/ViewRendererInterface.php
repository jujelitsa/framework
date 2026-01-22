<?php

namespace jujelitsa\framework\view;

interface ViewRendererInterface
{
    /**
     * Рендер страницы
     * Пример вызова:
     * Рендер html-страницы из файла проекта /view/html/site/about/index.php
     * (new View())->render('site/about/index', compact('administratorName', 'companyPhone'))
     * Рендер ansi консольного вывода из файла проекта /view/ansi/calculation/index.php
     * (new View())->render('calculation/index', compact('context'))
     * 
     * @param string $view имя вью файла отрисовки страницы
     * @param array $params значения переменных, используемых для отрисовки представления
     * @return string 
     */
    function render(string $view, array $params = []): string;

}