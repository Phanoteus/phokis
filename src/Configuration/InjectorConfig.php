<?php declare(strict_types = 1);
/**
 * A class for configuring the Auryn injector.
 *
 * @see https://github.com/rdlowrey/auryn
 */

namespace Phanoteus\Phokis\Configuration;

use Auryn\Injector;

class InjectorConfig
{
    private $aliases;
    private $definitions;
    private $delegates;
    private $prepares;
    private $shares;

    /**
     * InjectorConfig constructor
     *
     * @param array $aliases     Contains mappings for the Injector's `alias` function.
     * @param array $definitions Mappings for the `define` function.
     * @param array $delegates   Mappings for the `delegate` function.
     * @param array $prepares    Mappings for the `prepare` function.
     * @param array $shares      Mappings for the `share` function.
     */
    public function __construct(
        array $aliases = [],
        array $definitions = [],
        array $delegates = [],
        array $prepares = [],
        array $shares = []
    )
    {
        $this->aliases = $aliases;
        $this->definitions = $definitions;
        $this->delegates = $delegates;
        $this->prepares = $prepares;
        $this->shares = $shares;
    }

    /**
     * Applies mappings/configuration to Injector.
     *
     * @param  Injector     $injector
     * @param  bool|boolean $shareInjector Indicates whether to share the Injector itself.
     * @return void
     */
    public function configure(Injector $injector, bool $shareInjector = false)
    {
        foreach ($this->aliases as $item => $alias) {
            $injector->alias($item, $alias);
        }

        foreach ($this->definitions as $item => $definition) {
            $injector->define($item, $definition);
        }

        foreach ($this->delegates as $item => $delegate) {
            $injector->delegate($item, $delegate);
        }

        foreach ($this->prepares as $item => $prep) {
            $injector->prepare($item, $prep);
        }

        foreach ($this->shares as $item) {
            $injector->share($item);
        }

        if ($shareInjector) {
            $injector->share($injector);
        }
    }
}
