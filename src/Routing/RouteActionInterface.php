<?php declare(strict_types = 1);

namespace Phanoteus\Phokis\Routing;

interface RouteActionInterface
{
  public function getAction(): ?callable;
  public function setAction(callable $action);

  public function getParameters(): array;
  public function setParameters(array $parameters);
  public function mergeParameters(array $parameters);
}