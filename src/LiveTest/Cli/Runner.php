<?php

/*
 * This file is part of the LiveTest package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LiveTest\Cli;

use Base\Http\Client\Zend;

use Annovent;

use LiveTest;

use Annovent\Event\Event;
use Annovent\Event\Dispatcher;

use LiveTest\TestRun;
use LiveTest\Listener\Listener;

use Base\Www\Uri;
use Base\Cli\ArgumentRunner;
use Base\Config\Yaml;

use LiveTest\TestRun\Properties;
use LiveTest\TestRun\Run;

class Runner extends ArgumentRunner
{
//  protected $mandatoryArguments = array ('testsuite' );

  private $config;
  private $testSuiteConfig;

  private $eventDispatcher;

  private $extensions = array ();

  private $testRun;
  private $runId;

  private $runAllowed = true;

  private $defaultDomain = 'http://www.example.com';

  public function __construct($arguments, Dispatcher $dispatcher)
  {
    parent::__construct($arguments);

    $this->eventDispatcher = $dispatcher;

    $this->initRunId();
    $this->initConfig();

    if( !$this->initListener($arguments) )
    {
      $this->initGlobalSettings();
      $this->initTestSuiteConfig();
      $this->initDefaultDomain();
    }
  }

  private function initDefaultDomain()
  {
    $domain = $this->config->DefaultDomain;
    if ($domain != '')
    {
      $this->defaultDomain = (string)$domain;
    }
  }

  private function initRunId()
  {
    $this->runId = (string)time();
  }

  private function initConfig()
  {
    if ($this->hasArgument('config'))
    {
      $configFileName = $this->getArgument('config');
    }
    else
    {
      $configFileName = __DIR__ . '/../../default/config.yml';
    }

    if (!file_exists($configFileName))
    {
      throw new \LiveTest\Exception('The config file (' . $configFileName . ') was not found.');
    }

    $defaultConfig = new Yaml(__DIR__ . '/../../default/config.yml', true);
    $currentConfig = new Yaml($configFileName, true);

    if (!is_null($currentConfig->Listener))
    {
      $currentConfig->Listener = $defaultConfig->Listener->merge($currentConfig->Listener);
    }
    else
    {
      $currentConfig->Listener = $defaultConfig->Listener;
    }

    $this->config = $currentConfig;
  }

  private function initTestSuiteConfig()
  {
    $testSuiteFileName = $this->getArgument('testsuite');
    $this->testSuiteConfig = new Yaml($testSuiteFileName);
  }

  private function initGlobalSettings()
  {
    if (!is_null($this->config->Global))
    {
      if (!is_null($this->config->Global->external_paths))
      {
        $this->addAdditionalIncludePaths($this->config->Global->external_paths->toArray());
      }
    }
  }

  private function addAdditionalIncludePaths(array $additionalIncludePaths)
  {
    foreach ( $additionalIncludePaths as $path )
    {
      set_include_path(get_include_path() . PATH_SEPARATOR . $path);
    }
  }

  /**
   * @notify LiveTest.Runner.Init
   *
   * @param array()own_type $arguments
   */
  private function initListener($arguments)
  {
    if (!is_null($this->config->Listener))
    {
      foreach ( $this->config->Listener as $name => $extensionConfig )
      {
        $className = (string)$extensionConfig->class;
        if ($className == '')
        {
          throw new Exception('The class name for the "' . $name . '" listener is missing. Please check your configuration.');
        }
        if (is_null($extensionConfig->parameter))
        {
          $parameter = new \Zend_Config(array ());
        }
        else
        {
          $parameter = $extensionConfig->parameter;
        }
        $listener = new $className($this->runId, $this->eventDispatcher);
        $this->registerListener($listener, $parameter->toArray());
      }
    }
    $result = $this->eventDispatcher->notify('LiveTest.Runner.Init', array( 'arguments' => $arguments ));
    if (!$result)
    {
      $this->runAllowed = false;
    }
  }

  private function registerListener(Listener $listener, array $parameter = null)
  {
    \LiveTest\initializeObject($listener, $parameter);
    $this->eventDispatcher->registerListener($listener);
  }

  public function isRunAllowed()
  {
    return $this->runAllowed;
  }

  private function initTestRun()
  {
    $testRunProperties = new Properties($this->testSuiteConfig, new Uri($this->defaultDomain));
    $this->testRun = new Run($testRunProperties, new Zend(), $this->eventDispatcher);
  }

  public function run()
  {
    if ($this->isRunAllowed())
    {
      $this->initTestRun();
      $this->testRun->run();
    }
    else
    {
      throw new Exception('Not allowed to run');
    }
  }
}