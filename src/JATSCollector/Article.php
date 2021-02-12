<?php


namespace JATSCollector;


use DOMDocument;
use DOMElement;
use DOMNode;
use JATSParser\Body\Document as JATSDocument;
use JATSParser\HTML\Document as HTMLDocument;

class Article
{
    /**
     * @var string
     */
    protected $html = '';
    /**
     * @var JATSDocument $jatsDocument
     */
    private $jatsDocument;
    /**
     * @var JATSDocument $jatsDocument
     */
    private $htmlDocument;
    /**
     * @var DOMElement
     */
    private $xml;
    /**
     * @var DOMDocument
     */
    private $dom;
    /**
     * @var DOMElement
     */
    private $meta;

    public function __construct($filepath, $dom)
    {
//        $this->dom = new DOMDocument('1.0', 'UTF-8');
        $this->dom = $dom;
        $this->xml = $this->dom->createElement('article');
        $this->dom->appendChild($this->xml);
        $this->meta = $this->dom->createElement('original-meta');
        $this->xml->appendChild($this->meta);

        $this->xml->appendChild(
            $this->dom->createElement('original_xml', basename($filepath))
        );
        $files         = glob(dirname($filepath) . '/MediaObjects/*.jpg');
        $featuredImage = false;
        if (count($files) > 0) {
            $featuredImage = basename($files[0]);
        }
        $this->xml->appendChild(
            $this->dom->createElement('featured_image', $featuredImage)
        );

        $this->jatsDocument = new JATSDocument($filepath);
        $this->htmlDocument = new HTMLDocument($this->jatsDocument);
        $this->htmlDocument->setReferences('apa', 'en-US', true);
        $this->parseJournalMeta();
        $this->parseArticleMeta();
        $this->parseArticleContent();
    }

