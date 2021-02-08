<?php

namespace JATSParser\Body;

use DOMElement;
use DOMNode;
use JATSParser\Body\JATSElement as JATSElement;
use JATSParser\Body\Document as Document;
use JATSParser\Body\Text as Text;

/**
 * Class Listing
 * @package JATSParser\Body
 */
class Listing extends AbstractElement
{

    /*
     * @var int
     * type of a list: 1, 2, 3, 4 -> list, sublist, subsublist, etc.
     * default is 1
     */
    private $type;

    /* @var string: "unordered", "ordered" */
    private $style;

    private $content;

    public function __construct(DOMElement $list)
    {
        parent::__construct($list);
        $this->xpath = Document::getXpath();
        $content     = array();
        $list->hasAttribute("list-type") ? $this->style = $list->getAttribute("list-type") : $this->style = "unordered";
        $this->type = self::listElementLevel($list);

        $listItemNodes = $this->xpath->query("list-item", $list);
        foreach ($listItemNodes as $listItemNode) {
            $listItem        = array(); // represents list item
            $insideListItems = $this->xpath->query("child::node()", $listItemNode);

            foreach ($insideListItems as $insideJatsListItem) {
                if ($insideJatsListItem->nodeName === "p") {
                    $par        = new Par($insideJatsListItem);
                    $listItem[] = $par;
                } elseif ($insideJatsListItem->nodeName === "list") {
                    $insideListing = new Listing($insideJatsListItem);
                    $listItem[]    = $insideListing;
                } else {
                    $listItemTexts = $this->xpath->query("self::text()|.//text()", $insideJatsListItem);
                    foreach ($listItemTexts as $listItemText) {
                        /**
                         * We must ensure that picking up Text
                         * Node from the current list level -> avoiding parsing nested lists
                         */
                        if (self::listElementLevel($listItemText) === $this->type) {
                            $jatsText   = new Text($listItemText);
                            $listItem[] = $jatsText;
                        }
                    }
                }
            }

            $content[] = $listItem;
        }

        $this->content = $content;
    }

    public function getContent(): array
    {
        return $this->content;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getStyle(): string
    {
        return $this->style;
    }

    /**
     * @param DOMNode $textNode
     * @return bool
     */

    private function listElementLevel(DOMNode $textNode): bool
    {
        return preg_match_all("/\blist\b[^-]|\blist\b$/", $textNode->getNodePath(), $mathes);
    }
}
