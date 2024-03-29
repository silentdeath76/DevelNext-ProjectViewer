<?php


namespace app\ui\notify;


use gui;
use app\ui\AbstractNode;

class UpdateNotify extends AbstractNode
{
    /**
     * @var UXLabelEx
     */
    private $label;

    /**
     * @var UXButton
     */
    private $button;

    protected function make()
    {
        $this->container = new UXHBox();
        $this->container->add($this->label = new UXLabelEx(""));
        $this->container->add($this->button = new UXFlatButton(""));
        $this->container->spacing = 5;
        $this->container->padding = 5;
        $this->container->alignment = "CENTER_LEFT";
        $this->container->style = '-fx-background-radius: 5; -fx-border-radius: 5; -fx-background-color: #0000002F';
        $this->container->opacity = 0;

        $this->label->ellipsisString = null;
        $this->label->autoSize = true;
        $this->label->autoSizeType = 'HORIZONTAL';

        $this->button->backgroundColor = '#00000000';
        $this->button->hoverColor = '#0000001F';
        $this->button->clickColor = ' #0000000F';
        $this->button->borderRadius = 3;
        $this->button->padding = 3;
        $this->button->font->bold = true;
        $this->button->width = 90;
        $this->button->alignment = 'CENTER';

        $this->button->ellipsisString = null;
    }

    public function show ($text, $buttonText, callable $callback, $customPadding = 0) {
        $this->label->text = $text;
        $this->button->text = $buttonText;
        
        $this->container->maxWidth = 0;
        $this->button->on("click", $callback);

        $toSize = UXFont::getDefault()->calculateTextWidth($text) + UXFont::getDefault()->calculateTextWidth($buttonText) + 30 + $this->container->paddingRight + $customPadding;

        $this->applyAnimation($toSize, $target);
    }

    private function applyAnimation ($toSize, $target) {
        $show = new UXAnimationTimer(function () use (&$show, $toSize, $target) {
            $step = 20;
            $this->container->maxWidth += $step;
            $this->container->width += $step;
            // $this->container->x = $target->x + $target->width - $this->container->width + 1;
            $this->container->opacity += 0.2;

            if ($this->container->maxWidth > $toSize) {
                $show->stop();
            }
        });

        $show->start();
    }
    
}