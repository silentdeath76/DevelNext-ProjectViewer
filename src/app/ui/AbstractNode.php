<?php


namespace app\ui;


use php\gui\layout\UXPane;

abstract class AbstractNode
{

    /**
     * @var UXPane
     */
    protected $container;

    /**
     * AbstractNode constructor.
     */
    public function __construct()
    {
        $this->make();
    }

    /**
     * @return UXPane
     */
    public function getNode()
    {
        return $this->container;
    }

    abstract protected function make();
}