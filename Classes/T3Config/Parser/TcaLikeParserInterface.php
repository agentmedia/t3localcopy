<?php
namespace AgentMedia\T3LocalCopy\T3Config\Parser;

interface TcaLikeParserInterface {

/**
 * Get configuration fields matching the specified criteria.
 *
 * @param array $matchConfig The configuration criteria to match. You can use '*' as a wildcard for any value.
 * @return array The matching configuration fields.
 */
public function getConfigFields(array $matchConfig): array;
}