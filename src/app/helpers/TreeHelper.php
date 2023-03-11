<?php
namespace app\helpers;

use std;
use gui;

class TreeHelper
{
    function makeTree(UXTreeItem $root, array $items, callable $callback = null)
    {
        foreach ($items as $key => $value) {
            $value = trim($value);

            if (empty($value)) continue;

            if ($root->children->count() > 0) {
                foreach ($root->children->toArray() as $child) {
                    if ($child->value === $value) {
                        $this->makeTree($child, array_slice($items, 1), $callback);
                        return;
                    }
                }
            }

            $root->children->add($root = new UXTreeItem($value));
            

            if (is_callable($callback)) {
                $callback($root, true);
            }
        }

        if (is_callable($callback)) {
            $callback($root, false);
        }
    }

    /**
     * @param UXTreeItem $node
     * @param string $path
     * @return string
     */
    function getPath(UXTreeItem $node, $path = ""): string
    {
        if ($node->parent instanceof UXTreeItem) {
            $path = $node->value . "\\" . $path;

            return $this->getPath($node->parent, $path);
        }

        return rtrim($node->value . "\\" . $path, "\\");
    }

    /**
     * @param UXTreeItem $node
     */
    public function sort(UXTreeItem $node): void
    {
        if ($node->children->isNotEmpty()) {
            $notEmpty = [];
            $empty = [];

            foreach ($node->children as $key => $children) {
                if ($children->children->isNotEmpty()) {
                    $notEmpty[] = $children;
                } else {
                    $empty[] = $children;
                }
            }

            uasort($notEmpty, function ($a, $b) {
                return str::compare($a->value, $b->value);
            });

            uasort($empty, function ($a, $b) {
                return str::compare($a->value, $b->value);
            });

            $node->children->clear();
            $node->children->addAll($notEmpty);
            $node->children->addAll($empty);

            foreach ($node->children as $children) {
                $this->sort($children);
            }
        }
    }
}