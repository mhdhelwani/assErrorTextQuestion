<?php

require_once './Modules/Test/classes/inc.AssessmentConstants.php';

/**
 * Class for error text answers
 *
 * @author Mohammed Helwani <mohammed.helwani@llz.uni-halle.de>
 * @version $Id: $
 * @ingroup    ModulesTestQuestionPool
 *
 */
class assAnswerErrorTextQuestion
{
    /**
     * Array consisting of one errortext-answer
     * E.g. array('text_wrong' => 'Guenther', 'text_correct' => 'Günther', 'points' => 20, 'positions' => 10, 15, 'error_type' => 'S')
     *
     * @var array Array consisting of one errortext-answer
     */
    protected $arrData;

    /**
     * assAnswerErrorTextL constructor
     *
     * @param string $text_wrong Wrong text
     * @param string $text_correct Correct text
     * @param double $points Points
     * @param int $positions Positions
     * @param string $error_type Error type
     *
     */
    public function __construct($text_wrong = "", $text_correct = "", $points = 0.0, $positions = 0, $error_type ="S")
    {
        $this->arrData = array(
            'text_wrong' => $text_wrong,
            'text_correct' => $text_correct,
            'points' => $points,
			'positions' => $positions,
			'error_type' => $error_type
		);
	}

    /**
     * Object getter
     */
    public function __get($value)
    {
        switch ($value) {
            case "text_wrong":
            case "text_correct":
            case "points":
            case "positions":
            case "error_type":
                return $this->arrData[$value];
                break;
            default:
                return null;
                break;
        }
    }

    /**
     * Object setter
     */
    public function __set($key, $value)
    {
        switch ($key) {
            case "text_wrong":
            case "text_correct":
            case "points":
            case "positions":
            case "error_type":
                $this->arrData[$key] = $value;
                break;
            default:
                break;
        }
    }
}