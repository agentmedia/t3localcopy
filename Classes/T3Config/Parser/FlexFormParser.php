<?php

namespace AgentMedia\T3LocalCopy\T3Config\Parser;

final class FlexFormParser extends TcaLikeParserAbstract{
    private \DOMDocument $flexDom;
    public function __construct(\DOMDocument $flexDom) {
        $this->flexDom = $flexDom;
    }

    public static function fromXml(string $flexXml): FlexFormParser {
        $dom = new \DOMDocument();
        $dom->loadXML($flexXml);
        return new self($dom);
    }

    public static function fromFile(string $flexConfigPath): FlexFormParser {
        if (!file_exists($flexConfigPath) || !is_readable($flexConfigPath)) {
            throw new \Exception("FlexForm configuration file not found or not readable: " . $flexConfigPath);
        }
        return self::fromXml(file_get_contents($flexConfigPath));
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


    public function getConfigFields(array $matchConfig): array {
        $result = [];
        $xpath = new \DOMXPath($this->flexDom);
        $allSheets = $xpath->query('/T3DataStructure/sheets/*');
        foreach ($allSheets as $sheetNode) {
            /**
             * @var \DOMElement $sheetNode
             */
            $sheetName = $sheetNode->nodeName;
            $fields = $xpath->query('.//el/*', $sheetNode);
            foreach ($fields as $fieldNode) {
                /**
                 * @var \DOMElement $fieldNode
                 */
                $fieldName = $fieldNode->nodeName;
                $configNode = $xpath->query('.//config', $fieldNode)->item(0);
                $fieldDef = [
                    'config' => $configNode ? $this->nodeToArray($configNode) : []
                ];
                if ($this->checkMatch($fieldDef, $matchConfig)) {
                    $result[$sheetName][$fieldName] = $fieldDef;
                }
            }
        }
        return $result;
    }

   private function nodeToArray(\DOMNode $node): array|string
    {
        $children = [];

        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $children[] = $child;
            }
        }
        // Leaf node: return its text value
        if (count($children) === 0) {
            return trim($node->textContent);
        }

        $result = [];

        foreach ($children as $child) {
            $name  = $child->nodeName;
            $value = $this->nodeToArray($child);

            // Preserve repeated element names as arrays
            if (array_key_exists($name, $result)) {
                if (!is_array($result[$name]) ||
                    !array_is_list($result[$name])) {
                    $result[$name] = [$result[$name]];
                }
                $result[$name][] = $value;
            } else {
                $result[$name] = $value;
            }
        }
        return $result;
    }


}