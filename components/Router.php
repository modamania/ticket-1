<?php

class Router
{
    private $routes;//маршруты
    private $page_404;//равно 1, если страница не найдена

    /**
     * Конструктор
     */
    public function __construct(){
        //путь к роутеру
        $routesPath = ROOT.'/config/routes.php';
        //подключаем массив роутов
        $this->routes = include($routesPath);
        $this->page_404 = 1;
    }

    /**
     * Метод, который возвращает строку запроса без имени сайта
     * @return string
     */
    private function getURI()
    {
        if (!empty($_SERVER['REQUEST_URI'])) {
            return trim($_SERVER['REQUEST_URI'], '/');
        }
    }

    /**
     * Метод, который осуществляет анализ запроса
     * и передачёт управления на нужный action
     */
    public function run(){
        $uri = $this->getURI();

        //$uriPattern - левая часть роута (из строки зароса)
        //$path - правая часть роута (нужный путь)

        $num_path = 0;
        foreach ($this->routes as $uriPattern => $path) {
            preg_match("~$uriPattern~", $uri, $matches);

            if(!empty($matches)) {

                //если не домашняя страница
                if(strlen($matches[0]) == 0 && strlen($uri) != 0){
                    $this->page_404 = 1;
                    break;
                }

                $this->page_404 = 0;
                //паттерн с регулярным выражением
                $internalRoute = preg_replace("~$uriPattern~", $path, $uri);
                $segments = explode('/', $internalRoute);
                $controllerName = array_shift($segments).'Controller';
                $controllerName = ucfirst($controllerName);//делаем первую букву заглавной
                $actionName = 'action'.ucfirst((array_shift($segments)));
                $controllerFile = ROOT . '/controllers/' .$controllerName. '.php';

                //подключаем файл класса контроллера
                if (file_exists($controllerFile)) {
                    include_once($controllerFile);
                }

                $controllerObject = new $controllerName;

                //параметры (категория и id)
                $result = null;

                if(!empty($segments) > 0){
                    $parameters = $segments;
                    try {
                        $result = call_user_func_array(array($controllerObject, $actionName), $parameters);
                    } catch (Exception $e) {
                        $this->page_404 = 1;
                    }
                    break;
                }
                else {
                    try {
                        $result = call_user_func(array($controllerObject, $actionName));
                    }
                    catch (Exception $e) {
                        $this->page_404 = 1;
                    }
                    break;
                }

                if ($result != null) {
                    $this->page_404 = 1;
                    break;
                }
            }
            $num_path ++;
        }
        if($this->page_404 == 1){
            //если страница не найдена
            $controllerObject = new SiteController;
            $actionName = 'action404';
            $result = call_user_func(array($controllerObject, $actionName));
        }
    }
}
