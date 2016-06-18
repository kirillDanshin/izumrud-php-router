<?php

namespace Izumrud;

class Router
{
    /**
     * @var array List of URI's to match against
     */
    private $_listUri = [];

    /**
     * @var array List of closures to call
     */
    private $_listCall = [];

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
     * @var {callable|null} Injeced logger
     */
    private $injected_logger = null;

    public function __construct()
    {
        // if ($this->_project === null) {
        //     if (!array_key_exists($this->GetWorkingProject(), $this->_projects)) {
        //         $this->project = 'global';
        //     }
        // }
    }

    public function GetWorkingProject()
    {
        // if (array_key_exists()) {
        // }

        return 'global';
    }

    /**
     * add - Adds a URI and Function to the two lists.
     *
     * @param string          $uri      A path such as about/system
     * @param callable|object $function An anonymous function
     * @param string|null     $project
     */
    public function Add($uri, callable $function, $project = null)
    {
        $uri = trim($uri, $this->_trim);
        $project = is_null($project) ? 'global' : trim($project, 'www.');
        $this->_listUri[$project][] = $uri;
        $this->_listCall[$project][] = $function;
    }

    /**
     * AddProjects adds projects to local registry.
     *
     * @param {array} $projects Projects to add
     */
    public function AddProjects(array $projects)
    {
        foreach ($projects as $name => $cfg) {
            if (isset($this->_projects[$name])) {
                $this->log('overriding project %s', $name);
            }
            $this->_projects[$name] = $cfg;
            if (isset($cfg['not_found_handler'])) {
                $this->SetNotFoundAction($cfg['not_found_handler'], $name);
            }
        }
    }

    /**
     * AddProjectHandler registers a project handler.
     *
     * @param {callable} $callback
     * @param {string}   $project
     */
    public function AddProjectHandler($callback, $project)
    {
        if ($this->GetWorkingProject() == $project) {
            if (is_callable($callback)) {
                $this->_projectHadlers[$project] = $callback;
            }
        }
    }

    /**
     * SetNotFoundAction sets a "not found" handler for a project.
     *
     * @param {callable} $notFoundFunction
     * @param {string}   $project
     */
    public function SetNotFoundAction($notFoundFunction, $project = 'global')
    {
        if (is_callable($notFoundFunction)) {
            $this->_notFoundFunction[$project] = $notFoundFunction;

            return true;
        } else {
            return false;
        }
    }

    /**
     * @param {callable} $logger - function($msg string)
     */
    public function InjectLogger($logger)
    {
        $this->log('overriding logger');
        if (is_callable($logger)) {
            $this->injected_logger = $logger;

            return true;
        }

        return false;
    }

    private function log()
    {
        if ($this->injected_logger == null || !is_callable($this->injected_logger)) {
            return false;
        }
        $args = func_get_args();
        $c = count($args);
        if ($c == 0) {
            return false;
        }
        if ($c > 1) {
            $msg = call_user_func_array('sprintf', $args);
        } else {
            $msg = $args[0];
        }
        call_user_func_array($this->injected_logger, [$msg]);

        return true;
    }

    /**
     * submit - Looks for a match for the URI and runs the related function.
     */
    public function Submit($check_global = false)
    {
        if (isset($this->_projectHadlers[$this->GetWorkingProject()])) {
            call_user_func($this->_projectHadlers[$this->GetWorkingProject()]);
        }

        if (isset($_REQUEST['uri']) && $_REQUEST['uri'] !== '/' && !empty($_REQUEST['uri'])) {
            $uri = preg_replace("/\/+/", '/', trim($_REQUEST['uri'], $this->_trim));
        } else {
            $uri = 'home';
        }
        $project = $check_global ? 'global' : $this->GetWorkingProject();
        $replacementValues = [];

        if (!isset($this->_listUri[$project])) {
            $this->log('"%s" project uri map wasn\'t found while serving "%s"', $project, $uri);

            // $this->

            return false;
        }

        /*
         * List through the stored URI's
         */
        foreach ($this->_listUri[$project] as $listKey => $listUri) {
            /*
             * See if there is a match
             */
            if (preg_match("#^$listUri$#", $uri)) {
                /*
                 * Replace the values
                 */
                $realUri = explode('/', $uri);
                $fakeUri = explode('/', $listUri);
                $this->_found = true;

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
                call_user_func_array($this->_listCall[$project][$listKey], $replacementValues);
            }
        }

        if ($this->_found == false) {
            $args = ['requested_uri' => $uri];
            if (isset($this->_notFoundFunction[$project])) {
                call_user_func_array($this->_notFoundFunction[$project], [$args]);
            }
            if ($project !== 'global') {
                if (self::submit(true) == false) {
                    $selProject = array_key_exists($project, $this->_notFoundFunction) ? $project :
                        'global';
                    call_user_func_array($this->_notFoundFunction[$selProject], [$args]);
                }
            }

            return false;
        } else {
            return true;
        }
    }

    /**
     * Projects - returns current projects registry.
     *
     * @return {array} projects
     */
    public function Projects()
    {
        return $this->_projects;
    }
}

/* -*- mode: php;-*- */
