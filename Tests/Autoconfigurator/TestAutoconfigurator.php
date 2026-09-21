<?php

use AgentMedia\T3LocalCopy\T3Config\Parser\FlexFormParser;
use AgentMedia\T3LocalCopy\T3Config\Parser\TcaParser;
use AgentMedia\T3LocalCopy\T3Config\Utility\AutoConfigurator;
use AgentMedia\T3LocalCopy\TableConfigurations\Condition\DirectRecordCondition;
use AgentMedia\T3LocalCopy\TableConfigurations\TableConfig;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../TestDumper.php';
$flexParser =  FlexFormParser::fromFile(__DIR__ . '/Input/flexForm.xml');
$dumper = new TestDumper(__DIR__ . '/Output');
$dumper->dump($flexParser->getConfigFalReferenceFields(), 'falReferenceFieldsFromFlexForm.json');
$dumper->dump($flexParser->getConfigGroupDbFields(), 'groupDbFieldsFromFlexForm.json');

$tceFormsFlexParser = FlexFormParser::fromFile(__DIR__ . '/Input/flexForm.TCEforms.xml');
$dumper->dump($tceFormsFlexParser->getConfigFalReferenceFields(), 'falReferenceFieldsFromTceFormsFlexForm.json');
$dumper->dump($tceFormsFlexParser->getConfigGroupDbFields(), 'groupDbFieldsFromTceFormsFlexForm.json');
$dumper->dump($tceFormsFlexParser->getConfigSelectTableFields(), 'selectTableFieldsFromTceFormsFlexForm.json');

$tcaParser = TcaParser::fromPhpFile(__DIR__ . '/Input/my_model.php');
$dumper->dump($tcaParser->getConfigFalReferenceFields(), 'falReferenceFieldsFromTca.php.json');
$dumper->dump($tcaParser->getConfigGroupDbFields(), 'groupDbFieldsFromTca.php.json');

$autoConfigurator = new AutoConfigurator();

$tableConfig = new TableConfig(tableName: 'tt_content', l10nParentColumn: 'l18n_parent', pidColumn: 'pid', includeParents: true);
AutoConfigurator::addFlexConfigFalRelations($tableConfig, $flexParser, new DirectRecordCondition(['CType' => 'my_custom_type']));
AutoConfigurator::addFlexConfigGroupDbFieldsRelations($tableConfig, $flexParser, new DirectRecordCondition(['CType' => 'my_custom_type']));
AutoConfigurator::addFlexConfigSelectTableRelations($tableConfig, $flexParser, new DirectRecordCondition(['CType' => 'my_custom_type']));

AutoConfigurator::addTcaConfigFalRelations($tableConfig, $tcaParser, new DirectRecordCondition(['CType' => 'my_custom_type']));
AutoConfigurator::addTcaConfigGroupDbFieldsRelations($tableConfig, $tcaParser, new DirectRecordCondition(['CType' => 'my_custom_type']));
AutoConfigurator::addTcaConfigSelectTableRelations($tableConfig, $tcaParser, new DirectRecordCondition(['CType' => 'my_custom_type']));
$dumper->dump(['flexForeignTableRelations' => $tableConfig->getFlexFieldForeignTableRelations(),
    'flexMultipleForeignTableRelations' => $tableConfig->getFlexFieldMultipleForeignTableRelations(),
    'tcaColumnForeignTableRelations' => $tableConfig->getUidsForeignTableRelations(),
    'tcaColumnMultipleForeignTableRelations' => $tableConfig->getUidsMultipleForeignTableRelations(),
    'foreignChildRelations' => $tableConfig->getForeignChildRelations()
], 'tableConfig.json');