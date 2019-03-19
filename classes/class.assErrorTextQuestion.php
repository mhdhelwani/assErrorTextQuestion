<?php

require_once './Modules/TestQuestionPool/classes/class.assQuestion.php';
require_once './Modules/Test/classes/inc.AssessmentConstants.php';
require_once './Modules/TestQuestionPool/interfaces/interface.ilObjQuestionScoringAdjustable.php';
require_once './Modules/TestQuestionPool/interfaces/interface.ilObjAnswerScoringAdjustable.php';
require_once './Modules/TestQuestionPool/interfaces/interface.iQuestionCondition.php';
require_once './Modules/TestQuestionPool/classes/class.ilUserQuestionResult.php';

/**
 * ErrorTextQuestion OBJECT
 *
 * @author Mohammed Helwani <mohammed.helwani@llz.uni-halle.de>
 * @version $Id: $
 * @ingroup    ModulesTestQuestionPool
 *
 */
class assErrorTextQuestion extends assQuestion implements ilObjQuestionScoringAdjustable, ilObjAnswerScoringAdjustable, iQuestionCondition
{
    /**
     * @var ilassErrorTextQuestionPlugin    The plugin object
     */
    var $plugin = null;

    protected $errortext;
    protected $textsize;
    protected $errordata;
    protected $points_wrong;
    protected $text_direction;
    protected $error_type;

    /**
     * assErrorTextQuestion constructor
     *
     * The constructor takes possible arguments an creates an instance of the assErrorTextQuestion object.
     *
     * @param string $title A title string to describe the question
     * @param string $comment A comment string to describe the question
     * @param string $author A string containing the name of the questions author
     * @param integer $owner A numerical ID to identify the owner/creator
     * @param string $question The question string of the single choice question
     *
     * @access public
     * @see assQuestion:assQuestion()
     */
    function __construct($title = "", $comment = "", $author = "", $owner = -1, $question = "")
    {
        $this->getPlugin()->loadLanguageModule();

        parent::__construct($title, $comment, $author, $owner, $question);
        $this->errortext = '';
        $this->textsize = 100.0;
        $this->text_direction = "LTR";
        $this->error_type = "W";
        $this->errordata = array();
    }

    /**
     * Get the plugin object
     *
     * @return object The plugin object
     */
    public function getPlugin()
    {
        if ($this->plugin == null) {
            include_once "./Services/Component/classes/class.ilPlugin.php";
            $this->plugin = ilPlugin::getPluginObject(IL_COMP_MODULE, "TestQuestionPool", "qst", "assErrorTextQuestion");

        }
        return $this->plugin;
    }

