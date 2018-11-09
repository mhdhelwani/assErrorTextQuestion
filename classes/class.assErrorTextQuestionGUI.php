<?php

require_once './Modules/TestQuestionPool/classes/class.assQuestionGUI.php';
require_once './Modules/TestQuestionPool/interfaces/interface.ilGuiQuestionScoringAdjustable.php';
require_once './Modules/TestQuestionPool/interfaces/interface.ilGuiAnswerScoringAdjustable.php';

require_once './Modules/Test/classes/inc.AssessmentConstants.php';

/**
 * ErrorTextQuestionGUI class for question type plugins
 *
 * @author Mohammed Helwani <mohammed.helwani@llz.uni-halle.de>
 * @version $Id: $
 * @ingroup    ModulesTestQuestionPool
 *
 * @ilctrl_iscalledby assErrorTextQuestionGUI: ilObjQuestionPoolGUI, ilObjTestGUI, ilQuestionEditGUI, ilTestExpressPageObjectGUI
 */
class assErrorTextQuestionGUI extends assQuestionGUI implements ilGuiQuestionScoringAdjustable, ilGuiAnswerScoringAdjustable
{
    /**
     * @var ilassErrorTextQuestionPlugin    The plugin object
     */
    var $plugin = null;


    /**
     * @var assErrorTextQuestion    The question object
     */
    var $object = null;

    /**
     * assErrorTextQuestionGUI constructor
     *
     * @param integer $id The database id of a single choice question object
     * @access public
     */
    public function __construct($id = -1)
    {
        parent::__construct();
        include_once "./Services/Component/classes/class.ilPlugin.php";
        $this->plugin = ilPlugin::getPluginObject(IL_COMP_MODULE, "TestQuestionPool", "qst", "assErrorTextQuestion");
        $this->plugin->includeClass("class.assErrorTextQuestion.php");
        $this->object = new assErrorTextQuestion();
        $this->setErrorMessage($this->lng->txt("msg_form_save_error"));
        if ($id >= 0) {
            $this->object->loadFromDb($id);
        }
    }

    /**
     * Creates an output of the edit form for the question
     *
     * @param bool $checkonly
     * @return bool
     */
    public function editQuestion($checkonly = FALSE)
    {
        $save = $this->isSaveCommand();
        $this->getQuestionTemplate();

        include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->outQuestionType());
        $form->setMultipart(FALSE);
        $form->setTableWidth("100%");
        $form->setId("orderinghorizontal");

        $this->addBasicQuestionFormProperties($form);

        $this->populateQuestionSpecificFormPart($form);

        if (count($this->object->getErrorData()) || $checkonly) {
            $this->populateAnswerSpecificFormPart($form);
        }
        // points for wrong selection
        $points_wrong = new ilNumberInputGUI($this->lng->txt("points_wrong"), "points_wrong");
        $points_wrong->allowDecimals(true);
        $points_wrong->setValue($this->object->getPointsWrong() ? $this->object->getPointsWrong() : -1);
        $points_wrong->setInfo($this->lng->txt("points_wrong_info"));
        $points_wrong->setSize(6);
        $points_wrong->setRequired(true);
        $form->addItem($points_wrong);

        $this->populateTaxonomyFormSection($form);

        $form->addCommandButton("analyze", $this->lng->txt('analyze_errortext'));
        $this->addQuestionFormCommandButtons($form);

        $errors = false;

        if ($save) {
            $form->setValuesByPost();
            $errors = !$form->checkInput();
            if($form->getItemByPostVar("text_direction")->getValue() == "RTL" &&
                $form->getItemByPostVar("error_type")->getValue() == "C"){
                ilUtil::sendFailure($this->object->getPlugin()->txt('rtl_char_error_message'), TRUE);
                $errors = true;
            }

            if($form->getItemByPostVar("is_error_text_changed")->getValue() == "1" ){
                ilUtil::sendFailure($this->object->getPlugin()->txt('is_error_text_changed_message'), TRUE);
                $errors = true;
            }

            $form->setValuesByPost(); // again, because checkInput now performs the whole stripSlashes handling and we need this if we don't want to have duplication of backslashes
            if ($errors) $checkonly = false;
        }

