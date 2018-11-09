<#1>
<?php
/**
 * ErrorTextQuestion OBJECT
 *
 * @author Mohammed Helwani <mohammed.helwani@llz.uni-halle.de>
 * @version $Id: $
 */

$res = $ilDB->queryF("SELECT * FROM qpl_qst_type WHERE type_tag = %s", array('text'), array('assErrorTextQuestion')
);

if ($res->numRows() == 0)
{
    $res = $ilDB->query("SELECT MAX(question_type_id) maxid FROM qpl_qst_type");
    $data = $ilDB->fetchAssoc($res);
    $max = $data["maxid"] + 1;

    $affectedRows = $ilDB->manipulateF(
		"INSERT INTO qpl_qst_type (question_type_id, type_tag, plugin) VALUES (%s, %s, %s)",
		array("integer", "text", "integer"),
		array($max, 'assErrorTextQuestion', 1)
    );
}
?>
<#2>
<?php
if (!$ilDB->tableExists("il_qpl_qst_errortextq")) {
    $fields = array(
        "question_fi" => array(
            "type" => "integer", "length" => 4, "notnull" => true
        ),
        "errortext" => array(
            "type" => "text", "length" => 4000, "notnull" => true
        ),
        "textsize" => array(
            "type" => "float", "notnull" => true, "default" => 100
        ),
        "points_wrong" => array(
            "type" => "float", "notnull" => true, "default" => -1.0
        ),
        "text_direction" => array(
            "type" => "text", "length" => 10, "notnull" => true
        ),
        "error_type" => array(
            "type" => "text", "length" => 1, "notnull" => true
        )
    );

    $ilDB->createTable("il_qpl_qst_errortextq", $fields);
    $ilDB->addPrimaryKey("il_qpl_qst_errortextq", array("question_fi"));
}
?>
<#3>
<?php
if (!$ilDB->tableExists("il_qpl_a_errortextq")) {
    $fields = array(
        "answer_id" => array(
            "type" => "integer", "length" => 4, "notnull" => true
        ),
        "question_fi" => array(
            "type" => "integer", "length" => 4, "notnull" => true
        ),
        "text_wrong" => array(
            "type" => "text", "length" => 150, "notnull" => true
        ),
        "text_correct" => array(
            "type" => "text", "length" => 150, "notnull" => false, "default" => null
        ),
        "points" => array(
            "type" => "float", "notnull" => true, "default" => 0
        ),
        "sequence" => array(
            "type" => "integer", "length" => 2, "notnull" => true, "default" => 0
        ),
        "positions" => array(
            "type" => "text", "length" => 256, "notnull" => true, "default" => 0
        ),
        "error_type" => array(
            "type" => "text", "length" => 1, "notnull" => true
        )
    );
    $ilDB->createTable("il_qpl_a_errortextq", $fields);
    $ilDB->addPrimaryKey("il_qpl_a_errortextq", array("answer_id"));
    $ilDB->addIndex('il_qpl_a_errortextq',array('question_fi'),'i1');
    $ilDB->createSequence("il_qpl_a_errortextq");
}
?>