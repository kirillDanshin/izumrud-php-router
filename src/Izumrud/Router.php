<?php

namespace Izumrud;

class Router
{
    /**
     * @var array List of URI's to match against
     */
    private $_listUri = array();

    /**
     * @var array List of closures to call
     */
    private $_listCall = array();

    /**
     * @var string Class-wide items to clean
     */
    private $_trim = '/\^$';

    /**
     * @var string Class-wide items to clean
     */
    private $_notFoundFunction = null;

    /**
     * @var string Class-wide items to clean
     */
    private $_found = false;

    /**
     * @var string Class-wide project identifier
     */
    private $_project = null;

    /**
     * @var string Class-wide projects register
     */
    private $_projects = null;

    /**
     * @var string Class-wide projects register
     */
    private $_projectHadlers = null;

    /**
     * @var string Class-wide routing map handler
     */
    private $_routingMap = null;

    /**
     * @var string Flag for checking global-wide URIs
     */
    private $_check_globals = true;

    /**
     * readRoutingMap - Reads "routes.map" file in project directory.
     */
    public function __construct()
    {
        if ($this->_project === null) {
            if (!array_key_exists($this->getWorkingProject(), $this->_projects)) {
                $this->project = 'global';
            }
        }
    }

    public function getWorkingProject()
    {
        if (array_key_exists()) {
        }

        return 'global';
    }

    /**
     * add - Adds a URI and Function to the two lists.
     *
     * @param string          $uri      A path such as about/system
     * @param callable|object $function An anonymous function
     * @param string|null     $project
     */
    public function add($uri, callable $function, $project = null)
    {
        $uri = trim($uri, self::$_trim);
        $project = is_null($project) ? 'global' : trim($project, 'www.');
        self::$_listUri[$project][] = $uri;
        self::$_listCall[$project][] = $function;
    }

    public function addProjectHandler($callback, $project)
    {
        if (Izumrud::getWorkingProject() == $project) {
            if (is_callable($callback)) {
                self::$_projectHadlers[$project] = $callback;
            }
        }
    }

    public function setNotFoundAction($notFoundFunction, $project = 'global')
    {
        if (is_callable($notFoundFunction)) {
            self::$_notFoundFunction[$project] = $notFoundFunction;

            return true;
        } else {
            return false;
        }
    }

    /**
     * submit - Looks for a match for the URI and runs the related function.
     */
    public function submit($check_global = false)
    {
        if (isset(self::$_projectHadlers[Izumrud::getWorkingProject()])) {
            call_user_func(self::$_projectHadlers[Izumrud::getWorkingProject()]);
        }
        self::projects();
        $uri = isset($_REQUEST['uri']) ?
            ($_REQUEST['uri'] == '/' ? 'home' : (empty($_REQUEST['uri']) ? 'home' : $_REQUEST['uri'])) : 'home';
        $uri = trim($uri, self::$_trim);
        $project = $check_global ? 'global' : Izumrud::getWorkingProject();
        $replacementValues = array();

        /*
         * List through the stored URI's
         */
        foreach (self::$_listUri[$project] as $listKey => $listUri) {
            /*
             * See if there is a match
             */
            if (preg_match("#^$listUri$#", $uri)) {
                /*
                 * Replace the values
                 */
                $realUri = explode('/', $uri);
                $fakeUri = explode('/', $listUri);
                self::$_found = true;

                /*
                 * Gather the .+ values with the real values in the URI
                 */
                foreach ($fakeUri as $key => $value) {
                    if ($value == '.+') {
                        $replacementValues[] = $realUri[$key];
                    }
                }

                /*
                 * Pass an array for arguments
                 */
                call_user_func_array(self::$_listCall[$project][$listKey], $replacementValues);
            }
        }

        if (self::$_found == false) {
            if ($project !== 'global') {
                if (self::submit(true) == false) {
                    $arguments = array('requested_uri' => $uri);
                    $notFoundFunctionProject = array_key_exists($project, self::$_notFoundFunction) ? $project :
                        'global';
                    call_user_func_array(self::$_notFoundFunction[$notFoundFunctionProject], array($arguments));
                }
            }

            return false;
        } else {
            return true;
        }
    }

    /**
     * projects - Reads "routes.map" file in project directory.
     */
    public function projects()
    {
        self::$_projects = Izumrud::getProjectsList();
    }
}

/* -*- mode: php;-*- */