        $this->tpl->addJavascript($this->plugin->getDirectory() . "/js/errortextquestion.js");
        if (!$checkonly) $this->tpl->setVariable("QUESTION_DATA", $form->getHTML());
        return $errors;
    }

    /**
     * @param ilPropertyFormGUI $form
     * @return \ilPropertyFormGUI|void
     */
    public function populateAnswerSpecificFormPart(ilPropertyFormGUI $form)
    {
        $header = new ilFormSectionHeaderGUI();
        $header->setTitle($this->lng->txt("errors_section"));
        $form->addItem($header);

        include_once "class.ilErrorTextWizardInputQuestionGUI.php";
        $errordata = new ilErrorTextWizardInputQuestionGUI($this->lng->txt("errors"), "errordata", $this->plugin);
        $errordata->setKeyName($this->lng->txt('text_wrong'));
        $errordata->setValueName($this->lng->txt('text_correct'));
        $errordata->setValues($this->object->getErrorData());
        $form->addItem($errordata);

        return $form;
    }

    /**
     * @param $form ilPropertyFormGUI
     * @return \ilPropertyFormGUI|void
     */
    public function populateQuestionSpecificFormPart(ilPropertyFormGUI $form)
    {
        $langDirection = new ilSelectInputGUI($this->plugin->txt("text_direction"), "text_direction");
        $langDirection->setOptions(["LTR" => "LTR", "RTL" => "RTL"]);
        $langDirection->setValue($this->object->getTextDirection());
        $form->addItem($langDirection);

        $errorType = new ilSelectInputGUI($this->plugin->txt("error_type"), "error_type");
        $errorType->setOptions(["W" => $this->plugin->txt("error_type_word"), "C" => $this->plugin->txt("error_type_char")]);
        $errorType->setValue($this->object->getErrorType());
        $form->addItem($errorType);

        $isErrorTextChanged = new ilHiddenInputGUI("is_error_text_changed");
        $isErrorTextChanged->setValue(0);
        $form->addItem($isErrorTextChanged);
        
        // errortext
        $errortext = new ilTextAreaInputGUI($this->lng->txt("errortext"), "errortext");
        $errortext->setValue($this->object->getErrorText());
        $errortext->setRequired(TRUE);
        $errortext->setInfo($this->plugin->txt("errortext_info"));
        $errortext->setRows(10);
        $errortext->setCols(80);
        $form->addItem($errortext);

        if (!$this->object->getSelfAssessmentEditingMode()) {
            // textsize
            $textsize = new ilNumberInputGUI($this->lng->txt("textsize"), "textsize");
            $textsize->setValue(strlen($this->object->getTextSize()) ? $this->object->getTextSize() : 100.0);
            $textsize->setInfo($this->lng->txt("textsize_errortext_info"));
            $textsize->setSize(6);
            $textsize->setSuffix("%");
            $textsize->setMinValue(10);
            $textsize->setRequired(true);
            $form->addItem($textsize);
        }
    }

    /**
     * Parse the error text
     */
    public function analyze()
    {
        $this->writePostData(true);
        list($errorItems, $text) = $this->object->getErrorsFromText($_POST['errortext']);
        $this->object->setErrorData($errorItems);
        $this->editQuestion();
    }

    /**
     * Evaluates a posted edit form and writes the form data in the question object
     *
     * @param bool $always
     * @return integer A positive value, if one of the required fields wasn't set, else 0
     */
    public function writePostData($always = false)
    {
        $hasErrors = (!$always) ? $this->editQuestion(true) : false;
        if (!$hasErrors) {
            require_once 'Services/Form/classes/class.ilPropertyFormGUI.php';
            $this->writeQuestionGenericPostData();
            $this->writeQuestionSpecificPostData(new ilPropertyFormGUI());
            $this->writeAnswerSpecificPostData(new ilPropertyFormGUI());
            $this->saveTaxonomyAssignments();
            return 0;
        }
        return 1;
    }

    public function writeAnswerSpecificPostData(ilPropertyFormGUI $form)
    {
        $this->object->flushErrorData();
        if (is_array($_POST['errordata']['key'])) {
            foreach ($_POST['errordata']['key'] as $idx => $val) {
                $this->object->addErrorData($val,
                    $_POST['errordata']['value'][$idx],
                    $_POST['errordata']['points'][$idx],
                    $_POST['errordata']['positions'][$idx],
                    $_POST['errordata']['error_type'][$idx]
                );
            }
        }
    }

    public function writeQuestionSpecificPostData(ilPropertyFormGUI $form)
    {
        $questiontext = $_POST["question"];
        $this->object->setQuestion($questiontext);
        $this->object->setErrorText($_POST["errortext"]);
        $points_wrong = str_replace(",", ".", $_POST["points_wrong"]);
        if (strlen($points_wrong) == 0)
            $points_wrong = -1.0;
        $this->object->setPointsWrong($points_wrong);
        $this->object->setTextDirection($_POST["text_direction"]);
        $this->object->setErrorType($_POST["error_type"]);

        if (!$this->object->getSelfAssessmentEditingMode()) {
            $this->object->setTextSize($_POST["textsize"]);
        }
    }

    function getTestOutput($active_id,
                           $pass = NULL,
                           $is_postponed = FALSE,
                           $use_post_solutions = FALSE,
                           $show_feedback = FALSE)
    {
        // generate the question output
        $template = $this->plugin->getTemplate("tpl.il_as_qpl_errortextquestion_output.html");
        if ($active_id) {
            $solutions = NULL;
            include_once "./Modules/Test/classes/class.ilObjTest.php";
            if (!ilObjTest::_getUsePreviousAnswers($active_id, true)) {
                if (is_null($pass)) $pass = ilObjTest::_getPass($active_id);
            }
            $solutions = $this->object->getUserSolutionPreferingIntermediate($active_id, $pass);
        }

        $selections = array();
        if (is_array($solutions)) {
            foreach ($solutions as $solution) {
                $selections[$solution['value1']] = [
                    "position" => $solution['value1']];
            }
            krsort($selections);
        }
        $style = 'style= "cursor: pointer; white-space:pre-wrap;';
        if ($this->object->getTextSize() >= 10) {
            $style .= ' font-size: ' . $this->object->getTextSize() . '%;';
        }
        $style .= "direction:" . $this->object->getTextDirection() . ';"';

        $template->setVariable("STYLE", $style);
        $template->setVariable("DIR", $this->object->getTextDirection());
        $template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($this->object->getQuestion(), TRUE));
        $errortext = $this->object->createErrorTextOutput($selections);
        $this->ctrl->setParameterByClass($this->getTargetGuiClass(), 'errorvalue', '');
        $template->setVariable("ERRORTEXT", $errortext);
        $template->setVariable("ERRORTEXT_ID", "qst_" . $this->object->getId());
        $template->setVariable("ERRORTEXT_VALUE", implode(",",array_column($selections, "position")));

        $questionoutput = $template->get();
        if (!$show_question_only) {
            // get page object output
            $questionoutput = $this->getILIASPage($questionoutput);
        }
        $this->tpl->addJavascript($this->plugin->getDirectory() . "/js/errortextquestion.js");
        $this->tpl->addCss($this->plugin->getDirectory() . "/css/errortextquestion.css");
        $questionoutput = $template->get();
        $pageoutput = $this->outQuestionPage("", $is_postponed, $active_id, $questionoutput);
        return $pageoutput;
    }


    function getPreview($show_question_only = FALSE, $showInlineFeedback = false)
    {
        $selections = is_object($this->getPreviewSession()) ? (array)$this->getPreviewSession()->getParticipantsSolution() : array();

        $template = $this->plugin->getTemplate("tpl.il_as_qpl_errortextquestion_output.html");

        $style = 'style= "cursor: pointer; white-space:pre-wrap;';
        if ($this->object->getTextSize() >= 10) {
            $style .= ' font-size: ' . $this->object->getTextSize() . '%;';
        }
        $style .= "direction:" . $this->object->getTextDirection() . ';"';

        $template->setVariable("STYLE", $style);
        $template->setVariable("DIR", $this->object->getTextDirection());

        $template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($this->object->getQuestion(), TRUE));
        $errortext = $this->object->createErrorTextOutput($selections);
        $template->setVariable("ERRORTEXT", $errortext);
        $template->setVariable("ERRORTEXT_ID", "qst_" . $this->object->getId());
        $questionoutput = $template->get();
        if (!$show_question_only) {
            // get page object output
            $questionoutput = $this->getILIASPage($questionoutput);
        }
        $this->tpl->addJavascript($this->plugin->getDirectory() . "/js/errortextquestion.js");
        $this->tpl->addCss($this->plugin->getDirectory() . "/css/errortextquestion.css");
        return $questionoutput;
    }

    /**
     * Get the question solution output
     *
     * The getSolutionOutput() method is used to print either the
     * user's pass' solution or the best possible solution for the
     * current errorText question object.
     *
     * @param    integer $active_id The active test id
     * @param    integer $pass The test pass counter
     * @param    boolean $graphicalOutput Show visual feedback for right/wrong answers
     * @param    boolean $result_output Show the reached points for parts of the question
     * @param    boolean $show_question_only Show the question without the ILIAS content around
     * @param    boolean $show_feedback Show the question feedback
     * @param    boolean $show_correct_solution Show the correct solution instead of the user solution
     * @param    boolean $show_manual_scoring Show specific information for the manual scoring output
     * @param    boolean $show_question_text
     *
     * @return    string    HTML solution output
     **/
    function getSolutionOutput($active_id, $pass = NULL,
                               $graphicalOutput = FALSE,
                               $result_output = FALSE,
                               $show_question_only = TRUE,
                               $show_feedback = FALSE,
                               $show_correct_solution = FALSE,
                               $show_manual_scoring = FALSE,
                               $show_question_text = TRUE)
    {
        // get the solution of the user for the active pass or from the last pass if allowed
        $template = $this->plugin->getTemplate("tpl.il_as_qpl_errortextquestion_output_solution.html");

        $selections = array();
        if (($active_id > 0) && (!$show_correct_solution)) {
            /* Retrieve tst_solutions entries. */
            $reached_points = $this->object->getReachedPoints($active_id, $pass);
            $solutions =& $this->object->getSolutionValues($active_id, $pass);
            if (is_array($solutions)) {
                foreach ($solutions as $solution) {
                    $selections[$solution['value1']] = [
                        "position" => $solution['value1']];
                }
                krsort($selections);
            }
        } else {
            list ($selections, $errorItems) = $this->object->getBestSelection();
            $reached_points = $this->object->getPoints();
        }

        if ($result_output) {
            $resulttext = ($reached_points == 1) ? "(%s " . $this->lng->txt("point") . ")" : "(%s " . $this->lng->txt("points") . ")";
            $template->setVariable("RESULT_OUTPUT", sprintf($resulttext, $reached_points));
        }

        $style = 'style= "white-space:pre-wrap; direction:' . $this->object->getTextDirection() . ';';
        if ($this->object->getTextSize() >= 10) {
            $style .= ' font-size: ' . $this->object->getTextSize() . '%;';
        }
        $style .= '"';

        $template->setVariable("STYLE", $style);
        $template->setVariable("DIR", $this->object->getTextDirection());
        if ($show_question_text == true) {
            $template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($this->object->getQuestion(), TRUE));
        }

        $errortext = $this->object->createErrorTextOutput($selections, $graphicalOutput, $show_correct_solution, false);

        $template->setVariable("ERRORTEXT", $errortext);
        $questionoutput = $template->get();

        $solutiontemplate = new ilTemplate("tpl.il_as_tst_solution_output.html", TRUE, TRUE, "Modules/TestQuestionPool");

        $feedback = '';
        if ($show_feedback) {
            if (!$this->isTestPresentationContext()) {
                $fb = $this->getGenericFeedbackOutput($active_id, $pass);
                $feedback .= strlen($fb) ? $fb : '';
            }

            $fb = $this->getSpecificFeedbackOutput($active_id, $pass);
            $feedback .= strlen($fb) ? $fb : '';
        }
        if (strlen($feedback)) $solutiontemplate->setVariable("FEEDBACK", $feedback);

        $solutiontemplate->setVariable("SOLUTION_OUTPUT", $questionoutput);

        $solutionoutput = $solutiontemplate->get();
        if (!$show_question_only) {
            // get page object output
            $solutionoutput = $this->getILIASPage($solutionoutput);
        }
        return $solutionoutput;
    }

    function getSpecificFeedbackOutput($active_id, $pass)
    {
        list ($selections, $errorItems) = $this->object->getBestSelection(false);

        if (!$this->object->feedbackOBJ->specificAnswerFeedbackExists(array_keys($errorItems))) {
            return '';
        }

        $feedback = '<table class="test_specific_feedback"><tbody>';

        $matchedIndexes = array();

        foreach ($errorItems as $index => $answer) {
            $ordinal = $index + 1;

            $feedback .= '<tr>';

            $feedback .= '<td class="text-nowrap">' . $ordinal . '. ' . $answer["errorText"] . ':</td>';

            foreach ($this->object->getErrorData() as $idx => $ans) {
                if (isset($matchedIndexes[$idx])) {
                    continue;
                }

                if ($ans->positions == $answer["positions"]) {
                    $fb = $this->object->feedbackOBJ->getSpecificAnswerFeedbackTestPresentation(
                        $this->object->getId(), $idx
                    );

                    $feedback .= '<td>' . $fb . '</td>';

                    $matchedIndexes[$idx] = $idx;

                    break;
                }
            }

            $feedback .= '</tr>';
        }

        $feedback .= '</tbody></table>';

        return $this->object->prepareTextareaOutput($feedback, TRUE);
    }

    /**
     * Sets the ILIAS tabs for this question type
     * called from ilObjTestGUI and ilObjQuestionPoolGUI
     */
    public function setQuestionTabs()
    {
        global $rbacsystem, $ilTabs;

        $ilTabs->clearTargets();

        $this->ctrl->setParameterByClass("ilAssQuestionPageGUI", "q_id", $_GET["q_id"]);
        include_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";
        $q_type = $this->object->getQuestionType();

        if (strlen($q_type)) {
            $classname = $q_type . "GUI";
            $this->ctrl->setParameterByClass(strtolower($classname), "sel_question_types", $q_type);
            $this->ctrl->setParameterByClass(strtolower($classname), "q_id", $_GET["q_id"]);
        }

        if ($_GET["q_id"]) {
            if ($rbacsystem->checkAccess('write', $_GET["ref_id"])) {
                // edit page
                $ilTabs->addTarget("edit_page",
                    $this->ctrl->getLinkTargetByClass("ilAssQuestionPageGUI", "edit"),
                    array("edit", "insert", "exec_pg"),
                    "", "", $force_active);
            }

            $this->addTab_QuestionPreview($ilTabs);
        }

        $force_active = false;
        if ($rbacsystem->checkAccess('write', $_GET["ref_id"])) {
            $url = "";
            if ($classname) $url = $this->ctrl->getLinkTargetByClass($classname, "editQuestion");
            // edit question properties
            $ilTabs->addTarget("edit_question",
                $url,
                array("editQuestion", "save", "saveEdit", "analyze", "originalSyncForm"),
                $classname, "", $force_active);
        }

        // add tab for question feedback within common class assQuestionGUI
        $this->addTab_QuestionFeedback($ilTabs);

        // add tab for question hint within common class assQuestionGUI
        $this->addTab_QuestionHints($ilTabs);

        // add tab for question's suggested solution within common class assQuestionGUI
        $this->addTab_SuggestedSolution($ilTabs, $classname);

        // Assessment of questions sub menu entry
        if ($_GET["q_id"]) {
            $ilTabs->addTarget("statistics",
                $this->ctrl->getLinkTargetByClass($classname, "assessment"),
                array("assessment"),
                $classname, "");
        }

        $this->addBackTab($ilTabs);
    }

    /**
     * Returns a list of postvars which will be suppressed in the form output when used in scoring adjustment.
     * The form elements will be shown disabled, so the users see the usual form but can only edit the settings, which
     * make sense in the given context.
     *
     * E.g. array('cloze_type', 'image_filename')
     *
     * @return string[]
     */
    public function getAfterParticipationSuppressionAnswerPostVars()
    {
        return array();
    }

    /**
     * Returns a list of postvars which will be suppressed in the form output when used in scoring adjustment.
     * The form elements will be shown disabled, so the users see the usual form but can only edit the settings, which
     * make sense in the given context.
     *
     * E.g. array('cloze_type', 'image_filename')
     *
     * @return string[]
     */
    public function getAfterParticipationSuppressionQuestionPostVars()
    {
        return array();
    }

    /**
     * Returns an html string containing a question specific representation of the answers so far
     * given in the test for use in the right column in the scoring adjustment user interface.
     *
     * @param array $relevant_answers
     *
     * @return string
     */
    public function getAggregatedAnswersView($relevant_answers)
    {
        $passdata = array(); // Regroup answers into units of passes.
        foreach ($relevant_answers as $answer_chosen) {
            $passdata[$answer_chosen['active_fi'] . '-' . $answer_chosen['pass']][$answer_chosen["value1"]] = ["position" => $answer_chosen["value1"]];
        }

        $html = '';
        foreach ($passdata as $key => $pass) {
            krsort($pass);
            $passdata[$key] = $this->object->createErrorTextOutput($pass);
            $html .= $passdata[$key] . '<hr /><br />';
        }
        if ($this->object->getTextDirection() === "RTL") {
            $html = "<div style='direction:RTL;'>" . $html . "</div>";
        }
        return $html;
    }
}