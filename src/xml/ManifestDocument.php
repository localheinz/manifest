<?php
/*
 * This file is part of PharIo\Manifest.
 *
 * (c) Arne Blankerts <arne@blankerts.de>, Sebastian Heuer <sebastian@phpeople.de>, Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PharIo\Manifest;

use DOMDocument;
use DOMElement;

class ManifestDocument {
    const XMLNS = 'https://phar.io/xml/manifest/1.0';

    /**
     * @var DOMDocument
     */
    private $dom;

    /**
     * ManifestDocument constructor.
     *
     * @param DOMDocument $dom
     */
    private function __construct(DOMDocument $dom) {
        $this->ensureCorrectDocumentType($dom);
        $this->dom = $dom;
    }

    public static function fromFile($filename) {
        // TODO: check file exists, readable
        return self::fromString(
            file_get_contents($filename)
        );
    }

    public static function fromString($xmlString) {
        $prev = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $dom = new DOMDocument();
        $dom->loadXML($xmlString);

        libxml_use_internal_errors($prev);

        if (libxml_get_last_error() !== false) {
            throw new ManifestDocumentLoadingException(libxml_get_errors());
        }

        return new self($dom);
    }

    public function getContainsElement() {
        return new ContainsElement(
            $this->fetchElementByName('contains')
        );
    }

    public function getCopyrightElement() {
        return new CopyrightElement(
            $this->fetchElementByName('copyright')
        );
    }

    public function getRequiresElement() {
        return new RequiresElement(
            $this->fetchElementByName('requires')
        );
    }

    public function hasBundlesElement() {
        return $this->dom->getElementsByTagNameNS(self::XMLNS, 'bundles')->length === 1;
    }

    public function getBundlesElement() {
        return new BundlesElement(
            $this->fetchElementByName('bundles')
        );
    }

    private function ensureCorrectDocumentType(DOMDocument $dom) {
        $root = $dom->documentElement;
        if ($root->localName !== 'phar' || $root->namespaceURI !== self::XMLNS) {
            throw new ManifestDocumentException('Not a phar.io manifest document');
        }
    }

    /**
     * @param $elementName
     *
     * @return DOMElement
     *
     * @throws ManifestDocucmentException
     */
    private function fetchElementByName($elementName) {
        $element = $this->dom->getElementsByTagNameNS(self::XMLNS, $elementName)->item(0);
        if (!$element instanceof DOMElement) {
            throw new ManifestDocucmentException(
                sprintf('Element %s missing', $elementName)
            );
        }

        return $element;
    }

}
