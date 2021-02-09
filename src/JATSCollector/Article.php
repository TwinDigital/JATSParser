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

        $refListNodes = $xpath->query('/article/back/ref-list/ref-list');
        foreach ($refListNodes as $refListNode) {
            $this->xml->appendChild(
                $this->dom->importNode($refListNode, true)
            );
        }
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
