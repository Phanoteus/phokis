<?php declare(strict_types = 1);

namespace Phanoteus\Phokis\Routing;

use Phanoteus\Phokis\Routing\RouteActionInterface;

class RouteAction implements RouteActionInterface
{
  private $action;
  private $parameters;

  public function __construct(callable $action = null, array $parameters = []) {
    $this->action = $action;
    $this->parameters = $parameters;    
  }

  public function getAction(): ?callable {
    return $this->action;
  }

  public function setAction(callable $action) {
    $this->action = $action;
  }

  public function getParameters(): array {
    return $this->parameters;
  }

  public function setParameters(array $parameters) {
    $this->parameters = $parameters;
  }

  public function mergeParameters(array $parameters) {
    $this->parameters = array_merge($this->parameters, $parameters);
  }
}