    protected function parseJournalMeta()
    {
        $xpathArticleMeta = '/article/front/article-meta';
        $xpath            = $this->jatsDocument::getXpath();

        $this->xml->appendChild(
            $this->dom->createElement(
                'title',
                $this->maybeGetXpathValue(
                    $xpathArticleMeta . '/title-group/article-title/text()',
                    ''
                )
            )
        );

        $this->xml->appendChild(
            $this->dom->createElement(
                'subtitle',
                $this->maybeGetXpathValue(
                    $xpathArticleMeta . '/title-group/subtitle/text()',
                    ''
                )
            )
        );

        $this->xml->appendChild(
            $this->dom->createElement(
                'publication_date',
                date(
                    'Y-m-d',
                    strtotime(
                        $this->maybeGetXpathValue(
                            $xpathArticleMeta . '/pub-date[@publication-format="electronic"]/year/text()',
                            ''
                        ) . '-' .
                        $this->maybeGetXpathValue(
                            $xpathArticleMeta . '/pub-date[@publication-format="electronic"]/month/text()',
                            ''
                        ) . '-' .
                        $this->maybeGetXpathValue(
                            $xpathArticleMeta . '/pub-date[@publication-format="electronic"]/day/text()',
                            ''
                        )
                    )
                )
            )
        );


        $this->xml->appendChild(
            $this->dom->createElement(
                'volume',
                $this->maybeGetXpathValue(
                    $xpathArticleMeta . '/volume/text()',
                    ''
                )
            )
        );
        $this->xml->appendChild(
            $this->dom->createElement(
                'issue',
                $this->maybeGetXpathValue(
                    $xpathArticleMeta . '/issue/text()',
                    ''
                )
            )
        );

        $authorName = trim(
            $this->maybeGetXpathValue(
                $xpathArticleMeta . '/contrib-group/contrib[@contrib-type="author"]/name/given-names/text()',
                ''
            ) . ' ' .
            $this->maybeGetXpathValue(
                $xpathArticleMeta . '/contrib-group/contrib[@contrib-type="author"]/name/surname/text()',
                ''
            )
        );
//        echo $authorName . "\n";
        $this->xml->appendChild(
            $this->dom->createElement('author', $authorName)
        );
        $this->xml->appendChild(
            $this->dom->createElement(
                'copyright',
                $this->maybeGetXpathValue(
                    $xpathArticleMeta . '/permissions/copyright-statement/text()',
                    ''
                )
            )
        );
        $this->xml->appendChild(
            $this->dom->createElement(
                'copyright_year',
                $this->maybeGetXpathValue(
                    $xpathArticleMeta . '/permissions/copyright-year/text()',
                    ''
                )
            )
        );

        $abstractElements = $xpath->query($xpathArticleMeta . '/abstract//*[normalize-space(text())]');
        $abstractText     = '';
        foreach ($abstractElements as $abstractElement) {
            /**
             * @var DOMNode $abstractElement
             */
            if ($abstractElement->nodeName === 'title') {
                continue;
            }
            $abstractText .= $abstractElement->nodeValue;
        }
        $this->xml->appendChild(
            $this->dom->createElement(
                'abstract',
                $abstractText
            )
        );

        $this->xml->appendChild(
            $this->dom->createElement(
                'category',
                $this->maybeGetXpathValue(
                    $xpathArticleMeta . '/article-categories/subj-group/subject/text()',
                    ''
                )
            )
        );


        $articleMeta = $xpath->query('/article/front/article-meta');
        foreach ($articleMeta as $item) {
            $this->meta->appendChild(
                $this->dom->importNode($item, true)
            );
        }
        $journalMeta = $xpath->query('/article/front/journal-meta');
        foreach ($journalMeta as $item) {
            $this->meta->appendChild(
                $this->dom->importNode($item, true)
            );
        }

        $refNodes       = $xpath->query('/article/back/ref-list/ref-list/ref');
        $newRefListNode = $this->dom->createElement('ref-list');
        foreach ($refNodes as $refNode) {
            /** @var DOMElement $refNode */
            $cdataString = '';
            foreach ($xpath->evaluate('mixed-citation/node()', $refNode) as $item) {
                if (!is_a($item, DOMNode::class)) {
                    continue;
                }
                /** @var DOMElement $item */
                if ($item->nodeName === 'ext-link') {
                    $cdataString .= '<a href="' . $item->getAttribute('xlink:href') . '" target="_blank">';
                    $cdataString .= trim($item->nodeValue);
                    $cdataString .= '</a>';
                    continue;
                }
                $cdataString .= $item->nodeValue;
            }
            if ($cdataString) {
                $newRefNode = $this->dom->createElement('ref');
                $newRefNode->setAttribute('id', $refNode->getAttribute('id'));
                $labelNodes = $xpath->query('label', $refNode);
                if ($labelNodes->length > 0) {
                    $newRefNode->appendChild(
                        $this->dom->createElement('label', $labelNodes->item(0)->nodeValue)
                    );
                }
                $citationCdata   = $this->dom->createCDATASection($cdataString);
                $citationElement = $this->dom->createElement('mixed-citation');
                $citationElement->appendChild($citationCdata);
                $newRefNode->appendChild($citationElement);
                $newRefListNode->appendChild($newRefNode);
            }
        }
        $this->xml->appendChild($newRefListNode);
    }

    /**
     * @return DOMElement
     */
    public function getXmlElement()
    {
        return $this->xml;
    }

    private function maybeGetXpathValue($expression, $default = false)
    {
        $return = $default;
        $nodes  = $this->jatsDocument::getXpath()->query($expression);
        foreach ($nodes as $node) {
            /**
             * @var DOMNode $node
             */
            $return = $node->nodeValue;
        }
        return htmlspecialchars($return);
    }

    private function parseArticleMeta()
    {
    }

    private function parseArticleContent()
    {
        $html = $this->htmlDocument->saveHTML();
        $elem = $this->dom->createElement('content_raw');
        $elem->appendChild($this->dom->createCDATASection($html));
        $this->xml->appendChild($elem);
    }
}