    /**
     * Returns true, if the question is complete
     *
     * @return boolean True, if the question is complete for use, otherwise false
     */
    public function isComplete()
    {
        if (strlen($this->title) && ($this->author) && ($this->question) && ($this->getMaximumPoints() > 0)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Saves a question object to a database
     *
     * @param    string $original_id
     * @access    public
     * @see assQuestion::saveToDb()
     */
    function saveToDb($original_id = "")
    {
        $this->saveQuestionDataToDb($original_id);
        $this->saveAdditionalQuestionDataToDb();
        $this->saveAnswerSpecificDataToDb();
        parent::saveToDb();
    }

    public function saveAnswerSpecificDataToDb()
    {
        global $ilDB;
        $ilDB->manipulateF("DELETE FROM il_qpl_a_errortextq WHERE question_fi = %s",
            array('integer'),
            array($this->getId())
        );

        $sequence = 0;
        foreach ($this->errordata as $object) {
            $next_id = $ilDB->nextId('il_qpl_a_errortextq');
            $ilDB->manipulateF(
                "INSERT INTO il_qpl_a_errortextq (answer_id, question_fi, text_wrong, text_correct, points, sequence, positions, error_type) 
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s)",
                array('integer', 'integer', 'text', 'text', 'float', 'integer', 'text', 'text'),
                array(
                    $next_id,
                    $this->getId(),
                    $object->text_wrong,
                    $object->text_correct,
                    $object->points,
                    $sequence++,
                    $object->positions,
                    $object->error_type
                )
            );
        }
    }

    /**
     * Saves the data for the additional data table.
     *
     * This method uses the ugly DELETE-INSERT. Here, this does no harm.
     */
    public function saveAdditionalQuestionDataToDb()
    {
        global $ilDB;
        // save additional data
        $ilDB->manipulateF("DELETE FROM " . $this->getAdditionalTableName() . " WHERE question_fi = %s",
            array("integer"),
            array($this->getId())
        );

        $ilDB->manipulateF("INSERT INTO " . $this->getAdditionalTableName() . " (question_fi, errortext, textsize, points_wrong, text_direction, error_type) VALUES (%s, %s, %s, %s, %s, %s)",
            array("integer", "text", "float", "float", "text", "text"),
            array(
                $this->getId(),
                $this->getErrorText(),
                $this->getTextSize(),
                $this->getPointsWrong(),
                $this->getTextDirection(),
                $this->getErrorType()
            )
        );
    }

    /**
     * Loads a question object from a database
     *
     * @param integer $question_id A unique key which defines the question in the database
     * @see assQuestion::loadFromDb()
     */
    public function loadFromDb($question_id)
    {
        global $ilDB;

        $result = $ilDB->queryF("SELECT qpl_questions.*, " . $this->getAdditionalTableName() . ".* FROM qpl_questions LEFT JOIN " .
            $this->getAdditionalTableName() . " ON " . $this->getAdditionalTableName() .
            ".question_fi = qpl_questions.question_id WHERE qpl_questions.question_id = %s",
            array("integer"),
            array($question_id)
        );
        if ($result->numRows() == 1) {
            $data = $ilDB->fetchAssoc($result);
            $this->setId($question_id);
            $this->setObjId($data["obj_fi"]);
            $this->setTitle($data["title"]);
            $this->setComment($data["description"]);
            $this->setOriginalId($data["original_id"]);
            $this->setNrOfTries($data['nr_of_tries']);
            $this->setAuthor($data["author"]);
            $this->setPoints($data["points"]);
            $this->setOwner($data["owner"]);
            include_once("./Services/RTE/classes/class.ilRTE.php");
            $this->setQuestion(ilRTE::_replaceMediaObjectImageSrc($data["question_text"], 1));
            $this->setErrorText($data["errortext"]);
            $this->setTextSize($data["textsize"]);
            $this->setPointsWrong($data["points_wrong"]);
            $this->setEstimatedWorkingTime(substr($data["working_time"], 0, 2), substr($data["working_time"], 3, 2), substr($data["working_time"], 6, 2));
            $this->setTextDirection($data["text_direction"]);
            $this->setErrorType($data["error_type"]);

            try {
                $this->setAdditionalContentEditingMode($data['add_cont_edit_mode']);
            } catch (ilTestQuestionPoolException $e) {
            }
        }

        $result = $ilDB->queryF("SELECT * FROM il_qpl_a_errortextq WHERE question_fi = %s ORDER BY sequence ASC",
            array('integer'),
            array($question_id)
        );
        include_once "class.assAnswerErrorTextQuestion.php";
        if ($result->numRows() > 0) {
            while ($data = $ilDB->fetchAssoc($result)) {
                array_push($this->errordata, new assAnswerErrorTextQuestion(
                    $data["text_wrong"],
                    $data["text_correct"],
                    $data["points"],
                    $data["positions"],
                    $data["error_type"]));
            }
        }

        parent::loadFromDb($question_id);
    }

    /**
     * Duplicates a question
     * This is used for copying a question to a test
     *
     * @param bool $for_test
     * @param string $title
     * @param string $author
     * @param string $owner
     * @param null $testObjId
     *
     * @access public
     * @return int
     */
    function duplicate($for_test = true, $title = "", $author = "", $owner = "", $testObjId = null)
    {
        if ($this->id <= 0) {
            // The question has not been saved. It cannot be duplicated
            return;
        }
        // duplicate the question in database
        $this_id = $this->getId();
        $thisObjId = $this->getObjId();

        $clone = $this;
        include_once("./Modules/TestQuestionPool/classes/class.assQuestion.php");
        $original_id = assQuestion::_getOriginalId($this->id);
        $clone->id = -1;

        if ((int)$testObjId > 0) {
            $clone->setObjId($testObjId);
        }

        if ($title) {
            $clone->setTitle($title);
        }

        if ($author) {
            $clone->setAuthor($author);
        }
        if ($owner) {
            $clone->setOwner($owner);
        }

        if ($for_test) {
            $clone->saveToDb($original_id);
        } else {
            $clone->saveToDb();
        }
        // copy question page content
        $clone->copyPageOfQuestion($this_id);
        // copy XHTML media objects
        $clone->copyXHTMLMediaObjectsOfQuestion($this_id);

        $clone->onDuplicate($thisObjId, $this_id, $clone->getObjId(), $clone->getId());
        return $clone->id;
    }

    /**
     * Copies a question
     * This is used when a question is copied on a question pool
     *
     * @param $target_questionpool_id
     * @param string $title
     *
     * @access public
     * @return int|void
     */
    function copyObject($target_questionpool_id, $title = "")
    {
        if ($this->id <= 0) {
            // The question has not been saved. It cannot be duplicated
            return;
        }
        // duplicate the question in database

        $thisId = $this->getId();
        $thisObjId = $this->getObjId();

        $clone = $this;
        include_once("./Modules/TestQuestionPool/classes/class.assQuestion.php");
        $original_id = assQuestion::_getOriginalId($this->id);
        $clone->id = -1;
        $clone->setObjId($target_questionpool_id);
        if ($title) {
            $clone->setTitle($title);
        }
        $clone->saveToDb();

        // copy question page content
        $clone->copyPageOfQuestion($original_id);
        // copy XHTML media objects
        $clone->copyXHTMLMediaObjectsOfQuestion($original_id);

        $clone->onCopy($thisObjId, $thisId, $clone->getObjId(), $clone->getId());

        return $clone->id;
    }

    public function createNewOriginalFromThisDuplicate($targetParentId, $targetQuestionTitle = "")
    {
        if ($this->id <= 0) {
            // The question has not been saved. It cannot be duplicated
            return;
        }

        include_once("./Modules/TestQuestionPool/classes/class.assQuestion.php");

        $sourceQuestionId = $this->id;
        $sourceParentId = $this->getObjId();

        // duplicate the question in database
        $clone = $this;
        $clone->id = -1;

        $clone->setObjId($targetParentId);

        if ($targetQuestionTitle) {
            $clone->setTitle($targetQuestionTitle);
        }

        $clone->saveToDb();
        // copy question page content
        $clone->copyPageOfQuestion($sourceQuestionId);
        // copy XHTML media objects
        $clone->copyXHTMLMediaObjectsOfQuestion($sourceQuestionId);

        $clone->onCopy($sourceParentId, $sourceQuestionId, $clone->getObjId(), $clone->getId());

        return $clone->id;
    }

    /**
     * Returns the maximum points, a learner can reach answering the question
     *
     * @see $points
     */
    public function getMaximumPoints()
    {
        $maxpoints = 0.0;
        foreach ($this->errordata as $object) {
            if ($object->points > 0) $maxpoints += $object->points;
        }
        return $maxpoints;
    }

    /**
     * Returns the points, a learner has reached answering the question.
     * The points are calculated from the given answers.
     *
     * @access public
     * @param integer $active_id
     * @param integer $pass
     * @param boolean $authorizedSolution
     * @param boolean $returndetails (deprecated !!)
     * @throws ilTestException
     * @return integer/array $points/$details (array $details is deprecated !!)
     */
    public function calculateReachedPoints($active_id, $pass = NULL, $authorizedSolution = true, $returndetails = FALSE)
    {
        if ($returndetails) {
            throw new ilTestException('return details not implemented for ' . __METHOD__);
        }

        global $ilDB;

        /* First get the positions which were selected by the user. */
        $answers = array();
        if (is_null($pass)) {
            $pass = $this->getSolutionMaxPass($active_id);
        }
        $result = $this->getCurrentSolutionResultSet($active_id, $pass, $authorizedSolution);

        while ($row = $ilDB->fetchAssoc($result)) {
            $answers[$row["value1"]] = $row["value1"];
        }
        $points = $this->getPointsForSelectedPositions($answers);
        return $points;
    }

    public function calculateReachedPointsFromPreviewSession(ilAssQuestionPreviewSession $previewSession)
    {
        $selections = $previewSession->getParticipantsSolution();
        return $this->getPointsForSelectedPositions($selections);
    }

    /**
     * Saves the learners input of the question to the database.
     *
     * @access public
     * @param integer $active_id Active id of the user
     * @param integer $pass Test pass
     * @param boolean $authorized
     * @return boolean $status
     */
    public function saveWorkingData($active_id, $pass = NULL, $authorized = true)
    {
        if (is_null($pass)) {
            include_once "./Modules/Test/classes/class.ilObjTest.php";
            $pass = ilObjTest::_getPass($active_id);
        }

        $this->getProcessLocker()->executeUserSolutionUpdateLockOperation(function() use (&$entered_values, $active_id, $pass, $authorized) {

            $this->removeCurrentSolution($active_id, $pass, $authorized);

            if(strlen($_POST["qst_" . $this->getId()]))
            {
                $selected = explode(",", $_POST["qst_" . $this->getId()]);
                foreach ($selected as $position)
                {
                    $this->saveCurrentSolution($active_id, $pass, $position, null, $authorized);
                }
                $entered_values = true;
            }

        });

        if ($entered_values) {
            include_once("./Modules/Test/classes/class.ilObjAssessmentFolder.php");
            if (ilObjAssessmentFolder::_enabledAssessmentLogging()) {
                $this->logAction($this->lng->txtlng("assessment", "log_user_entered_values", ilObjAssessmentFolder::_getLogLanguage()), $active_id, $this->getId());
            }
        } else {
            include_once("./Modules/Test/classes/class.ilObjAssessmentFolder.php");
            if (ilObjAssessmentFolder::_enabledAssessmentLogging()) {
                $this->logAction($this->lng->txtlng("assessment", "log_user_not_entered_values", ilObjAssessmentFolder::_getLogLanguage()), $active_id, $this->getId());
            }
        }

        return true;
    }

    public function savePreviewData(ilAssQuestionPreviewSession $previewSession)
    {
        if (strlen($_POST["qst_" . $this->getId()])) {
            $postSelections = explode(",", $_POST["qst_" . $this->getId()]);
            $selections = array();
            foreach ($postSelections as $postSelection) {
                $selections[$postSelection] = $postSelection;
            }
        } else {
            $selections = array();
        }

        $previewSession->setParticipantsSolution($selections);
    }

    /**
     * Reworks the allready saved working data if neccessary
     * @param integer $active_id
     * @param integer $pass
     * @param boolean $obligationsAnswered
     * @param boolean $authorized
     */
    protected function reworkWorkingData($active_id, $pass, $obligationsAnswered, $authorized)
    {
        // nothing to rework!
    }

    /**
     * Returns the name of the answer table in the database
     *
     * @return string The answer table name
     */
    public function getAnswerTableName()
    {
        return "il_qpl_a_errortextq";
    }

    /**
     * Collects all text in the question which could contain media objects
     * which were created with the Rich Text Editor
     */
    public function getRTETextWithMediaObjects()
    {
        $text = parent::getRTETextWithMediaObjects();
        return $text;
    }

    /**
     * Synchronize a question with its original
     *
     * @access public
     */
    function syncWithOriginal()
    {
        parent::syncWithOriginal();
    }

    /**
     * Returns the question type of the question
     *
     * @return string The question type of the question
     */
    public function getQuestionType()
    {
        return "assErrorTextQuestion";
    }

    /**
     * Returns the names of the additional question data tables
     *
     * all tables must have a 'question_fi' column
     * data from these tables will be deleted if a question is deleted
     *
     * @return mixed    the name(s) of the additional tables (array or string)
     */
    public function getAdditionalTableName()
    {
        return "il_qpl_qst_errortextq";
    }

    /**
     * Creates an Excel worksheet for the detailed cumulated results of this question
     *
     * @param object $worksheet    Reference to the parent excel worksheet
     * @param object $startrow     Startrow of the output in the excel worksheet
     * @param object $active_id    Active id of the participant
     * @param object $pass         Test pass
     *
     * @return object
     */
    public function setExportDetailsXLS($worksheet, $startrow, $active_id, $pass)
    {
        parent::setExportDetailsXLS($worksheet, $startrow, $active_id, $pass);

        $i = 0;
        $selections = array();
        $solutions =& $this->getSolutionValues($active_id, $pass);
        if (is_array($solutions)) {
            foreach ($solutions as $solution) {
                $selections[$solution["value1"]] = $solution["value1"];
            }
        }
        krsort($selections);

        $errortext = $this->createErrorTextExport($selections);
        $i++;
        $worksheet->setCell($startrow + $i, 0, $errortext);
        $i++;
        return $startrow + $i + 1;
    }


    /**
     * Creates a question from a QTI file
     *
     * Receives parameters from a QTI parser and creates a valid ILIAS question object
     *
     * @param object $item The QTI item object
     * @param integer $questionpool_id The id of the parent questionpool
     * @param integer $tst_id The id of the parent test if the question is part of a test
     * @param object $tst_object A reference to the parent test object
     * @param integer $question_counter A reference to a question counter to count the questions of an imported question pool
     * @param array $import_mapping An array containing references to included ILIAS objects
     */
    public function fromXML(&$item, &$questionpool_id, &$tst_id, &$tst_object, &$question_counter, &$import_mapping)
    {
        include_once "import/qti12/class.assErrorTextQuestionImport.php";
        $import = new assErrorTextQuestionImport($this);
        $import->fromXML($item, $questionpool_id, $tst_id, $tst_object, $question_counter, $import_mapping);
    }

    /**
     * Returns a QTI xml representation of the question and sets the internal
     * domxml variable with the DOM XML representation of the QTI xml representation
     *
     * @return string The QTI xml representation of the question
     */
    public function toXML($a_include_header = true, $a_include_binary = true, $a_shuffle = false, $test_output = false, $force_image_references = false)
    {
        include_once "export/qti12/class.assErrorTextQuestionExport.php";
        $export = new assErrorTextQuestionExport($this);
        return $export->toXML($a_include_header, $a_include_binary, $a_shuffle, $test_output, $force_image_references);
    }

    /**
     * Returns the best solution for a given pass of a participant
     *
     * @return array An associated array containing the best solution
     */
    public function getBestSolution($active_id, $pass)
    {
        $user_solution = array();
        return $user_solution;
    }

    public function getErrorsFromText($a_text = "")
    {
        if (strlen($a_text) == 0)
            $a_text = $this->getErrorText();

        include_once "./Services/Utilities/classes/class.ilStr.php";

        $r_passage = "/(#|\\(\\(|\\)\\))/";

        $errorTextSplitArray = preg_split($r_passage, $a_text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $errorsFound = array();
        $counter = 0;
        $text = "";


        for ($i = 0; $i < count($errorTextSplitArray); $i++) {
            if (in_array($errorTextSplitArray[$i], ["#", "((", "))"])) {
                if (ilStr::substr($errorTextSplitArray[$i - 1], ilStr::strLen($errorTextSplitArray[$i - 1]) - 1, 1) == "\\") {
                    $text .= ilStr::substr($errorTextSplitArray[$i - 1], 0, ilStr::strLen($errorTextSplitArray[$i - 1]) - 1) . $errorTextSplitArray[$i];
                } else {
                    $text .= $errorTextSplitArray[$i - 1];
                    if ($errorTextSplitArray[$i][0] == "#") {
                        if ($this->getErrorType() == "W") {
                            $errorWord = preg_split("/[\s]/", $errorTextSplitArray[$i + 1])[0];
                        } else {
                            $errorWord = ilStr::substr($errorTextSplitArray[$i + 1], 0, 1);

                        }
                        if ($errorWord == "\r\n") {
                            $errorWord = str_replace("\r\n", "  ", $errorWord);
                        }
                        $errorsFound[$counter]["errorText"] = $errorWord;
                        $errorsFound[$counter]["positions"] = ilStr::strLen($text);
                        $errorsFound[$counter]["error_type"] = "S";
                        $counter++;
                    } else if ($errorTextSplitArray[$i] == "((") {
                        $positions = [];
                        $errorText = str_replace("\r\n", "  ", $errorTextSplitArray[$i + 1]);
                        $errorsFound[$counter]["errorText"] = $errorText;
                        if ($this->getErrorType() == "W") {
                            while (true) {
                                if (function_exists("mb_strrpos")) {
                                    $spacePos = mb_strrpos($errorText, " ");
                                } else {
                                    $spacePos = strrpos($errorText, " ");
                                }
                                $pos = $spacePos;
                                if ($spacePos) {
                                    $pos = $spacePos + 1;
                                }

                                $positions[ilStr::strLen($text) + $pos] = ilStr::strLen($text) + $pos;
                                $errorText = trim(ilStr::subStr($errorText, 0, $spacePos));
                                if (!$errorText) {
                                    break;
                                }
                            }
                        } else {
                            while (true) {
                                if (ilStr::subStr($errorText, ilStr::strLen($errorText) - 1, 1) == "\n") {
                                    $errorText = ilStr::subStr($errorText, 0, ilStr::strLen($errorText) - 2);
                                } else {
                                    $errorText = ilStr::subStr($errorText, 0, ilStr::strLen($errorText) - 1);
                                }
                                if (!$errorText) {
                                    break;
                                }
                                $positions[ilStr::strLen($text) + ilStr::strLen($errorText)] = ilStr::strLen($text) + ilStr::strLen($errorText);
                            }
                            $positions[ilStr::strLen($text)] = ilStr::strLen($text);
                        }
                        ksort($positions);
                        $errorsFound[$counter]["positions"] = implode(",", $positions);
                        $errorsFound[$counter]["error_type"] = "M";
                        $counter++;
                    }
                }
            }
        }
        $text .= $errorTextSplitArray[count($errorTextSplitArray) - 1];

        return [$errorsFound, $text];
    }

    public function setErrorData($a_data)
    {
        include_once "class.assAnswerErrorTextQuestion.php";
        $temp = $this->errordata;
        $this->errordata = array();

        foreach ($a_data as $idx => $error) {
            $text_correct = "";
            $points = 0.0;
            foreach ($temp as $object) {
                if (strcmp(str_replace("\r\n", "", $object->text_wrong), str_replace("\r\n", "", $error["errorText"])) == 0 &&
                    $error["positions"] == $object->positions
                ) {
                    $text_correct = $object->text_correct;
                    $points = $object->points;
                    continue;
                }
            }
            $this->errordata[$idx] = new assAnswerErrorTextQuestion(
                $error["errorText"],
                $text_correct,
                $points,
                $error["positions"],
                $error["error_type"]);
        }
        ksort($this->errordata);
    }

    public function createErrorTextOutput($selections = null, $graphicalOutput = false, $correct_solution = false, $use_link_tags = true)
    {
        include_once "./Services/Utilities/classes/class.ilStr.php";
        if (!is_array($selections)) $selections = array();

        list($errorItems, $text) = $this->getErrorsFromText($this->getErrorText());

        $word = "";
        $returnTextArray = [];

        for ($i = 0; $i < ilStr::strLen($text); $i++) {
            $addLink = false;
            if ($this->getErrorType() == "W") {
                if ($i == 0 || in_array(ilStr::subStr($text, $i - 1, 1), [" ", "\n", "\r"])) {
                    $position = $i;
                }

                if (!in_array(ilStr::subStr($text, $i, 1), [" ", "\n", "\r"])) {
                    $word .= ilStr::subStr($text, $i, 1);
                }

                if ((in_array(ilStr::subStr($text, $i + 1, 1), [" ", "\n", "\r"]) && $word) || $i + 1 == ilStr::strLen($text)) {
                    $addLink = true;
                }
            } else {
                $position = $i;
                $word = ilStr::subStr($text, $i, 1);
                if (in_array(ilStr::subStr($text, $i, 2), ["\r\n"])) {
                    $word = " ";
                }
                if (!in_array(ilStr::subStr($text, $i - 1, 2), ["\r\n"])) {
                    $addLink = true;
                }
            }

            if ($addLink) {
                $returnTextArray[$position] = [
                    "word" => $word,
                    "isSelected" => array_key_exists($position, $selections),
                    "isRight" => false,
                    "selectType" => "S",
                    "startMultiSelect" => false,
                    "endMultiSelect" => false
                ];
                $word = "";
            }
            if (in_array(ilStr::subStr($text, $i, 1), [" ", "\n", "\r"]) && !$addLink) {
                $returnTextArray[$i * -1] = [
                    "word" => ilStr::subStr($text, $i, 1),
                    "isSelected" => false,
                    "isRight" => false,
                    "selectType" => "S",
                    "startMultiSelect" => false,
                    "endMultiSelect" => false
                ];
            }
        }

        foreach ($this->errordata as $solution) {
            $selectionType = $solution->error_type;
            if ($selectionType == "S") {
                if ($returnTextArray[$solution->positions]["isSelected"]) {
                    $returnTextArray[$solution->positions]["isRight"] = "true";
                }
                if ($correct_solution) {
                    $returnTextArray[$solution->positions]["word"] = $solution->text_correct;
                    $returnTextArray[$solution->positions]["isSelected"] = "true";
                }

            }
            if ($selectionType == "M") {
                $multiSelectionPositions = explode(",", $solution->positions);
                ksort($multiSelectionPositions);
                $isRight = true;
                $isOneSelected = false;
                foreach ($multiSelectionPositions as $multiSelectionPosition) {
                    if (!$returnTextArray[$multiSelectionPosition]["isSelected"]) {
                        $isRight = false;
                    } else {
                        $isOneSelected = true;
                    }

                    $returnTextArray[$multiSelectionPosition]["selectType"] = "M";

                    if ($correct_solution) {
                        if ($multiSelectionPositions[count($multiSelectionPositions) - 1] == $multiSelectionPosition) {
                            $returnTextArray[$multiSelectionPosition]["word"] = $solution->text_correct;
                            $returnTextArray[$multiSelectionPosition]["isSelected"] = "true";
                        } else {
                            unset($returnTextArray[$multiSelectionPosition]);
                        }
                    }
                }
                $returnTextArray[$multiSelectionPositions[count($multiSelectionPositions) - 1]]["endMultiSelect"] = true;
                $returnTextArray[$multiSelectionPositions[count($multiSelectionPositions) - 1]]["isOneSelected"] = $isOneSelected;
                $returnTextArray[$multiSelectionPositions[0]]["startMultiSelect"] = true;
                $returnTextArray[$multiSelectionPositions[0]]["isOneSelected"] = $isOneSelected;
                $returnTextArray[$multiSelectionPositions[count($multiSelectionPositions) - 1]]["isRight"] = $isRight;
            }
        }

        foreach ($returnTextArray as $textKey => $textItem) {
            $img = "";
            if ($textKey >= 0) {
                if ($use_link_tags) {
                    $text = '<a href="#" position="' . $textKey . '"' . 'class="%s"' . '>%s</a>';
                } else {
                    $text = '<span class="%s">%s</span>';
                }

                if ($graphicalOutput) {
                    if ($returnTextArray[$textKey]["selectType"] == "M") {
                        if ($returnTextArray[$textKey]["startMultiSelect"] && $returnTextArray[$textKey]["isOneSelected"]) {
                            $text = "<span class='selGroup'>" . $text;
                        }
                        if ($returnTextArray[$textKey]["endMultiSelect"] && $returnTextArray[$textKey]["isOneSelected"]) {
                            $text .= "</span>";
                            $img = ' <img src="' . ilUtil::getImagePath("icon_not_ok.svg") . '" alt="' . $this->lng->txt("answer_is_wrong") . '" title="' . $this->lng->txt("answer_is_wrong") . '" /> ';
                            if ($returnTextArray[$textKey]["isRight"]) {
                                $img = ' <img src="' . ilUtil::getImagePath("icon_ok.svg") . '" alt="' . $this->lng->txt("answer_is_right") . '" title="' . $this->lng->txt("answer_is_right") . '" /> ';
                            }
                        }
                    }
                    if ($returnTextArray[$textKey]["isSelected"] && $returnTextArray[$textKey]["selectType"] == "S") {
                        $img = ' <img src="' . ilUtil::getImagePath("icon_not_ok.svg") . '" alt="' . $this->lng->txt("answer_is_wrong") . '" title="' . $this->lng->txt("answer_is_wrong") . '" /> ';
                        if ($returnTextArray[$textKey]["isRight"]) {
                            $img = ' <img src="' . ilUtil::getImagePath("icon_ok.svg") . '" alt="' . $this->lng->txt("answer_is_right") . '" title="' . $this->lng->txt("answer_is_right") . '" /> ';
                        }
                    }
                }
            } else {
                $text = "%s%s";
            }

            if ($returnTextArray[$textKey]["isSelected"]) {
                $text = vsprintf($text, ["ilc_qetitem_ErrorTextSelected", $returnTextArray[$textKey]["word"]]);
            } else {
                $text = vsprintf($text, ["", $returnTextArray[$textKey]["word"]]);
            }


            $returnTextArray[$textKey]["word"] = $text . $img;
        }

        $returnText = implode(array_column($returnTextArray, "word"));

        return $returnText;
    }

    public function createErrorTextExport($selections = null)
    {
        list($errorItems, $text) = $this->getErrorsFromText($this->getErrorText());
        $word = "";
        $textReturn = "";

        for ($i = 0; $i < ilStr::strLen($text); $i++) {
            $addLink = false;
            if ($this->getErrorType() == "W") {
                if ($i == 0 || in_array(ilStr::subStr($text, $i - 1, 1), [" ", "\n", "\r"])) {
                    $position = $i;
                }

                if (!in_array(ilStr::subStr($text, $i, 1), [" ", "\n", "\r"])) {
                    $word .= ilStr::subStr($text, $i, 1);
                }

                if ((in_array(ilStr::subStr($text, $i + 1, 1), [" ", "\n", "\r"]) && $word) || $i + 1 == ilStr::strLen($text)) {
                    $addLink = true;
                }

            } else {
                $position = $i;
                $word = ilStr::subStr($text, $i, 1);
                if (in_array(ilStr::subStr($text, $i, 2), ["\r\n"])) {
                    $word = " ";
                }
                if (!in_array(ilStr::subStr($text, $i - 1, 2), ["\r\n"])) {
                    $addLink = true;
                }
            }

            if ($addLink) {
                if (array_key_exists($position, $selections)) {
                    $textReturn .= "#" . $word . "#";
                } else {
                    $textReturn .= $word;
                }
                $word = "";
            }
            if (in_array(ilStr::subStr($text, $i, 1), [" ", "\n", "\r"]) && !$addLink) {
                $textReturn .= ilStr::subStr($text, $i, 1);
            }
        }

        ilUtil::prepareFormOutput($textReturn);

        return $textReturn;
    }

    public function getBestSelection($withPositivePointsOnly = true)
    {
        list($errorItems, $text) = $this->getErrorsFromText($this->getErrorText());

        $selections = array();
        foreach ($errorItems as $key => $errorItem) {
            foreach ($this->errordata as $errorData) {
                if (!$withPositivePointsOnly || $withPositivePointsOnly && $errorData->points > 0) {
                    if ($errorItem["positions"] == $errorData->positions) {
                        $positions = explode(",", $errorData->positions);
                        foreach ($positions as $position) {
                            $selections[$position] = ["position" => $position];
                        }
                    }
                } else {
                    unset($errorItems[$key]);
                }
            }
        }

        krsort($selections);
        ksort($errorItems);

        return [$selections, $errorItems];
    }

    protected function getPointsForSelectedPositions($answers)
    {
        $total = 0;

        foreach ($this->errordata as $solution) {
            $points = 0;
            if ($solution->error_type == "S") {
                if (in_array($solution->positions, $answers)) {
                    $points = $solution->points;
                    unset($answers[$solution->positions]);
                }
            }

            if ($solution->error_type == "M") {
                $points = $this->getPointsWrong();
                $multiSelectionPositions = explode(",", $solution->positions);
                $allIsSelected = true;
                foreach ($multiSelectionPositions as $key => $multiSelectionPosition) {
                    if (!in_array($multiSelectionPosition, $answers)) {
                        $allIsSelected = false;
                    }
                    unset($answers[$multiSelectionPosition]);
                }
                if ($allIsSelected) {
                    $points = $solution->points;
                }
            }
            $total += $points;
        }

        $points = $this->getPointsWrong();
        foreach ($answers as $answer) {
            $total += $points;
        }

        return $total;
    }

    /**
     * Flush error data
     */
    public function flushErrorData()
    {
        $this->errordata = array();
    }

    public function addErrorData($text_wrong, $text_correct, $points, $positions, $error_type)
    {
        include_once "class.assAnswerErrorTextQuestion.php";
        array_push($this->errordata, new assAnswerErrorTextQuestion($text_wrong, $text_correct, $points, $positions, $error_type));
    }

    /**
     * Get error data
     *
     * @return array Error data
     */
    public function getErrorData()
    {
        return $this->errordata;
    }

    /**
     * Get error text
     *
     * @return string Error text
     */
    public function getErrorText()
    {
        return $this->errortext;
    }

    /**
     * Set error text
     *
     * @param string $a_value Error text
     */
    public function setErrorText($a_value)
    {
        $this->errortext = $a_value;
    }

    /**
     * Set text size in percent
     *
     * @return double Text size in percent
     */
    public function getTextSize()
    {
        return $this->textsize;
    }

    /**
     * Set text size in percent
     *
     * @param double $a_value text size in percent
     */
    public function setTextSize($a_value)
    {
        // in self-assesment-mode value should always be set (and must not be null)
        if ($a_value === null) {
            $a_value = 100;
        }
        $this->textsize = $a_value;
    }

    /**
     * Set text direction
     *
     * @return string Text direction
     */
    public function getTextDirection()
    {
        return $this->text_direction;
    }

    /**
     * Set text direction
     *
     * @param string $a_value text direction
     */
    public function setTextDirection($a_value)
    {
        if ($a_value === null) {
            $a_value = "LTR";
        }
        $this->text_direction = $a_value;
    }

    /**
     * Set error type
     *
     * @return string error type
     */
    public function getErrorType()
    {
        return $this->error_type;
    }

    /**
     * Set error type
     *
     * @param string $a_value error type
     */
    public function setErrorType($a_value)
    {
        if ($a_value === null) {
            $a_value = "W";
        }
        $this->error_type = $a_value;
    }

    /**
     * Get wrong points
     *
     * @return double Points for wrong selection
     */
    public function getPointsWrong()
    {
        return $this->points_wrong;
    }

    /**
     * Set wrong points
     *
     * @param double $a_value Points for wrong selection
     */
    public function setPointsWrong($a_value)
    {
        $this->points_wrong = $a_value;
    }

    /**
     * Object getter
     */
    public function __get($value)
    {
        switch ($value) {
            case "errortext":
                return $this->getErrorText();
                break;
            case "textsize":
                return $this->getTextSize();
                break;
            case "text_direction":
                return $this->getTextDirection();
                break;
            case "error_type":
                return $this->getErrorType();
                break;
            case "points_wrong":
                return $this->getPointsWrong();
                break;
            default:
                return parent::__get($value);
                break;
        }
    }

    /**
     * Object setter
     */
    public function __set($key, $value)
    {
        switch ($key) {
            case "errortext":
                $this->setErrorText($value);
                break;
            case "textsize":
                $this->setTextSize($value);
                break;
            case "text_direction":
                $this->setTextDirection($value);
                break;
            case "error_type":
                $this->setErrorType($value);
                break;
            case "points_wrong":
                $this->setPointsWrong($value);
                break;
            default:
                parent::__set($key, $value);
                break;
        }
    }

    /**
     * Returns a JSON representation of the question
     */
    public function toJSON()
    {
        include_once("./Services/RTE/classes/class.ilRTE.php");
        $result = array();
        $result['id'] = (int)$this->getId();
        $result['type'] = (string)$this->getQuestionType();
        $result['title'] = (string)$this->getTitle();
        $result['question'] = $this->formatSAQuestion($this->getQuestion());
        $result['text'] = (string)ilRTE::_replaceMediaObjectImageSrc($this->getErrorText(), 0);
        $result['nr_of_tries'] = (int)$this->getNrOfTries();
        $result['shuffle'] = (bool)$this->getShuffle();
        $result['feedback'] = array(
            'onenotcorrect' => $this->formatSAQuestion($this->feedbackOBJ->getGenericFeedbackTestPresentation($this->getId(), false)),
            'allcorrect' => $this->formatSAQuestion($this->feedbackOBJ->getGenericFeedbackTestPresentation($this->getId(), true))
        );

        $answers = array();
        foreach ($this->getErrorData() as $idx => $answer_obj) {
            array_push($answers, array(
                "answertext_wrong" => (string)$answer_obj->text_wrong,
                "answertext_correct" => (string)$answer_obj->text_correct,
                "points" => (float)$answer_obj->points,
                "order" => (int)$idx + 1,
                "positions" => (string)$answer_obj->positions,
                "error_type" => (string)$answer_obj->error_type
            ));
        }
        $result['correct_answers'] = $answers;

        list($errorItems, $text) = $this->getErrorsFromText($this->getErrorText());

        foreach ($errorItems as $key => $errorItem) {
            foreach ($result["correct_answers"] as $aidx => $answer) {
                if ($errorItem["positions"] == $answer["positions"] &&
                    !$answer["pos"]
                ) {
                    $result["correct_answers"][$aidx]["pos"] = $this->getId() . "_" . ($key + 1);
                    break;
                }
            }
            array_push($answers, array(
                "answertext" => (string)ilUtil::prepareFormOutput($errorItem["errorText"]),
                "order" => $this->getId() . "_" . ($key + 1)
            ));
        }

        $result['answers'] = $answers;

        $mobs = ilObjMediaObject::_getMobsOfObject("qpl:html", $this->getId());
        $result['mobs'] = $mobs;

        return json_encode($result);
    }

    /**
     * Get all available operations for a specific question
     *
     * @param string $expression
     *
     * @internal param string $expression_type
     * @return array
     */
    public function getOperators($expression)
    {
        require_once "./Modules/TestQuestionPool/classes/class.ilOperatorsExpressionMapping.php";
        return ilOperatorsExpressionMapping::getOperatorsByExpression($expression);
    }

    /**
     * Get all available expression types for a specific question
     * @return array
     */
    public function getExpressionTypes()
    {
        return array(
            iQuestionCondition::PercentageResultExpression,
            iQuestionCondition::NumberOfResultExpression,
            iQuestionCondition::EmptyAnswerExpression,
            iQuestionCondition::ExclusiveResultExpression
        );
    }

    /**
     * Get the user solution for a question by active_id and the test pass
     *
     * @param int $active_id
     * @param int $pass
     *
     * @return ilUserQuestionResult
     */
    public function getUserQuestionResult($active_id, $pass)
    {
        /** @var ilDB $ilDB */
        global $ilDB;
        $result = new ilUserQuestionResult($this, $active_id, $pass);

        $data = $ilDB->queryF(
            "SELECT value1+1 as value1 FROM tst_solutions WHERE active_fi = %s AND pass = %s AND question_fi = %s AND step = (
				SELECT MAX(step) FROM tst_solutions WHERE active_fi = %s AND pass = %s AND question_fi = %s
			)",
            array("integer", "integer", "integer", "integer", "integer", "integer"),
            array($active_id, $pass, $this->getId(), $active_id, $pass, $this->getId())
        );

        while ($row = $ilDB->fetchAssoc($data)) {
            $result->addKeyValue($row["value1"], $row["value1"]);
        }

        $points = $this->calculateReachedPoints($active_id, $pass);
        $max_points = $this->getMaximumPoints();

        $result->setReachedPercentage(($points / $max_points) * 100);

        return $result;
    }

    /**
     * If index is null, the function returns an array with all anwser options
     * Else it returns the specific answer option
     *
     * @param null|int $index
     *
     * @return array|ASS_AnswerSimple
     */
    public function getAvailableAnswerOptions($index = null)
    {
        $error_text_array = explode(' ', $this->errortext);

        if ($index !== null) {
            if (array_key_exists($index, $error_text_array)) {
                return $error_text_array[$index];
            }
            return null;
        } else {
            return $error_text_array;
        }
    }

    /**
     * Get the submitted user input as a serializable value
     *
     * @return mixed user input (scalar, object or array)
     */
    public function getSolutionSubmit()
    {
        return 0;
    }

    /**
     * Calculate the reached points for a submitted user input
     *
     * @param mixed user input (scalar, object or array)
     * @return integer
     */
    public function calculateReachedPointsforSolution($solution)
    {
        return 0;
    }
}