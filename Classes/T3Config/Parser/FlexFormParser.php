<?php

namespace AgentMedia\T3LocalCopy\T3Config\Parser;

final class FlexFormParser {
    private \DOMDocument $flexDom;
    public function __construct(\DOMDocument $flexDom) {
        $this->flexDom = $flexDom;
    }

    public static function fromXml(string $flexXml): FlexFormParser {
        $dom = new \DOMDocument();
        $dom->loadXML($flexXml);
        return new self($dom);
    }

    public function getUniqueDomValueNode(string $sheet, string $field): ?\DOMNode {
        $xpath = new \DOMXPath($this->flexDom);
        $query = sprintf('//sheet[@index="%s"]//field[@index="%s"]/value', $sheet, $field);
        $nodes = $xpath->query($query);
        return $nodes->length > 0 ? $nodes->item(0) : null;
    }

    public function getValue(string $sheet, string $field, ?string $default = null): ?string {
        $node = $this->getUniqueDomValueNode($sheet, $field);
        if ($node === null) {
            return $default;
        }
        return $node->nodeValue ?? $default;
    }
}