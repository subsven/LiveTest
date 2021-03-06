<?php

use Base\Http\ConnectionStatus;
use LiveTest\TestRun\Information;
use LiveTest\TestRun\Properties;
use Base\Http\Response\Response;
use LiveTest\TestRun\Result\Result;
use Annovent\Event\Dispatcher;
use LiveTest\Listener\Listener;

class ConnectionStatusListener implements Listener
{
  private $handleConnectionStatusCalled = false;
  private $connectionStatus;

  public function __construct($runId, Dispatcher $eventDispatcher)
  {

  }

  /**
   * @event LiveTest.Run.HandleConnectionStatus
   */
  public function handleConnectionStatus(ConnectionStatus $connectionStatus)
  {
    $this->handleConnectionStatusCalled = true;
    $this->connectionStatus = $connectionStatus;
  }

  public function isHandleConnectionStatusCalled()
  {
    return $this->handleConnectionStatusCalled;
  }

  public function getConnectionStatus( )
  {
    return $this->connectionStatus;
  }
}